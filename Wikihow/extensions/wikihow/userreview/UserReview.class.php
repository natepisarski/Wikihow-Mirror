<?php

class UserReview {
	const TABLE_SUBMITTED = "userreview_submitted";
	const TABLE_CURATED = "userreview_curated";

	const MIN_REVIEWS = 3;
	const MIN_REVIEW_HIGH_HELPFUL = 2;
	const MIN_REVIEW_LOW_HELPFUL = 6;
	const MIN_REVIEW_WHITELIST = 2;
	const MAX_CHARACTER_COUNT = 20;
	const MAX_REVIEW_LENGTH = 115;
	const MAX_REVIEWS = 30;
	const NUMBER_REVIEWS_KEY = "ur_numreviews";
	const NUMBER_REVIEWS_ALL_KEY = "ur_numreviews_all";
	const REVIEWS_KEY = "userreviews2";
	const ELIGIBLE_KEY = "userrevieweligible7";
	const HELPFULNESS_KEY = "userreviewhelpful5";
	const WHITELIST = "userreview_whitelist";

	const ELIGIBLE_EXPERT = 1;
	const ELIGIBLE_WHITELIST = 2;
	const ELIGIBLE_HELP_90 = 3;
	const ELIGIBLE_HELP_80_EDIT = 4;
	const ELIGIBLE_HELP_80_STU = 5;
	const ELIGIBLE_HELP_70_EDIT = 6;
	static $eligibleArray = [
		0 => "ineligible",
		self::ELIGIBLE_EXPERT => "expert",
		self::ELIGIBLE_WHITELIST => "whitelist",
		self::ELIGIBLE_HELP_90 => "help_90",
		self::ELIGIBLE_HELP_80_EDIT => "help_80_edit",
		self::ELIGIBLE_HELP_80_STU => "help_80_stu",
		self::ELIGIBLE_HELP_70_EDIT => "help_70_edit"
	];

	const SENSITIVE_ID = 9;

	const ICON_MIN_REVIEWS = 11;
	const ICON_MIN_HELPFULNESS = 80;

	public static function onMakeGlobalVariablesScript( &$vars, $out ) {
		$title = $out->getTitle();

		if ($title && $title->exists() && $title->inNamespace(NS_MAIN)) {
			$articleID = $title->getArticleID();
			$vars['wgIsUserReviewEligible'] = self::getArticleEligibilityString($articleID);
			$vars['wgNumReviews'] = self::getEligibleNumCuratedReviews($articleID);
		}

		return true;
	}

	/********
	Determines whether an article is eligible to display
	reviews on the page.
	 ********/
	public static function isArticleEligibleForReviews($articleId) {
		$eligible = self::getArticleEligibility($articleId);

		return $eligible>0?true:false;
	}

	public static function getArticleEligibility($articleId) {
		global $wgMemc;

		$key = wfMemcKey(self::ELIGIBLE_KEY, $articleId);
		$eligible = $wgMemc->get($key);
		if (!is_numeric($eligible)) {
			$eligible = self::setArticleEligibleForReviews($articleId);
		}

		return $eligible;
	}

	public static function getArticleEligibilityString($articleId) {
		$eligible = self::getArticleEligibility($articleId);

		if (array_key_exists($eligible, self::$eligibleArray)) {
			return self::$eligibleArray[$eligible];
		} else {
			return "unknown";
		}
	}

	public static function setArticleEligibleForReviews($articleId) {
		global $wgMemc, $wgLanguageCode;

		if ($wgLanguageCode != 'en') {
			return 0;
		}

		if (\SensitiveArticle\SensitiveArticle::hasReasons($articleId, [self::SENSITIVE_ID])) {
			return 0;
		}

		$title = Title::newFromId($articleId);
		if ( $title && !RobotPolicy::isTitleIndexable($title) ) {
			return 0;
		}

		$key = wfMemcKey(self::ELIGIBLE_KEY, $articleId);
		$isVerified = !Misc::isIntl() && SocialProofStats::articleVerified($articleId);
		$inWhitelist = ArticleTagList::hasTag(self::WHITELIST, $articleId);

		$eligible = 0;
		if ( $isVerified ){
			$eligible = self::ELIGIBLE_EXPERT;
		} elseif($inWhitelist) {
			$eligible = self::ELIGIBLE_WHITELIST;
		} else {
			$helpful = self::getHelpfulnessScore($articleId);
			if ($helpful > 0) {
				$dbr = wfGetDB(DB_REPLICA);
				$res = $dbr->select('titus_copy',
					array('ti_page_id', 'ti_helpful_total_including_deleted', 'ti_helpful_total', 'ti_helpful_percentage_including_deleted', 'ti_helpful_total', 'ti_helpful_percentage', 'ti_last_fellow_edit_timestamp', 'ti_stu_views_www', 'ti_stu_10s_percentage_www', 'ti_stu_3min_percentage_www'),
					array('ti_language_code' => "en", 'ti_page_id' => $articleId),
					__METHOD__);

				$row = $res ? $res->fetchObject() : null;
			}

			$datecutoff = "20140101"; //January 1, 2014 - titus table only stores 8 digits of the date

			if ( $helpful >= intval(wfMessage('userreview_helpful_threshold')->text()) ) {
				$eligible = self::ELIGIBLE_HELP_90;
			} elseif ( $helpful >= intval(wfMessage('userreview_helpful_fellow_threshold')->text()) ) {
				if ($row->ti_last_fellow_edit_timestamp >= $datecutoff) {
					$eligible = self::ELIGIBLE_HELP_80_EDIT;
				} elseif ($row->ti_stu_views_www >= wfMessage('userreview_stuview_threshold')->text() && $row->ti_stu_10s_percentage_www <= wfMessage('userreview_stu10s_threshold')->text() &&  $row->ti_stu_3min_percentage_www >= wfMessage('userreview_stu3min_threshold')->text()) {
					$eligible = self::ELIGIBLE_HELP_80_STU;
				}
			} elseif( $helpful >= intval(wfMessage('userreview_low_helpful_fellow_threshold')->text()) &&  $row->ti_last_fellow_edit_timestamp >= $datecutoff){
				$eligible = self::ELIGIBLE_HELP_70_EDIT;
			} else {
				$eligible = 0;
			}
		}

		$wgMemc->set($key, $eligible);
		return $eligible;
	}

	private static function getHelpfulnessScore($articleId) {
		global $wgMemc;

		$helpfulKey = wfMemcKey(self::HELPFULNESS_KEY, $articleId);
		$helpful = $wgMemc->get($helpfulKey);

		if (!is_numeric($helpful)) {
			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select('titus_copy',
				array('ti_page_id', 'ti_helpful_total_including_deleted', 'ti_helpful_total', 'ti_helpful_percentage_including_deleted', 'ti_helpful_total', 'ti_helpful_percentage', 'ti_last_fellow_edit_timestamp', 'ti_stu_views_www', 'ti_stu_10s_percentage_www', 'ti_stu_3min_percentage_www'),
				array('ti_language_code' => "en", 'ti_page_id' => $articleId),
				__METHOD__);

			$row = $res ? $res->fetchObject() : null;
			if (!$row) {
				$helpful = 0;
			} else {
				if ($row->ti_helpful_total < 10 && $row->ti_helpful_total_including_deleted >= 10) {
					$helpful = $row->ti_helpful_percentage_including_deleted;
				} elseif ($row->ti_helpful_total >= 10) {
					$helpful = $row->ti_helpful_percentage;
				} else {
					$helpful = 0;
				}
			}
			$wgMemc->set($helpfulKey, $helpful);
		}

		return $helpful;
	}

	public static function getIconHoverText(int $articleId): string {
		if (empty($articleId)) return '';
		$views = RequestContext::getMain()->getWikiPage()->getCount();
		$helpfulness = !Misc::isIntl() ? SocialProofStats::getPageRatingData($articleId)->rating : 0;
		$numReviews = self::getEligibleNumCuratedReviews($articleId);

		//second paragraph
		if ($numReviews < self::ICON_MIN_REVIEWS) {
			if ($helpfulness < self::ICON_MIN_HELPFULNESS)
				$msg = 'ur_hover_text_unhelpful_few_stories';
			else
				$msg = 'ur_hover_text_helpful_few_stories';
		}
		else {
			if ($helpfulness < self::ICON_MIN_HELPFULNESS)
				$msg = 'ur_hover_text_unhelpful_lotta_stories';
			else
				$msg = 'ur_hover_text_helpful_lotta_stories';
		}

		$views = number_format($views);
		$numReviews = number_format($numReviews);
		$text = wfMessage($msg, $views, $helpfulness, $numReviews)->parse();

		return $text;
	}

	/**
	 * No longer connected to expert stuff. Just based on complicated helpfulnes and # of reviews that
	 * currently exist algorithm
	 */
	public static function eligibleForByline(WikiPage $page): bool {
		return self::isArticleEligibleForReviews($page->getId())
			&& self::hasEnoughReviewsForIcon($page->getId());
	}

	public static function setBylineInfo(&$verifiers, $pageId) {
		$page = WikiPage::newFromID($pageId);
		if (!$page)  return true;

		if (self::eligibleForByline($page)) {
			$verifiers[SocialProofStats::VERIFIER_TYPE_READER] = true;
		}

		return true;
	}

	/*****
	 * Get all curated reviews for the given article.
	 * Reviews are return in an array, longest to shortest
	 ****/
	public static function getCuratedReviews($articleId) {
		global $wgMemc;

		$key = wfMemcKey(self::REVIEWS_KEY, $articleId);
		$value = $wgMemc->get($key);

		if (is_array($value)) {
			return $value;
		}

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(self::TABLE_CURATED, array('*'), array('uc_article_id' => $articleId, 'uc_eligible > 0'), __METHOD__, array("ORDER BY" => "uc_timestamp DESC"));

		$ids = [];
		if ($dbr->numRows($res) >= 0) {
			$reviews = array('reviews' => array());
			while ($row = $dbr->fetchRow($res)) {
				if (!empty($row['uc_user_id'])) {
					$ids[] = $row['uc_user_id'];
					$user = User::newFromId($row['uc_user_id']);
					$row['realname'] = $user->getRealName();
					$row['username'] = $user->getName();
					$row['userpage'] = $user->getUserPage()->getLocalURL();
				}
				$reviews['reviews'][] = $row;
			}
			//now look for the one with profile photos
			$dc = new UserDisplayCache($ids);
			$display_data = $dc->getData();
			$topReviews = [];

			foreach ($reviews['reviews'] as $index => &$row) {

				if (array_key_exists($row['uc_user_id'], $display_data) && strlen($row['uc_review']) > 200) {
					$row['avatarUrl'] = wfGetPad($display_data[$row['uc_user_id']]['avatar_url']);
					$row['fullname'] = $display_data[$row['uc_user_id']]['display_name'];

					//add it to the top pile
					$topReviews[] = $row;
					//remove it from the old list
					unset($reviews['reviews'][$index]);
				}
			}

			if (count($topReviews) > 0) {
				//first sort by length
				usort($topReviews, "UserReview::compareReviewsByLength");
				$reviews['reviews'] = array_merge($topReviews, $reviews['reviews']);
			}
		} else {
			$reviews = array();
		}

		$wgMemc->set($key, $reviews);
		return $reviews;
	}

	public static function compareReviewsByLength($review1, $review2) {
		$review1Len = strlen($review1['uc_review']);
		$review2Len = strlen($review2['uc_review']);
		if ($review1Len == $review2Len) {
			return 0;
		}
		return ($review1Len > $review2Len) ? -1 : 1;
	}

	/****
	 * Determines if article has enough curated reviews to show the icon
	 * at the top of an article. Doesn't affect whether reviews are shown
	 * in the right rail.
	 */
	public static function hasEnoughReviewsForIcon($articleId) {
		$numReviews = self::getEligibleNumCuratedReviews($articleId);
		$helpfulness = self::getHelpfulnessScore($articleId);
		$inWhitelist = ArticleTagList::hasTag(self::WHITELIST, $articleId);

		return (
			($inWhitelist && $numReviews >= self::MIN_REVIEW_WHITELIST)
			|| ($helpfulness >= intval(wfMessage('userreview_helpful_threshold')->text()) && $numReviews >= self::MIN_REVIEW_HIGH_HELPFUL)
			|| ($helpfulness >= intval(wfMessage('userreview_helpful_fellow_threshold')->text()) && $numReviews >= self::MIN_REVIEWS)
			|| ($helpfulness >= intval(wfMessage('userreview_low_helpful_fellow_threshold')->text()) && $numReviews >= self::MIN_REVIEW_LOW_HELPFUL)
		);
	}

	public static function getEligibleNumCuratedReviews($articleId) {
		global $wgMemc;

		$key = wfMemcKey(self::NUMBER_REVIEWS_KEY, $articleId);
		$value = $wgMemc->get($key);

		if (!$value) {
			$dbr = wfGetDB(DB_REPLICA);
			$value = $dbr->selectField(self::TABLE_CURATED, 'count(*)', array('uc_article_id' => $articleId, 'uc_eligible > 0'), __METHOD__);
			$wgMemc->set($key, $value);
		}

		return $value;
	}

	public static function getTotalCuratedReviews($articleId) {
		global $wgMemc;

		$key = wfMemcKey(self::NUMBER_REVIEWS_ALL_KEY, $articleId);
		$value = $wgMemc->get($key);

		if (!$value) {
			$dbr = wfGetDB(DB_REPLICA);
			$value = $dbr->selectField(self::TABLE_CURATED, 'count(*)', array('uc_article_id' => $articleId), __METHOD__);
			$wgMemc->set($key, $value);
		}

		return $value;
	}

	public static function clearReviews($articleId) {
		global $wgMemc;

		$key = wfMemcKey(self::ELIGIBLE_KEY, $articleId);
		$wgMemc->delete($key);
		$key = wfMemcKey(self::NUMBER_REVIEWS_KEY, $articleId);
		$wgMemc->delete($key);
		$key = wfMemcKey(self::REVIEWS_KEY, $articleId);
		$wgMemc->delete($key);
		$key = wfMemcKey(self::NUMBER_REVIEWS_ALL_KEY, $articleId);
		$wgMemc->delete($key);
	}

	/**
	 * Articles in this list only display last initial
	 */
	private static function shouldTruncate($articleId) {
		$ret = ArticleTagList::hasTag('userreview_truncate', $articleId);
		return $ret;
	}

	public static function getSidebarReviews($articleId) {
		return self::getUserReviews($articleId);
	}

	//for embedding in the article page
	public function addUserReviewWidget() {
		global $wgTitle;
		$html = self::getUserReviews($wgTitle->getArticleId());

		if (pq('.relatedwikihows')->length) {
			pq('.relatedwikihows')->before($html);
		}
	}

	public function getMobileReviews($articleId) {
		$isMobile = true;
		return self::getUserReviews($articleId, $isMobile);
	}

	private static function getUserReviews($articleId, $isMobile = false) {
		global $wgOut, $wgUser;

		if ($articleId > 0) {
			$reviews = self::getCuratedReviews($articleId);
			$isAmp = $reviews['amp'] = GoogleAmp::isAmpMode( $wgOut );
			if ($reviews !== false && count($reviews['reviews']) > 0) {
				$newReviews = [];
				$oldReviews = [];
				$cutoffDate = wfTimestamp(TS_MW, strtotime("6 months ago"));
				foreach ($reviews['reviews'] as $index => $review ) {
					if (isset($review['avatarUrl']) && $review['avatarUrl'] != "") {
						$newReviews[] = $review;
					}
					elseif(isset($review['uc_timestamp']) && $review['uc_timestamp'] > $cutoffDate) {
						$newReviews[] = $review;
					} else {
						$oldReviews[] = $review;
					}
				}
				usort($oldReviews, "UserReview::compareReviewsByLength");
				$reviews['reviews'] = array_merge($newReviews, $oldReviews);
				//only want to include the max number
				if (count($reviews['reviews'] > self::MAX_REVIEWS)) {
					array_splice($reviews['reviews'], self::MAX_REVIEWS);
				}

				$user_ids = [];
				foreach ($reviews['reviews'] as $review) {
					$user_ids[] = $review['uc_user_id'];
				}

				$dc = new UserDisplayCache($user_ids);
				$display_data = $dc->getData();

				foreach ($reviews['reviews'] as $index => &$review) {
					$review['uc_review']  = self::formatReview($review['uc_review']);
					$review['uc_firstname'] = trim($review['uc_firstname']);
					$review['uc_lastname'] = trim($review['uc_lastname']);
					$firstName = $review['uc_firstname'];
					$lastName = $review['uc_lastname'];
					$firstInitial = $firstName ? $firstName[0] : '';
					$lastInitial = $lastName ? $lastName[0] : '';
					$review['initials'] = $firstInitial . $lastInitial;
					$review['index'] = $index;
					$review['date'] = self::getFormattedDate($review['uc_timestamp']);
					if (self::shouldTruncate($articleId)) {
						$review['uc_lastname'] = $lastInitial.".";
					}
					$userId = $review['uc_user_id'];
					if (array_key_exists($userId, $display_data)) {
						$review['avatarUrl'] = wfGetPad($display_data[$userId]['avatar_url']);
						$review['fullname'] = $display_data[$userId]['display_name'];
					}
					if (!empty($review['uc_user_id']) && $review['realname'] != "") {
						if ($review['initials'] == "") {
							$nameParts = explode(" ", $review['realname']);
							if (count($nameParts) > 1) {
								$review['initials'] = $nameParts[0][0] . $nameParts[count($nameParts)-1][0];
							} else {
								$review['initials'] = $nameParts[0][0];
							}
						}
					} elseif (!empty($review['uc_user_id']) &&  $review['username'] != ""){
						if ($review['initials'] == "") {
							$review['initials'] = $review['username'][0];
						}
					} else {
						$review['fullname'] = $review['uc_firstname'] . ($review['uc_lastname'] != '' ? ' ' . $review['uc_lastname'] : '');
					}
					if ($review['uc_rating'] > 0) {
						$review['hasrating'] = true;
						$review['ratings'] = [];
						for ($i = 1; $i <= 5; $i++) {
							if ($review['uc_rating'] >= $i) {
								$review['ratings'][$i-1]['ratingClass'] = 'ur_rating_on';
							} else {
								$review['ratings'][$i-1]['ratingClass'] = 'ur_rating_off';
							}
						}
					}
					if ($review['uc_image'] != "") {
						$width = 247;
						$height = 247;
						$row2 = new stdClass;
						$row2->uci_image_name = $review['uc_image'];
						$thumb = UserCompletedImages::getUCICacheData(null, UserCompletedImages::fileFromRow($row2), $width, $height);
						if ( $thumb ) {
							$review['imageUrl'] = wfGetPad($thumb['url']);
						}
						if ($isAmp) {
							$review['hideReview'] = true;
						}
					}
				}

				$reviews['navigation'] = count($reviews['reviews']) > 1;

				if (!$isMobile && in_array("staff", $wgUser->getGroups())) {
					$toolTitle = Title::newFromText("UserReviewTool", NS_SPECIAL);
					$title = Title::newFromId($articleId);
					$reviews['staffLink'] = Linker::link($toolTitle, "Manage stories (staff)", ["target" => "_blank", "class" => "ur_nav_staff"], ["article" => $title->getText()]);
				}

				$reviews['ur_share'] = wfMessage('ur_share')->text();
				$reviews['ur_more'] = wfMessage('ur_more')->text();
				$reviews['ur_even_more'] = wfMessage('ur_even_more')->text();
				$reviews['ur_hide'] = wfMessage('ur_hide')->text();
				$reviews['is_alt'] = Misc::isAltDomain();

				Mustache_Autoloader::register();
				$options =  array(
					'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
				 );
				$m = new Mustache_Engine($options);

				$tmpl = $isMobile ? 'userreview_mobile' : 'userreview_sidebar';
				$html = $m->render($tmpl, $reviews);
				return $html;
			}
		}
	}

	/**
	 * Formats the review in case it's too long to include
	 * a "show more" link.
	 */
	private static function formatReview($review) {
		$review = htmlspecialchars($review);
		if (strlen($review) > self::MAX_REVIEW_LENGTH) {
			$char = $review[self::MAX_REVIEW_LENGTH];
			if ($char != " ") {
				$breakPoint = strrpos($review, " ",  self::MAX_REVIEW_LENGTH - strlen($review));
			} else {
				$breakPoint = self::MAX_REVIEW_LENGTH;
			}
			$review = '"' . substr($review, 0, $breakPoint) .  "<span class='ur_review_more'>" . substr($review, $breakPoint) ."\"</span><span class='ur_ellipsis'>...\" </span><a href='#ur_anchor_full' class='ur_review_show'>more</a>";
		} else {
			$review = '"' . $review . '"';
		}

		return $review;
	}

	public static function getFormattedDate($timestamp) {
		$timestamp = wfTimestamp(TS_UNIX, $timestamp);

		$now = time();
		$periods = array(wfMessage("day-plural")->text(), wfMessage("week-plural")->text(), wfMessage("month-plural")->text(), wfMessage("year-plural")->text());
		$period = array(wfMessage("day")->text(), wfMessage("week")->text(), wfMessage("month-singular")->text(), wfMessage("year-singular")->text());

		$dt1 = new DateTime("@$timestamp");
		$dt2 = new DateTime("@$now");
		$dt3 = new DateTime($dt2->format("Y")."-01-01"); //January 1 of this year
		$interval = $dt1->diff($dt2);

		//show Jan 2 format for all dates over a week old and Jan 2, 2011 for all dates not in the current calendar year
		if ($interval->y > 0 || $interval->m > 0 || $interval->d > 7) {
			if ($dt1 >= $dt3) {
				return $dt1->format("M j"); //If during the current year, don't show the year
			} else {
				return $dt1->format("M j, Y"); //If from a previous calendar year, show the year
			}
		} elseif ($interval->d > 0) {
			if ($interval->d == 1) {
				return "yesterday";
			}else {
				return wfMessage("ago", $interval->d . ' ' . ($interval->d == 1 ? $period[0] : $periods[0]))->text();
			}
		} else {
			return wfMessage('today')->text();
		}
	}

	public static function updateEligibilityField($articleId) {
		$dbw = wfGetDB(DB_MASTER);

		$eligible = UserReview::setArticleEligibleForReviews($articleId);
		$dbw->update(UserReview::TABLE_SUBMITTED, array('us_eligible' => $eligible), array('us_article_id' => $articleId), __METHOD__);
		$dbw->update(UserReview::TABLE_CURATED, array('uc_eligible' => $eligible), array('uc_article_id' => $articleId), __METHOD__);
		UserReview::clearReviews($articleId);
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if ($skin->getTitle()->inNamespace(NS_MAIN)) {
			if (Misc::isMobileMode()) {
				$out->addModules('mobile.wikihow.userreview');
			}
			else {
				$out->addModules('ext.wikihow.userreview');
			}
		}

		return true;
	}

	public static function handlePicturePatrol($imageName, $isGood) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select(self::TABLE_SUBMITTED, ['us_id', 'us_status', 'us_article_id'], ['us_image' => $imageName], __METHOD__);
		if ($dbw->numRows($res) > 0) {
			$row = $dbw->fetchRow($res);
			$status = intval($row['us_status']);
			$articleId = $row['us_article_id'];

			if ( $isGood ) {
				if ( $status === UserReviewTool::STATUS_UCI_WAITING ) {
					$dbw->update(
						self::TABLE_SUBMITTED,
						['us_status' => UserReviewTool::STATUS_AVAILABLE],
						['us_image' => $imageName],
						__METHOD__
					);
				}
			} else {
				$values = ['us_status' => UserReviewTool::STATUS_DELETED];
				$dbw->update(
					self::TABLE_SUBMITTED,
					$values,
					['us_id' => $row['us_id']],
					__METHOD__
				);
				$dbw->delete(
					self::TABLE_CURATED,
					['uc_submitted_id' => $row['us_id']],
					__METHOD__
				);
				self::clearReviews($articleId);
			}
		}

		return true;
	}

	public static function handSensitiveArticleEdit($articleId, $reasonIds) {
		if (in_array(self::SENSITIVE_ID, $reasonIds)) {
			self::updateEligibilityField($articleId);
		}
		return true;
	}

	public static function getCuratedReviewsBySubmittedIds($submittedIds) {
		if (!is_array($submittedIds)) {
			$submittedIds = [$submittedIds];
		}

		$dbr = wfGetDB(DB_REPLICA);
		$ids = $dbr->makeList($submittedIds);
		$res = $dbr->select(
			self::TABLE_CURATED,
			'*',
			['uc_submitted_id IN (' . $ids . ')']
			,
			__METHOD__,
			['ORDER BY' => 'FIELD(uc_submitted_id,' . $ids . ')']
		);

		$results = [];
		while ($row = $dbr->fetchRow($res)) {
			$results[] = $row;
		}

		return $results;
	}

	public static function purge( $articleId ) {
		global $wgMemc;

		$keys = [
			wfMemcKey( self::ELIGIBLE_KEY, $articleId ),
			wfMemcKey( self::HELPFULNESS_KEY, $articleId ),
			wfMemcKey( self::NUMBER_REVIEWS_ALL_KEY, $articleId ),
			wfMemcKey( self::NUMBER_REVIEWS_KEY, $articleId ),
			wfMemcKey( self::REVIEWS_KEY, $articleId )
		];
		foreach ( $keys as $key ) {
			$wgMemc->delete( $key );
		}
	}
}
