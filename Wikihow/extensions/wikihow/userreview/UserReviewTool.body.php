<?php

class UserReviewTool extends UnlistedSpecialPage {

	const STATUS_AVAILABLE = 0;
	const STATUS_SKIPPED = 1;
	const STATUS_CURATED = 2;
	const STATUS_DELETED = 3;
	const STATUS_UCI_WAITING = 4;

	public function __construct() {
		parent::__construct('UserReviewTool');
	}

	public function execute($par) {
		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getuser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !(in_array('staff', $userGroups) || in_array('user_review', $userGroups))) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$out->addModules('ext.wikihow.userreviewtool');
		$out->setPageTitle("User Review Curator");

		if ($request->wasPosted()) {

			$action = $request->getVal("a");
			$articleName = $request->getVal("article");
			$aid = null;
			if ($articleName != null) {
				$title = Title::newFromText($articleName);
				if ($title && $title->exists()) {
					$aid = $title->getArticleID();
				} else {
					//check if the capitalization is wrong
					$title = Misc::getCaseRedirect($title);
					if ($title && $title->exists()) {
						$aid = $title->getArticleID();
					} else {
						$out->setArticleBodyOnly(true);
						echo json_encode(['html' => "That article (" . $articleName . ") does not exist"]);
						return;
					}
				}
			}

			if ( $action == "getNext" ) {
				$out->setArticleBodyOnly(true);
				$this->getNext($aid);
			} elseif ( $action == "skip" ) {
				$out->setArticleBodyOnly(true);
				$this->skip();
				$this->getNext();
			} elseif ( $action == "clearskip" ) {
				$out->setArticleBodyOnly(true);
				$this->resetSkips();
			} elseif ( $action == "delete" ) {
				$out->setArticleBodyOnly(true);
				$this->delete();
			} elseif ( $action == "approve" ) {
				$out->setArticleBodyOnly(true);
				$this->approve();
			} elseif ( $action == "save" ) {
				$out->setArticleBodyOnly(true);
				$this->save();
			}
		}

	}

	private function approve() {
		$request = $this->getRequest();
		$user = $this->getUser();

		$id = $request->getVal("id");
		$firstname = $request->getVal("firstname", "");
		$lastname = $request->getVal("lastname", "");
		$review = $request->getVal("review");
		$articleId = $request->getVal("aid");
		$isEligible = $request->getVal("eligible", 0);
		$timestamp = $request->getVal("timestamp");
		$rating = $request->getVal("rating");
		$userId = $request->getVal("userid");
		$image = $request->getVal("image");

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(UserReview::TABLE_SUBMITTED,
			array('us_status' => self::STATUS_CURATED, 'us_curated_user' => $user->getId(), 'us_curated_timestamp' => wfTimestampNow()),
			array('us_id' => $id), __METHOD__);
		$dbw->insert(UserReview::TABLE_CURATED,
			array(
				'uc_submitted_id' => $id,
				'uc_review' => $review,
				'uc_firstname' => $firstname,
				'uc_lastname' => $lastname,
				'uc_article_id' => $articleId,
				'uc_eligible' => $isEligible,
				'uc_timestamp' => $timestamp,
				'uc_rating' => $rating,
				'uc_user_id' => $userId,
				'uc_image' => $image
			),
			__METHOD__
		);
		UserReview::clearReviews($articleId);
		self::releaseArticle($articleId);
	}

	private function save() {
		$request = $this->getRequest();
		$user = $this->getUser();

		$id = $request->getVal("id");
		$firstname = $request->getVal("firstname");
		$lastname = $request->getVal("lastname");
		$review = $request->getVal("review");
		$articleId = $request->getVal("aid");

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(UserReview::TABLE_CURATED,
			array('uc_review' => $review, 'uc_firstname' => $firstname, 'uc_lastname' => $lastname),
			array('uc_submitted_id' => $id), __METHOD__);
		$dbw->update(UserReview::TABLE_SUBMITTED,
			array('us_curated_user' => $user->getId(), 'us_curated_timestamp' => wfTimestampNow()),
			array('us_id' => $id), __METHOD__);
		UserReview::clearReviews($articleId);
		self::releaseArticle($articleId);
	}

	private function delete() {
		$user = $this->getUser();
		$request = $this->getRequest();
		$id = $request->getVal("id");
		$articleId = $request->getVal("aid");

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(UserReview::TABLE_SUBMITTED, array('us_status' => self::STATUS_DELETED, 'us_curated_user' => $user->getId(), 'us_curated_timestamp' => wfTimestampNow()), array('us_id' => $id), __METHOD__);
		$dbw->delete(UserReview::TABLE_CURATED, array('uc_submitted_id' => $id), __METHOD__);
		UserReview::clearReviews($articleId);
		self::releaseArticle($articleId);
	}

	private function skip() {
		$request = $this->getRequest();
		$ids = $request->getValues("ids");
		$articleId = $request->getVal("aid");

		if ($ids != null) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(UserReview::TABLE_SUBMITTED, array('us_status' => self::STATUS_SKIPPED), array('us_id IN (' . $dbw->makeList($ids['ids']) . ')'));
		}
		self::releaseArticle($articleId);
	}

	private function getNext($aid = null) {
		$isStaff = in_array("staff", $this->getUser()->getGroups());
		list($curated, $uncurated) = $this->getReviewData($aid, $isStaff);

		if ($curated['reviewCount'] == 0 && $uncurated['reviewCount'] == 0) {
			echo json_encode(['success' => false]);
		} else {
			echo json_encode(['html' => $this->getHtml($curated, $uncurated), 'success' => true, 'count' => $this->getPositiveUncuratedCount()]);
		}
	}

	private function getUncuratedCount() {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectField(UserReview::TABLE_SUBMITTED, "count('us_id') as count", array("us_status IN (" . self::STATUS_AVAILABLE . ", " . self::STATUS_SKIPPED . ")" ), __METHOD__ );
	}

	private function getPositiveUncuratedCount() {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectField(UserReview::TABLE_SUBMITTED, "count('us_id') as count", array("us_status IN (" . self::STATUS_AVAILABLE . ", " . self::STATUS_SKIPPED . ")", "us_positive" => 1 ), __METHOD__ );
	}

	private function getHtml($curated, $uncurated) {
		$html = "";

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		$html .= $m->render('userreview_submitted', $uncurated);
		$html .= $m->render('userreview_curated', $curated);

		return $html;
	}

	private function getReviewData($aid = null, $isStaff = false) {
		$dbr = wfGetDB(DB_REPLICA);

		$review = $this->getNextSingleReview($aid);
		//now get all the others for that article, but only the "positive" ones
		$res = $dbr->select(
			[UserReview::TABLE_SUBMITTED, UserReview::TABLE_CURATED, UserCompletedImages::UCI_TABLE],
			['*'],
			['us_article_id' => $review['us_article_id'], 'us_positive' => 1],
			__METHOD__,
			[],
			[UserReview::TABLE_CURATED => ['LEFT JOIN', 'us_id = uc_submitted_id'], UserCompletedImages::UCI_TABLE => ['LEFT JOIN', 'us_image = uci_image_name']]
		);

		$uncurated = array('reviews' => array());
		$curated = array('reviews' => array());

		$id = null;
		$isEligible = 0;
		while ($row = $dbr->fetchRow($res)) {
			if ($row['us_status'] == self::STATUS_UCI_WAITING) {
				//it has a UCI image that hasn't been patrolled yet
				continue;
			}
			$id = $row['us_article_id'];
			$isEligible = max($isEligible, $row['us_eligible']);

			if ($row['us_image'] != "") {
				$width = UserCompletedImages::THUMB_WIDTH;
				$height =UserCompletedImages::THUMB_HEIGHT;
				$row2 = new stdClass;
				$row2->uci_image_name = $row['us_image'];
				$thumb = UserCompletedImages::getUCICacheData(null, UserCompletedImages::fileFromRow($row2), $width, $height);
				if ($thumb) {
					$row['imageUrl'] = wfGetPad($thumb['url']);
				}
			}
			if ($row['uc_submitted_id'] != null) {
				if ($row['uc_user_id'] > 0) {
					$user = User::newFromId($row['uc_user_id']);
					$row['username'] = $user->getName();
				}
			} else {
				if ($row['us_user_id'] > 0) {
					$user = User::newFromId($row['us_user_id']);
					$row['username'] = $user->getName();
				}
			}
			if ($row['us_status'] == self::STATUS_CURATED) {
				if ($isStaff) {
					$row['editDate'] = date("n/j/Y", wfTimestamp(TS_UNIX, $row['us_curated_timestamp']));
					$row['editUser'] = User::newFromId($row['us_curated_user'])->getName();
				}
				$curated['reviews'][] = $row;
			} elseif ($row['us_status'] == self::STATUS_AVAILABLE || $row['us_status'] == self::STATUS_SKIPPED) {
				$uncurated['reviews'][] = $row;
			}
		}

		$uncurated['reviewCount'] = count($uncurated['reviews']);
		if ($id != null) {
			$title = Title::newFromID($id);
			if ($title) {
				$uncurated['articleText'] = $title->getText();
				$uncurated['articleUrl'] = $title->getFullURL();
			} else {
				$uncurated['articleText'] = "Article no longer exists";
				$uncurated['articleUrl']  = "#";
			}
		}
		$curated['reviewCount'] = count($curated['reviews']);
		if ($isStaff) {
			$curated['staff'] = true;
		}

		self::checkoutArticle($id);

		if ($isEligible) {
			$uncurated['eligible'] = $isEligible;
		}

		return array($curated, $uncurated);
	}

	private function getNextSingleReview($aid = null) {
		$dbr = wfGetDB(DB_REPLICA);

		$expired = wfTimestamp(TS_MW, time() - 2*24*60*60); //expires in 2 days
		$whereDefault = array('us_status' => self::STATUS_AVAILABLE, 'us_positive' => 1, "us_checkout < $expired"); //available and positive
		if ($aid != null) {
			$where1 = array('us_article_id' => $aid);
		} else {
			$where1 = $whereDefault;
		}

		$tries = 0;
		while ($tries < 2) {
			$res = $dbr->select(UserReview::TABLE_SUBMITTED, array('*', 'count(us_article_id) as count'), ($tries==0?$where1:$whereDefault), __METHOD__, array("GROUP BY" => "us_article_id", "ORDER BY" => "us_positive DESC, us_eligible DESC, count desc", "LIMIT" => "1"));
			$row = $res->fetchRow();
			if ($row === false) {
				//nothing left, undo all the skipped
				self::resetSkips();
			} else {
				return $row;
			}
			$tries++;
		}

	}

	private function resetSkips() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(UserReview::TABLE_SUBMITTED, array('us_status' => self::STATUS_AVAILABLE), array('us_status' => self::STATUS_SKIPPED), __METHOD__);
	}

	private function checkoutArticle($articleId) {
		if ($articleId != "") {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(UserReview::TABLE_SUBMITTED, array('us_checkout' => wfTimestampNow()), array('us_article_id' => $articleId), __METHOD__);
		}
	}

	private function releaseArticle($articleId) {
		if ($articleId) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(UserReview::TABLE_SUBMITTED, array('us_checkout' => ''), array('us_article_id' => $articleId), __METHOD__);
		}
	}
}
