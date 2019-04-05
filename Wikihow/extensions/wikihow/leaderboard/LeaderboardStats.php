<?php
// A list of static methods that are called from the Special:Leaderboard class.
//
// TODO: this whole class could be cleaned up to be use classes properly,
//   something akin to QueryPage

class LeaderboardStats {

	/**
	 * Query for Articles Written
	 **/
	public static function getArticlesWritten($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:articles_written:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:articles_written:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		// DB query new articles
		// Using query from SpecialNewPages per Jack's request
		list( $recentchanges, $page ) = $dbr->tableNamesN( 'recentchanges', 'page' );

		$conds = array();
		$conds['rc_new'] = 1;
		$conds['rc_namespace'] = 0;
		$conds['page_is_redirect'] = 0;
		if ($getArticles) {
			$conds['rc_user_text'] = $lb_user;
		}
		$condstext = $dbr->makeList( $conds, LIST_AND );

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND rc_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql =
			"SELECT 'Newpages' as type,
				rc_title AS title,
				rc_cur_id AS cur_id,
				rc_user AS \"user\",
				rc_user_text AS user_text
			FROM $recentchanges,$page
			WHERE rc_cur_id=page_id AND rc_timestamp >= '".$starttimestamp."' AND $condstext" . $bot;
		$res = $dbr->query($sql, __METHOD__);

		// Setup array for new articles
		foreach ($res as $row) {
			$t = Title::newFromID( $row->cur_id );
			if (isset($t)) {
				if ($t->getArticleID() > 0) {
					if ($getArticles) {
						$data[$t->getPartialURL()] = $t->getPrefixedText();
					} else {
						if (!preg_match('/\d+\.\d+\.\d+\.\d+/',$row->user_text)) {
							if (!isset($data[$row->user_text])) {
								$data[$row->user_text] = 1;
							} else {
								$data[$row->user_text]++;
							}
						}
					}
				}
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Thunbs Up
	 **/
	public static function getThumbsUp($starttimestamp) {
		global $wgMemc;

		$key = "leaderboard:thumbsup_received:$starttimestamp";
		$cachekey = wfMemcKey($key);
		$cache = $wgMemc->get($cachekey);
		if (is_array($cache)) {
			return $cache;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "
			SELECT
				count(thumb_recipient_id) as cnt,
				thumb_recipient_id
			FROM
				thumbs
			WHERE
				thumb_timestamp > '$starttimestamp' AND
				thumb_recipient_id != 0
			GROUP BY
				thumb_recipient_id
			ORDER BY
				cnt DESC
			LIMIT 30";

		$res = $dbr->query($sql, __METHOD__);

		$data = array();
		foreach ($res as $row) {
			$u = User::newFromId($row->thumb_recipient_id);
			$data[$u->getName()] = number_format($row->cnt, 0, "", ',');
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for RisingStars Written
	 **/
	public static function getRisingStar($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:risingstars_received:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:risingstars_received:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND rc_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT distinct(rc_title) ".
				"FROM recentchanges  ".
				"WHERE rc_timestamp >= '$starttimestamp' AND rc_comment like 'Marking new article as a Rising Star from From%'   ". $bot .
				"AND rc_namespace=".NS_TALK." ";
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$t = Title::newFromText($row->rc_title);
			$a = new Article($t);
			if ($a->isRedirect()) {
				$wp = new WikiPage($t);
				$t = $wp->getRedirectTarget();
				$a = new Article($t);
			}
			$author = $a->getContributors()->current();
			$username = $author ? $author->getName() : '';
			if ($getArticles) {
				if ($lb_user == $username)
					$data[$t->getPartialURL()] = $t->getPrefixedText();
			} else {
				$data[$username]++;
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	public function getWelcomeWagon($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:welcomewagon:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:welcomewagon:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);

		if (is_array($val)) {
		   return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT ww_revision_id ".
					"FROM welcome_wagon_messages ".
					"WHERE ww_from_user_id = ".$u->getID()."  and ww_timestamp >= '$starttimestamp' ".
					"ORDER BY ww_timestamp DESC ".
					"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$r = Revision::newFromId($row->ww_revision_id);

				if ($r) {
					$t = $r->getTitle();
					if ($r->getPrevious()) {
						$data["User_talk:".$t->getPartialUrl()."#".$r->getId()] = str_replace($r->getPrevious()->getText(), '', ContentHandler::getContentText( $r->getContent() ));
					}
				}
			}

		} else {

			$sql = "SELECT user_name, count(*) as C
					FROM welcome_wagon_messages left join $wgSharedDB.user on user_id = ww_from_user_id
					WHERE ww_timestamp >= '$starttimestamp'
					GROUP BY user_name ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if (isset($u)) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}

		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for RisingStars Boosted
	 **/
	public static function getRisingStarsNABed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:risingstars_nabed:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:risingstars_nabed:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}
		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND rc_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT rc_title,rc_user_text ".
				"FROM recentchanges  ".
				"WHERE rc_timestamp >= '$starttimestamp' AND rc_comment like 'Marking new article as a Rising Star from From%'   ". $bot .
				"AND rc_namespace=".NS_TALK." AND rc_user_text != 'WRM' ";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			$t = Title::newFromText($row->rc_title);
			if ($getArticles) {
				if ($lb_user == $row->rc_user_text)
					$data[$t->getPartialURL()] = $t->getPrefixedText();
			} else {
				$data[$row->rc_user_text]++;
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Requested Topics
	 **/
	public static function getRequestedTopics($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:requested_topics:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:requested_topics:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$starttimestamp = $dbr->strencode($starttimestamp);
		if ($getArticles) {
			$sql = "SELECT page_title, fe_user_text ".
					"FROM firstedit left join page on fe_page = page_id left join suggested_titles on page_title= st_title " .
					"WHERE fe_timestamp >= '$starttimestamp' AND fe_user_text = " . $dbr->addQuotes($lb_user) . " AND st_isrequest IS NOT NULL";
		} else {
			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND fe_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT page_title, fe_user_text ".
					"FROM firstedit left join page on fe_page = page_id left join suggested_titles on page_title= st_title " .
					"WHERE fe_timestamp >= '$starttimestamp' AND st_isrequest IS NOT NULL" . $bot;
		}

		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($getArticles) {
				$t = Title::newFromText($row->page_title);
				if (isset($t))
					$data[$t->getPartialURL()] = $t->getPrefixedText();
			} else {
				if (!preg_match('/\d+\.\d+\.\d+\.\d+/',$row->fe_user_text))
					$data[$row->fe_user_text]++;
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Unit Guardian leadboard
	 **/
	public static function getUnitGuardianed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:unitguardian:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:unitguardian:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='unitguardian' AND log_action != 'maybe' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='unitguardian' and log_action != 'maybe' and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	public static function getCategoryguarded($starttimestamp) {
		global $wgMemc, $wgSharedDB;

		$key = "leaderboard:categoryguarded:$starttimestamp";
		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);

		if (is_array($val)) {
			return $val;
		}

		$logKey = CategoryGuardian::LOG_TYPE;
		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$starttimestamp = $dbr->strencode($starttimestamp);
		$result = $dbr->query(
			"SELECT log_user, COUNT(*) as C FROM logging
			WHERE log_type = '$logKey' AND log_timestamp >= '$starttimestamp'
			GROUP BY log_user ORDER BY C desc limit 30",
			__METHOD__
		);

		foreach ($result as $row) {
			$user = User::newFromId($row->log_user);
			if ($user && $user->getId() > 0) {
				$data[$user->getName()] = $row->C;
			}
		}

		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	public static function getQuestionsSorted($starttimestamp) {
		global $wgMemc, $wgSharedDB;

		$key = "leaderboard:questionssorted:$starttimestamp";
		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);

		if (is_array($val)) {
			return $val;
		}

		$logKey = 'sort_questions_tool';
		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		$result = $dbr->query(
			"SELECT log_user, COUNT(*) as C FROM logging
			WHERE log_type = '$logKey' AND log_timestamp >= '$starttimestamp'
			GROUP BY log_user ORDER BY C desc limit 30",
			__METHOD__
		);

		foreach ($result as $row) {
			$user = User::newFromId($row->log_user);
			if ($user && $user->getId() > 0) {
				$data[$user->getName()] = $row->C;
			}
		}

		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for Articles TechFeedback Reviewed
	 **/
	public static function getTechFeedbackReviewed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:techfeedbackreviewed:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:techfeedbackreviewed:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='tech_update_tool' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='tech_update_tool' and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for ArticleFeedback Reviewed
	 **/
	public static function getArticleFeedbackReviewed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:articlefeedbackreviewed:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:articlefeedbackreviewed:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='article_feedback_tool' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='article_feedback_tool' and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for Articles TechTested
	 **/
	public static function getTechArticleTested($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:techarticletested:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:techarticletested:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='test_tech_articles' AND log_action <> 'skip' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='test_tech_articles' and log_action <> 'skip' and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for Duplicate Titles Reviewed
	 **/
	public static function getDuplicateTitlesReviewed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:duplicatetitlesreviewed:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:duplicatetitlesreviewed:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='duplicatetitles' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='duplicatetitles' and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for Flagged Answers fixed
	 **/
	public static function getFixFlaggedAnswers($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:fixflaggedanswers:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:fixflaggedanswers:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='fix_flagged_answers' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='fix_flagged_answers' and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for Flagged Answers fixed
	 **/
	public static function getQAPatrollers($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:qap:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:qap:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			// return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='qa_patrol' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$where = [
				'log_type' => 'qa_patrol',
				"log_timestamp >= '$starttimestamp'"
			];

			$bots = WikihowUser::getBotIDs();
			if (!empty($bots)) {
				$where[] = 'log_user NOT IN ('.$dbr->makeList($bots).')';
			}

			$qa_editor_ids = WikihowUser::getUserIDsByUserGroup('qa_editors');
			if (!empty($qa_editor_ids)) {
				$where[] = 'log_user IN ('.$dbr->makeList($qa_editor_ids).')';
			}

			$res = $dbr->select(
				[
					'logging',
					"$wgSharedDB.user"
				],
				[
					'log_user',
					'count(*) as C',
					'user_name'
				],
				$where,
				__METHOD__,
				[
					'GROUP BY' => 'log_user',
					'ORDER BY' => 'C desc',
					'LIMIT' => 30
				],
				["$wgSharedDB.user" => ['LEFT JOIN', 'user_id = log_user']]
			);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for Articles Spellchecked
	 **/
	public static function getSpellchecked($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:spellchecked:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:spellchecked:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='spellcheck' AND log_user = ".$u->getID()."  and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='spellcheck' and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for Articles NABed
	 **/
	public static function getArticlesNABed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:articles_nabed:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:articles_nabed:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT nap_page ".
				"FROM newarticlepatrol ".
				"WHERE nap_patrolled=1 and nap_user_ci = ".$u->getID()."  and nap_timestamp_ci >= '$starttimestamp' ".
				"ORDER BY nap_timestamp_ci DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromID($row->nap_page);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {
			$bots = WikihowUser::getBotIDs();
			$bot = "";

		if (count($bots) > 0) {
			$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$sql = "SELECT log_user, count(*) as C , user_name
			FROM logging left join $wgSharedDB.user on user_id=log_user
			WHERE log_type='nap' and log_timestamp >= '$starttimestamp' " . $bot .
			"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if (isset($u)) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

	/**
	 * Query for RC Edits
	 **/
	public static function getRCEdits($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:rc_edits:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:rc_edits:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$sql = '';
		$bots = WikihowUser::getBotIDs();
		$bot = count($bots) == 0
			? ""
			: " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
		$starttimestamp = $dbr->strencode($starttimestamp);
		if ($getArticles) {
			$sql = "SELECT log_user, log_title ".
				"FROM logging FORCE INDEX (times) ".
				"WHERE log_type='patrol' and log_namespace = 0 " .
				"AND log_timestamp >= '$starttimestamp' " . $bot;
		} else {
			$sql = "SELECT log_user, count(*) as C ".
				"FROM logging FORCE INDEX (times) ".
				"WHERE log_type='patrol' and log_timestamp >= '$starttimestamp' ". $bot .
				"GROUP BY log_user ORDER BY C DESC LIMIT 30;";
		}

		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($row->log_user > 0) {
				$u = User::newFromID( $row->log_user );
				if ($getArticles) {
					if ( $lb_user == $u->getName() ) {
						$t = Title::newFromText($row->log_title);
						if (isset($t))
							$data[$t->getPartialURL()] = $t->getPrefixedText();
					}
				} else {
					if (!preg_match('/\d+\.\d+\.\d+\.\d+/',$u->getName()))
						$data[$u->getName()] = number_format($row->C, 0, "", ',');
				}
			}
		}
		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for QC patrolling
	 **/
	public static function getQCPatrols($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:qc_patrol:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:qc_patrol:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND qcv_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT user_name, SUM(C) as C FROM
			( (SELECT user_name, count(*) as C from qc_vote left join $wgSharedDB.user on qcv_user=user_id
				WHERE qc_timestamp > '{$starttimestamp}' $bot group by qcv_user order by C desc limit 25)
			UNION
			(SELECT user_name, count(*) as C from qc_vote_archive left join $wgSharedDB.user on qcv_user=user_id
				WHERE qc_timestamp > '{$starttimestamp}' $bot group by qcv_user order by C desc limit 25) ) t1
			group by user_name  order by C desc limit 25";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			$data[$row->user_name] = $row->C;
		}
		$wgMemc->set($cachekey, $data, 300);
		return $data;
	}

	/**
	 * Query for RC Quick Edits
	 **/
	public static function getRCQuickEdits($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:rc_quick_edits:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:rc_quick_edits:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND rc_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT rc_user_text,rc_title ".
			"FROM recentchanges ".
			"WHERE rc_comment like 'Quick edit while patrolling' and rc_timestamp >= '$starttimestamp'". $bot .
			"GROUP BY rc_user_text,rc_title ";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($getArticles) {
				$t = Title::newFromText($row->rc_title);
				if ($row->rc_user_text == $lb_user) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			} else {
				$data[$row->rc_user_text]++;
			}

		}


		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}


	/**
	 * Query for Total Edits
	 **/
	public static function getTotalEdits($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:total_edits:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:total_edits:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();

		$bot = "";

		if (count($bots) > 0) {
			$bot = "AND rev_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT rev_user_text,page_title,page_namespace ".
			"FROM revision,page ".
			"WHERE rev_page=page_id and page_namespace NOT IN (2, 3, 18) and rev_timestamp >= '$starttimestamp' AND rev_user_text != 'WRM' ".
			$bot .
			"ORDER BY rev_timestamp desc";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($getArticles) {
				$t = Title::newFromText($row->page_title);
				if ($row->rev_user_text == $lb_user) {
					if ($row->page_namespace == NS_IMAGE) {
						$data['Image:' . $t->getPartialURL()] = $t->getPrefixedText();
					} else {
						$data[$t->getPartialURL()] = $t->getPrefixedText();
					}
				}
			} else {
				$data[$row->rev_user_text]++;
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for UCIPatrol aka Picture Patrol
	 **/
	public static function getUCIAdded($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:uci_tool2:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:uci_tool2:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();

		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT log_user, count(*) as C ".
			"FROM logging ".
			"WHERE log_type='ucipatrol' and log_timestamp >= '$starttimestamp' ". $bot.
			"GROUP BY log_user ORDER BY C DESC LIMIT 30";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			$u = User::newFromId($row->log_user);
			if ($u) {
				$data[$u->getName()] = number_format($row->C, 0, "", ',');
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for TipsPatrol
	 **/
	public static function getTipsAdded($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:tip_tool2:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:tip_tool2:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();

		$bot = "";
		if (count($bots) > 0) {
			$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT log_user, count(*) as C ".
			"FROM logging ".
			"WHERE log_type='newtips' and log_timestamp >= '$starttimestamp' ". $bot.
			"GROUP BY log_user ORDER BY C DESC LIMIT 30";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			$u = User::newFromId($row->log_user);
			if ($u) {
				$data[$u->getName()] = number_format($row->C, 0, "", ',');
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Articles Categorized
	 **/
	public static function getArticlesCategorized($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:articles_categorized:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:articles_categorized:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();

		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND rc_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT rc_user_text,rc_title ".
			"FROM recentchanges ".
			"WHERE rc_comment like 'categorization' and rc_timestamp >= '$starttimestamp' ". $bot .
			"GROUP BY rc_user_text,rc_title" ;
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($getArticles) {
				$t = Title::newFromText($row->rc_title);
				if ($row->rc_user_text == $lb_user) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			} else {
				$data[$row->rc_user_text]++;
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Images Added
	 **/
	public static function getImagesAdded($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:images_added:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:images_added:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND img_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT img_user_text,img_name FROM image ".
			"WHERE img_timestamp >= '$starttimestamp'" . $bot;

		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($getArticles) {
				if ($row->img_user_text == $lb_user) {
					$data['Image:'.$row->img_name] = $row->img_name;
				}
			} else {
				$data[$row->img_user_text]++;
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Articles Rated
	 **/
	public static function getArticlesRated($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:articles_added:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:articles_added:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		//log_type can only be 10 chars. Truncate appropriately
		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT log_user, count(*) as C ".
			"FROM logging ".
			"WHERE log_type='ratetool' and log_timestamp >= '$starttimestamp' ". $bot.
			"GROUP BY log_user ORDER BY C DESC LIMIT 30";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($row->log_user > 0) {
				$u = User::newFromID( $row->log_user );
				if ($getArticles) {
					if ( $lb_user == $u->getName() ) {
						$t = Title::newFromText($row->log_title);
						if (isset($t))
							$data[$t->getPartialURL()] = $t->getPrefixedText();
					}
				} else {
					if (!preg_match('/\d+\.\d+\.\d+\.\d+/',$u->getName()))
						//$data[$u->getName()]++;
						$data[$u->getName()] = number_format($row->C, 0, "", ',');
				}
			}
		}
		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Articles Repaired
	 **/
	public static function getArticlesRepaired($starttimestamp, $templatetype, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:repair_$templatetype:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:repair_$templatetype:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		//log_type can only be 10 chars. Truncate appropriately
		$starttimestamp = $dbr->strencode($starttimestamp);
		$logType = $dbr->strencode('EF_' . substr($templatetype, 0, 7));
		$sql = "SELECT log_user, count(*) as C ".
			"FROM logging ".
			"WHERE log_type='$logType' and log_timestamp >= '$starttimestamp' ". $bot.
			"GROUP BY log_user ORDER BY C DESC LIMIT 30";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($row->log_user > 0) {
				$u = User::newFromID( $row->log_user );
				if ($getArticles) {
					if ( $lb_user == $u->getName() ) {
						$t = Title::newFromText($row->log_title);
						if (isset($t))
							$data[$t->getPartialURL()] = $t->getPrefixedText();
					}
				} else {
					if (!preg_match('/\d+\.\d+\.\d+\.\d+/',$u->getName()))
						//$data[$u->getName()]++;
						$data[$u->getName()] = number_format($row->C, 0, "", ',');
				}
			}
		}
		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for NFDs Reviewed
	 **/
	public static function getNfdsReviewed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:nfd:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:nfd:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bots = WikihowUser::getBotIDs();
		$bot = "";

		if (count($bots) > 0) {
			$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT log_user, count(*) as C ".
			"FROM logging ".
			"WHERE log_type='nfd' and log_action='vote' and log_timestamp >= '$starttimestamp' ". $bot.
			"GROUP BY log_user ORDER BY C DESC LIMIT 30";
		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($row->log_user > 0) {
				$u = User::newFromID( $row->log_user );
				if ($getArticles) {
					if ( $lb_user == $u->getName() ) {
						$t = Title::newFromText($row->log_title);
						if (isset($t))
							$data[$t->getPartialURL()] = $t->getPrefixedText();
					}
				} else {
					if (!preg_match('/\d+\.\d+\.\d+\.\d+/',$u->getName()))
						//$data[$u->getName()]++;
						$data[$u->getName()] = number_format($row->C, 0, "", ',');
				}
			}
		}
		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for Videos Reviewed
	 **/
	public static function getVideosReviewed($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc;

		if ($getArticles) {
			$key = "leaderboard:videos_reviewed:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:videos_reviewed:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();

		$bot = "";
		$bots = WikihowUser::getBotIDs();

		if (count($bots) > 0) {
			$bot = " AND va_user NOT IN (" . $dbr->makeList($bots) . ") ";
		}

		$starttimestamp = $dbr->strencode($starttimestamp);
		$sql = "SELECT va_user, va_user_text, count(*) as C ".
			"FROM videoadder ".
			"WHERE va_timestamp >= '$starttimestamp' ". $bot .
			"AND va_skipped_accepted IN ('0','1') ".
			"GROUP BY va_user ORDER BY C desc ";
		if ($getArticles) {
			$u = User::newFromName($lb_user);
			$u->load();
			$id = $u->getID();
			$sql = "SELECT va_user, page_title, page_namespace ".
				"FROM videoadder left join page on page_id=va_page ".
				"WHERE va_timestamp >= '$starttimestamp' and va_user={$id}";
		}

		$res = $dbr->query($sql, __METHOD__);

		foreach ($res as $row) {
			if ($getArticles) {
				$t = Title::makeTitle($row->page_namespace, $row->page_title);
				if ($t)
					$data[$t->getPartialURL()] = $t->getPrefixedText();
			} else {
				//$data[$row->va_user_text]++;
				$data[$row->va_user_text] = number_format($row->C, 0, "", ',');
			}
		}

		$wgMemc->set($cachekey, $data, 3600);
		return $data;
	}

	/**
	 * Query for TopicTagging stats
	 **/
	public static function getTopicsTagged($starttimestamp, $lb_user = '', $getArticles = false) {
		global $wgMemc, $wgSharedDB;

		if ($getArticles) {
			$key = "leaderboard:topicstagged:$starttimestamp:$lb_user";
		} else {
			$key = "leaderboard:topicstagged:$starttimestamp";
		}

		$cachekey = wfMemcKey($key);
		$val = $wgMemc->get($cachekey);
		if (is_array($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$data = array();
		$starttimestamp = $dbr->strencode($starttimestamp);

		if ($getArticles) {
			$u = User::newFromName($lb_user);

			$sql = "SELECT log_title ".
				"FROM logging ".
				"WHERE log_type='topic_tagging' AND log_user = ".$u->getID()." and log_action IN ('upvote','downvote') and log_timestamp >= '$starttimestamp' ".
				"ORDER BY log_timestamp DESC ".
				"LIMIT 30";
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$t = Title::newFromText($row->log_title);
				if (isset($t)) {
					$data[$t->getPartialURL()] = $t->getPrefixedText();
				}
			}

		} else {

			$bots = WikihowUser::getBotIDs();
			$bot = "";

			if (count($bots) > 0) {
				$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ") ";
			}

			$sql = "SELECT log_user, count(*) as C , user_name
				FROM logging left join $wgSharedDB.user on user_id=log_user
				WHERE log_type='topic_tagging' and log_action IN ('upvote','downvote') and log_timestamp >= '$starttimestamp' " . $bot .
				"GROUP BY log_user ORDER BY C desc limit 30;";

			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$u = User::newFromName( $row->user_name );
				if ($u && $u->getId() > 0) {
					$data[$u->getName()] = $row->C;
				} else {
					// uh oh maybe?
				}
			}
		}
		$wgMemc->set($cachekey, $data, 60 * 15);
		return $data;
	}

}
