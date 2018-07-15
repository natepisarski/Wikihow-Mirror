<?php

class Common {
	public static function log($action, $articleId, $articleTitle) {
		global $wgUser, $wgRequest;
		wfDebugLog('nab', 'action = ' . $action
			. '; user = ' . $wgUser->getID() . ' (' . $wgUser->getName() .')'
			. '; article = ' . $articleId . ' (' . $articleTitle .')'
			. '; referer = ' . $wgRequest->getHeader("referer")
			. '; post string = ' . urldecode($wgRequest->getRawPostString())
		);
	}
}

class Newarticleboost extends SpecialPage {

	/**
	 * A constant to change when JS/CSS has been updated so that a new
	 * version is pulled off the CDN.
	 */
	const REVISION = 10;
	const DEMOTE_CATEGORY = "Articles in Quality Review";
	const NAB_TABLE = "newarticlepatrol";
	const BACKLOG_DATE = "20150401000000";

	public function __construct() {
		global $wgHooks;
		parent::__construct('Newarticleboost');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	//Function to determine if a user is a new article patroller
	public static function isNewArticlePatrol($user){
		return in_array('newarticlepatrol', $user->getGroups());
	}

	/**
	* Function which determines if an article can be summarily overwritten:
	* which occurs when any of the following is true:
	* 1.  A human pressed demote in NAB
	* 2.  They have been in NAB for over 18 months
	* 3.  They have been in NAB for over 6 months and have a quality score under .5
	**/
	public static function isOverwriteAllowed($title) {

		if( $title && $title->exists() ) {
			//first make sure it doesn't have a merge template
			$revision = Revision::newFromTitle($title);
			if (!$revision || $revision->getId() <= 0) return false;
			$text = $revision->getText();
			if (stripos($text, "{{merge") !== false) return false;

			$dbr = wfGetDB(DB_SLAVE);

			$eighteenMonths = wfTimestamp(TS_MW, strtotime("-18 months")); //18 months ago
			$sixMonths = wfTimestamp(TS_MW, strtotime("-6 months")); //6 months ago

			$res = $dbr->select(Newarticleboost::NAB_TABLE, array('nap_demote', 'nap_timestamp', 'nap_atlas_score', 'nap_patrolled'), array('nap_page' => $title->getArticleID()), __METHOD__);

			$row = $dbr->fetchRow($res);
			if($row === false) {
				//it was never put into nab (likely older than 2009)
				//which means we definitely don't want to overwrite
				return false;
			} elseif($row['nap_patrolled'] == 1) {
				return false;
			} elseif ($title->isRedirect()) {
				return false;
			} else if($row['nap_demote'] == 1) {
				return true;
			} else if ($row['nap_timestamp'] < $eighteenMonths) {
				return true;
			} else if($row['nap_timestamp'] < $sixMonths && $row['nap_atlas_score'] < 50) {
				return true;
			}

			return false;

		}
		return false;
	}

	/**
	 * Resets the values in the NAB table for the given title. The values to reset are:
	 * 1) timestamp
	 * 2) deomotion status
	 * 3) score
	 **/
	public static function redoNabStatus($title) {
		global $wgMemc;

		if($title) {
			Common::log('Newarticleboost::redoNabStatus', $title->getArticleID(), $title);
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(Newarticleboost::NAB_TABLE, array('nap_timestamp' => wfTimestamp(TS_MW), 'nap_demote' => 0, 'nap_atlas_score' => -1), array('nap_page' => $title->getArticleID()), __FUNCTION__);

			$wgMemc->delete( self::getNabbedCachekey($title->getArticleID()) );
		}
	}

	/**
	 * Returns the total number of New Articles waiting to be
	 * NAB'd.
	 */
	public static function getNABCount($dbr) {
		// putting the templatelinks condition back into the query
		// since the nab count is so low it does not effect the time of the query like it used
		// to and the number differce is much higher percentage (like 6000% currently).
		$one_hour_ago = wfTimestamp(TS_MW, time() - 60 * 60);
		$sql = "SELECT count(*) as C
				  FROM " . Newarticleboost::NAB_TABLE . ", page
				  LEFT JOIN templatelinks ON tl_from = page_id AND tl_title='Inuse'
				  WHERE page_id = nap_page
					AND page_is_redirect = 0
					AND nap_patrolled = 0
					AND nap_demote = 0
					AND tl_title is NULL
					AND nap_atlas_score >= 0
					AND nap_timestamp < '$one_hour_ago'
					AND nap_timestamp > '" . Newarticleboost::BACKLOG_DATE . "'";
		$res = $dbr->query($sql, __METHOD__);
		$row = $dbr->fetchObject($res);

		return $row->C;
	}

	public function getNABCountOld() {
		$dbr = wfGetDB(DB_SLAVE);

		$one_hour_ago = wfTimestamp(TS_MW, time() - 60 * 60);
		$sql = "SELECT count(*) as C
				  FROM " . Newarticleboost::NAB_TABLE . ", page
				  WHERE page_id = nap_page
					AND page_is_redirect = 0
					AND nap_patrolled = 0
					AND nap_demote = 0
					AND nap_timestamp < '$one_hour_ago'
					AND nap_timestamp <= '" . Newarticleboost::BACKLOG_DATE . "'";
		$res = $dbr->query($sql, __METHOD__);
		$row = $dbr->fetchObject($res);

		return $row->C;
	}

	/**
	 * Returns the id of the last NAB.
	 */
	public static function getLastNAB($dbr) {
		$res = $dbr->select(Newarticleboost::NAB_TABLE,
			array('nap_user_ci', 'nap_timestamp_ci'),
			array('nap_patrolled' => 1),
			__METHOD__,
			array('ORDER BY' => 'nap_timestamp_ci DESC', 'LIMIT' => 1));

		$row = $dbr->fetchObject($res);
		$nabuser = array();
		$nabuser['id'] = $row->nap_user_ci;
		$nabuser['date'] = wfTimeAgo($row->nap_timestamp_ci);

		return $nabuser;
	}

	/**
	 * Gets the total number of articles patrolled by the given user after
	 * the given timestamp.
	 */
	public static function getUserNABCount($dbr, $userId, $starttimestamp) {
		$row = $dbr->selectField(Newarticleboost::NAB_TABLE,
			'count(*) as count',
			array('nap_patrolled' => 1,
				'nap_user_ci' => $userId,
				'nap_timestamp_ci > "' . $starttimestamp . '"'),
			__METHOD__);
		return $row;
	}

	private static function getNabbedCachekey($page) {
		return wfMemcKey('napdata', $page);
	}

	/**
	 * Check whether or not a page ID has been nabbed.
	 * @param int $page
	 * @return boolean true if it's been nabbed
	 * or doesn't exist in newarticlepatrol table
	 */
	public static function isNABbed($dbr, $page) {
		$nap = self::getNAPData($dbr, $page);
		return (bool)$nap['nap_patrolled'];
	}

	public static function isNABbedNoDb($page) {
		$dbr = wfGetDB(DB_SLAVE);
		return self::isNABbed($dbr, $page);
	}

	/**
	 * For Titus -- get promoted date
	 * @param int $page
	 * @return date promoted, 0 (if it's not promoted), date created (if not in nab), or date added to nab (if too old to have a timestamp_ci)
	 */
	public static function getNABbedDate($dbr, $page) {
		$result = 0;
		$nap = self::getNAPData($dbr, $page);
		if ((bool)$nap['nap_patrolled']) {
			if ((bool)$nap['exists']) {
				$date = ($nap['nap_timestamp_ci']) ? $nap['nap_timestamp_ci'] : $nap['nap_timestamp'];
				if ($date) $result = $date;
			}
			else {
				//doesn't exist in NAB; grab creation date
				$createdate = $dbr->selectField('revision', 'min(rev_timestamp)', array('rev_page' => $page), __METHOD__);
				if ($createdate) $result = $createdate;
			}
		}
		return $result;
	}

	/**
	 * Check whether or not a page ID has been demoted.
	 * @param int $page
	 * @return boolean true if it's been demoted
	 * or doesn't exist in newarticlepatrol table
	 */
	public function isDemoted($dbr, $page) {
		$nap = self::getNAPData($dbr, $page);
		return (bool)$nap['nap_demote'];
	}


	public static function isDemotedNoDb($page) {
		$dbr = wfGetDB(DB_SLAVE);
		return self::isDemoted($dbr, $page);
	}

	/**
	 * For Titus -- get demoted date
	 * @param int $page
	 * @return date, 0 (if it's not promoted), or 1 (if it's promoted but old and dateless OR not in NAB)
	 */
	public static function getDemotedDate($dbr, $page) {
		$result = 0;
		$nap = self::getNAPData($dbr, $page);
		if ((bool)$nap['nap_demote']) {
			if ((bool)$nap['exists']) {
				$date = ($nap['nap_timestamp_ci']) ? $nap['nap_timestamp_ci'] : $nap['nap_timestamp'];
				if ($date) $result = $date;
			}
			else {
				//doesn't exist in NAB; grab assume it hasn't been demoted
			}
		}
		return $result;
	}

	/**
	 * Check whether or not a page ID is older than
	 * @param $dbr
	 * @param int $page
	 * @param $ts timestamp cutoff
	 * @return boolean true iff the page is older than the timestamp
	 * in the newarticlepatrol table. Returns false f the article doesn't exist or isn't older
	 * than the $ts param
	 */
	public function isOlderThan($dbr, $page, $ts) {
		$nap = self::getNAPData($dbr, $page);
		return isset($nap['nap_timestamp']) ? $nap['nap_timestamp'] < $ts : false;
	}

	/**
	 * @param $dbr
	 * @param int $page
	 * @return array containing data on whether page is nabbed.
	 * The nap timestamp will also be returned if the page exists in
	 * the newarticlepatrol table
	 */
	private static function getNAPData($dbr, $page) {
		global $wgMemc;

		$cachekey = self::getNabbedCachekey($page);
		$nap = $wgMemc->get($cachekey);
		if (is_array($nap)) return $nap;

		$nap = array('nap_patrolled' => 1,'nap_demote' => 0);
		$res = $dbr->select(
			Newarticleboost::NAB_TABLE,
			array('nap_patrolled', 'nap_timestamp','nap_demote','nap_timestamp_ci'),
			array('nap_page' => $page),
			__METHOD__);

		$row = $dbr->fetchRow($res);
		if (is_array($row)) {
			if ($row['nap_patrolled'] === '0') $nap['nap_patrolled'] = 0;
			if ($row['nap_demote'] === '1') $nap['nap_demote'] = 1;
			$nap['nap_timestamp'] = $row['nap_timestamp'];
			$nap['nap_timestamp_ci'] = $row['nap_timestamp_ci'];
			$nap['exists'] = 1;
		}
		else {
			$nap['exists'] = 0;
		}

		$wgMemc->set($cachekey, $nap, 5 * 60); // cache for 5 minutes
		return $nap;
	}

	/**
	 * Used in community dashboard to find out the most recently nabbed
	 * article.
	 */
	public static function getHighestNAB($dbr, $period = '7 days ago') {
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG', $startdate) . floor(date('i', $startdate) / 10) . '00000';

		$res = $dbr->select('logging',
			array('*',
				'count(*) as C',
				'MAX(log_timestamp) as recent_timestamp'),
			array("log_type" => 'nap',
				'log_timestamp > "' . $starttimestamp . '"'),
			__METHOD__,
			array("GROUP BY" => 'log_user',
				"ORDER BY"=>"C DESC",
				"LIMIT"=>1));
		$row = $dbr->fetchObject($res);

		$nabuser = array();
		$nabuser['id'] = $row->log_user;
		$nabuser['date'] = wfTimeAgo($row->recent_timestamp);

		return $nabuser;
	}

	/**
	 * If a user is nabbing an article, there are Skip/Cancel and Mark as
	 * Patrolled buttons at the buttom of the list of NAB actions.  When
	 * either of these buttons are pushed, this function processes the
	 * submitted form.
	 */
	private function doNABAction($dbw) {
		global $wgRequest, $wgOut, $wgUser;

		$errMsg = '';
		$aid = $wgRequest->getVal('page', 0);
		$aid = intval($aid);

		if ($wgRequest->getVal('nap_submit', null) != null) {
			$title = Title::newFromID($aid);

			Common::log('Newarticleboost::doNABAction - nap_submit', $aid, $title);
			// MARK ARTICLE AS PATROLLED
			self::markNabbed($dbw, $aid, $wgUser->getId());

			if (!$title) {
				Misc::jsonResponse(array('message' => "Error: target page for NAB was not found"), 400);
				return;
			}

			// ADD ANY TEMPLATES
			self::addTemplates($title);

			// Rising star actions FS RS
			$this->flagRisingStar($title);

			// DELETE ARTICLE IF PATROLLER WANTED THIS
			if ($wgRequest->getVal('delete', null) != null && $wgUser->isAllowed('delete')) {
				$article = new Article($title);
				$article->doDelete($wgRequest->getVal("delete_reason"));
			}

			// MOVE/RE-TITLE ARTICLE IF PATROLLER WANTED THIS
			if ($wgRequest->getVal('move', null) != null && $wgUser->isAllowed('move')) {
				if ($wgRequest->getVal('move_newtitle', null) == null) {
					Misc::jsonResponse(array('message' => "Error: no target page title specified."), 400);
					return;
				}
				$newTarget = $wgRequest->getVal('move_newtitle');
				$newTitle = Title::newFromText($newTarget);
				if (!$newTitle) {
					Misc::jsonResponse(array('message' => "Bad new title: $newTarget"), 400);
					return;
				}

				$ret = $title->moveTo($newTitle);
				if (is_string($ret)) {
					$errMsg = "Renaming of the article failed: " . wfMessage($ret);
				}

				// move the talk page if it exists
				$oldTitleTalkPage = $title->getTalkPage();
				if ($oldTitleTalkPage->exists()) {
					$newTitleTalkPage = $newTitle->getTalkPage();
					if ($oldTitleTalkPage->moveTo($newTitleTalkPage) === true) {
						$errMsg = "Error moving the talk page";
					}
				}

				$title = $newTitle;
			}

			wfRunHooks("NABArticleFinished", array($aid));
		}

		// GET NEXT UNPATROLLED ARTICLE
		if ($wgRequest->getVal('nap_skip') && $wgRequest->getVal('page') ) {
			Common::log('Newarticleboost::doNABAction - nap_skip', $aid, Title::newFromID($aid));
			// if article was skipped, clear the checkout of the
			// article, so others can NAB it
			$dbw->update(Newarticleboost::NAB_TABLE,
				array('nap_user_co' => 0),
				array('nap_page' => $aid),
				__METHOD__);
		}

		if( $wgRequest->getVal('nap_demote') && $wgRequest->getVal('page') ) {
			// ADD ANY TEMPLATES
			$title = Title::newFromID($aid);
			Common::log('Newarticleboost::doNABAction - nap_demote', $aid, $title);
			self::addTemplates($title);

			$this->demoteArticle($dbw, $aid, $wgUser->getId());
		}

		if ($errMsg) {
			Misc::jsonResponse(array('message' => $errMsg), 400);
		} else {
			Misc::jsonResponse(array('message' => "Operation successful"));
		}
	}

	public static function markPreviousEditsPatrolled($aid, $maxrcid = false) {
		$dbr = wfGetDB( DB_SLAVE );
		$user = RequestContext::getMain()->getUser();

		if (!$maxrcid) {
			$maxrcid = $dbr->selectField('recentchanges', 'MAX(rc_id)', array('rc_cur_id' => $aid), __METHOD__);
		}

		if ($maxrcid) {
			$res = $dbr->select('recentchanges',
								'rc_id',
								array('rc_id <= ' . $dbr->addQuotes($maxrcid),
										'rc_cur_id' => $aid,
										'rc_patrolled' => 0,
										'rc_user <> ' . $user->getId()
									),
								__METHOD__
							);

			foreach ($res as $row) {
					RecentChange::markPatrolled( $row->rc_id );
					PatrolLog::record( $row->rc_id, false );
			}

			$dbr->freeResult($res);
		}

	}
	public static function removeDemotedCategory($aid) {
		global $wgContLang;

		//remove the demoted category if it exists on this page
		$dbr = wfGetDB(DB_SLAVE);
		$cats = $dbr->select('categorylinks', array('cl_to'), array('cl_from' => $aid), __METHOD__);
		$dbCat = str_replace(" ", "-", Newarticleboost::DEMOTE_CATEGORY);
		$found = false;
		foreach($cats as $cat) {
			if($cat->cl_to == $dbCat) {
				$found = true;
				break;
			}
		}

		if($found) {
			$t = Title::newFromID($aid);
			$wikitext = Wikitext::getWikitext($dbr, $t);
			Common::log('Newarticleboost::removeDemotedCategory', $aid, $t);

			$intro = Wikitext::getIntro($wikitext);
			$intro = str_replace("\n[[" . $wgContLang->getNSText(NS_CATEGORY) . ":" . Newarticleboost::DEMOTE_CATEGORY . "]]", "", $intro);
			$wikitext = Wikitext::replaceIntro($wikitext, $intro);
			$result = Wikitext::saveWikitext($t, $wikitext, wfMessage('nap_demote_remove')->text());
		}
	}

	/**
	 * Mark an article as NAB'bed.
	 */
	public static function markNabbed($dbw, $aid, $userid, $logit = true) {
		global $wgMemc, $wgLanguageCode, $wgRequest;

		$wgMemc->delete( self::getNabbedCachekey($aid) );

		$ts = wfTimestampNow();
		$dbw->update(Newarticleboost::NAB_TABLE,
			array('nap_timestamp_ci' => $ts,
				'nap_user_ci' => $userid,
				'nap_patrolled' => '1',
				'nap_demote' => '0'),
			array('nap_page' => $aid),
			__METHOD__);

		Newarticleboost::removeDemotedCategory($aid);

		$page = WikiPage::newFromID($aid);
		if ($page) {
			RobotPolicy::clearArticleMemc($page);
		}

		// LOG ENTRY
		if ($logit) {
			$title = Title::newFromID($aid);
			if($title) {
				Common::log('Newarticleboost::markNabbed', $aid, $title);
				$params = array($aid);
				$log = new LogPage('nap', false);
				$log->addEntry( 'nap', $title, wfMessage('nap_logsummary', $title->getFullText())->text(), $params );
			}
		}

		if ( $wgRequest->getVal('maxrcid') ) {
			self::markPreviousEditsPatrolled($aid, $wgRequest->getVal('maxrcid'));
		} else {
			self::markPreviousEditsPatrolled($aid);
		}

		wfRunHooks('NABMarkPatrolled', array($aid));

		//send message
		if ($wgLanguageCode == "en") {
			Newarticleboost::sendPromotedMessage($userid, $page);
		}
	}

	// Review this in relation to Felicity: NAB automatically adding... bug
	public function demoteArticle($dbw, $aid, $userid, $logit = true) {
		global $wgContLang, $wgMemc, $wgLanguageCode;

		$wgMemc->delete( self::getNabbedCachekey($aid) );

		//clear robot cache
		$page = WikiPage::newFromID($aid);
		if ($page) RobotPolicy::clearArticleMemc($page);

		$ts = wfTimestampNow();

		Common::log('Newarticleboost::demoteArticle', $aid, Title::newFromID($aid));
		if (self::existsInNab($aid)) {
			//do that demotion
			$dbw->update(Newarticleboost::NAB_TABLE,
				array('nap_timestamp_ci' => $ts,
					'nap_user_ci' => $userid,
					'nap_patrolled' => '0',
					'nap_demote' => '1'),
				array('nap_page' => $aid),
				__METHOD__);
		}
		else {
			//add it demoted, but first grab the rev timestamp
			$min_ts = $dbw->selectField('revision',
				array('min(rev_timestamp)'),
				array('rev_page' => $aid),
				__METHOD__);

			$dbw->insert(Newarticleboost::NAB_TABLE,
				array(
					'nap_page' => $aid,
					'nap_timestamp' => $min_ts,
					'nap_patrolled' => '0',
					'nap_demote' => '1'),
				__METHOD__);

			//also add to nab_atlas
			if ($wgLanguageCode == 'en') {
				$dbw->insert('nab_atlas', array('na_page_id' => $aid), __METHOD__);
			}

		}


		//add demote cat
		$t = Title::newFromId($aid);
		if ($t && $t->exists()) {
			$dbr = wfGetDB(DB_MASTER);
			$wikitext = Wikitext::getWikitext($dbr, $t);
			$intro = Wikitext::getIntro($wikitext);
			$cat = "\n[[" . $wgContLang->getNSText(NS_CATEGORY) . ":" . Newarticleboost::DEMOTE_CATEGORY . "]]";
			$intro .= $cat;
			$wikitext = Wikitext::replaceIntro($wikitext, $intro);
			$result = Wikitext::saveWikitext($t, $wikitext, wfMessage('nap_demote_comment')->text());
		}

		// LOG ENTRY
		if ($logit) {
			$params = array($aid);
			$log = new LogPage('nap', false);
			$log->addEntry( 'nap', $t, wfMessage('nap_logsummary_demote', $t->getFullText())->text(), $params );
		}

		self::markPreviousEditsPatrolled($aid);
		wfRunHooks( 'NABArticleDemoted', array($aid) );
	}

	private function getNabUrl($title) {
		$nap = SpecialPage::getTitleFor('Newarticleboost', $title);
		return $nap->getFullURL(
			($this->do_newbie ? 'newbie=1' : '') .
			"&sortOrder={$this->sortOrder}" .
			"&sortValue={$this->sortValue}" .
			"&low={$this->wantLow}" .
			"&old={$this->wantOld}"
		);
	}

	/**
	 * Look up the next NAB page in sequence.
	 * @param string $aid The article ID to look up
	 * @return Title the representing Title object or null if not found
	 */
	private function getNextUnpatrolledArticle($dbw, $aid) {
		global $wgUser;

		$patrolled_opt = $this->do_newbie ? '' : 'AND nap_patrolled = 0';
		$newbie_opt = $this->do_newbie ? 'AND nap_newbie = 1' : '';
		$score_opt = $this->do_score ? 'AND nap_atlas_score != -1' : '';
		$old_opt = $this->wantOld ? ' AND nap_timestamp <= "' . Newarticleboost::BACKLOG_DATE . '" ' : ' AND nap_timestamp > "' . Newarticleboost::BACKLOG_DATE . '" ';

		$order = " ORDER BY nap_atlas_score DESC, nap_page DESC";
		if ($this->sortValue == "score") {
			$order = " ORDER BY nap_atlas_score {$this->sortOrder}, nap_page {$this->sortOrder}";
		} elseif ($this->sortValue == "date") {
			$order = " ORDER BY nap_timestamp {$this->sortOrder}";
		}

		$half_hour_ago = wfTimestamp(TS_MW, time() - 30 * 60);

		$sql = "SELECT page_title, nap_page
				  FROM " . Newarticleboost::NAB_TABLE . ", page
				  LEFT OUTER JOIN templatelinks ON page_id = tl_from
					AND tl_title='Inuse'
				  WHERE nap_page != $aid
				  AND page_id = nap_page
					AND page_is_redirect = 0
					{$patrolled_opt}
					AND (nap_user_co = 0 OR nap_timestamp_co < '$half_hour_ago')
					AND tl_title IS NULL
					AND nap_demote = 0
					{$score_opt}
					{$newbie_opt}
					{$old_opt}";
		if ($this->sortValue == "date") {
			$timestamp = $dbw->selectField(Newarticleboost::NAB_TABLE, 'nap_timestamp', array('nap_page' => $aid), __METHOD__);
			if ($this->sortOrder == "asc") {
				$sql .= " AND nap_timestamp >= {$timestamp}";
			} else {
				$sql .= " AND nap_timestamp <= {$timestamp}";
			}
			$sql .= " AND nap_page != {$aid} ";
		} else {
			$atlasScore = $dbw->selectField(Newarticleboost::NAB_TABLE, 'nap_atlas_score', array('nap_page' => $aid), __METHOD__);
			if ($this->sortOrder == "asc") {
				$sql .= "AND ((nap_page > $aid AND nap_atlas_score = $atlasScore) OR (nap_page != $aid AND nap_atlas_score > $atlasScore))";
			} else {
				$sql .= "AND ((nap_page < $aid AND nap_atlas_score = $atlasScore) OR (nap_page != $aid AND nap_atlas_score < $atlasScore))";
			}
		}

		$sql .= "{$order} LIMIT 1";

		$res = $dbw->query($sql, __METHOD__);

		$id = 0;
		if (($row = $dbw->fetchObject($res)) != null) {
			$id = $row->nap_page;
		}

		return $id ? Title::newFromID($id) : null;
	}

	/**
	 * Check whether templates needed to be added to article (via posted
	 * request).  If there are, add them to wikitext.
	 */
	private static function addTemplates($title) {
		global $wgRequest, $wgOut;

		// Check if there are templates to add to article
		$formVars = $wgRequest->getValues();
		$newTemplates = '';
		$templatesArray = array();
		foreach ($formVars as $key => $value) {
			if (strpos($key, 'template') === 0 && $value == 'on') {
				$len = strlen('template');
				$i = substr($key, $len, 1);
				$template = substr($key, $len + 2, strlen($key) - $len - 2);
				$params = '';
				foreach ($formVars as $key2=>$value2) {
					if (strpos($key2, "param$i") === 0) {
						$params .= '|';
						$params .= $value2;
					}
				}
				if ($template == 'nfddup') {
					$template = 'nfd|dup';
				}
				$newTemplates .= '{{' . $template . $params . '}}';
				$templatesArray[] = $template;
			}
		}

		// Add templates if there were some to add
		if ($newTemplates) {
			$rev = Revision::newFromTitle($title);
			$article = new Article($title);
			$wikitext = $rev->getText();
			// were these templates were already added, maybe
			// a back button situation?
			if (strpos($wikitext, $newTemplates) === false) {
				$wikitext = "$newTemplates\n$wikitext";
				$watch = $title->userIsWatching(); // preserve watching just in case
				$updateResult = $article->updateArticle($wikitext,
					wfMessage('nap_applyingtemplatessummary', implode(', ', $templatesArray))->text(),
					false, $watch);
				if ($updateResult) {
					$wgOut->redirect('');
				}
			}
		}
	}

	/**
	 * NAB user flagged this article as a rising star in the Action section
	 * of NAB'ing an article.
	 */
	private function flagRisingStar($title) {
		global $wgLang, $wgUser, $wgRequest;

		if ($wgRequest->getVal('cb_risingstar', null) != "on") {
			return;
		}
		Common::log('Newarticleboost::flagRisingStar', $title->getArticleID(), $title);

		$dateStr = $wgLang->timeanddate(wfTimestampNow());

		$patrollerName = $wgUser->getName();
		$patrollerRealName = User::whoIsReal($wgUser->getID());
		if (!$patrollerRealName) {
			$patrollerRealName = $patrollerName;
		}

		// post to user talk page
		$contribUsername = $wgRequest->getVal('prevuser', '');
		if ($contribUsername) {
			$this->notifyUserOfRisingStar($title, $contribUsername);
		}

		// Give user a thumbs up. Set oldId to -1 as this should be the
		// first revision
		//if (class_exists('ThumbsUp')) {
		//	ThumbsUp::thumbNAB(-1, $title->getLatestRevID(), $title->getArticleID());
		//}

		// post to article discussion page
		$wikitext = "";
		$article = "";

		$contribUser = new User();
		$contribUser->setName($contribUsername);
		$contribUserPage = $contribUser->getUserPage();
		$contribUserName = $contribUser->getName();
		$patrolUserPage = $wgUser->getUserPage();
		$patrolUserName = $wgUser->getName();

		$talkPage = $title->getTalkPage();
		$comment = '{{Rising-star-discussion-msg-2|[['.$contribUserPage.'|'.$contribUserName.']]|[['.$patrolUserPage.'|'.$patrolUserName.']]}}' . "\n";
		$formattedComment = TalkPageFormatter::createComment( $this->getUser(), $comment );

		wfRunHooks("MarkTitleAsRisingStar", array($title));

		if ($talkPage->getArticleId() > 0) {
			$rev = Revision::newFromTitle($talkPage);
			$wikitext = $rev->getText();
		}
		$article = new Article($talkPage);

		$wikitext = "$comment\n\n" . $wikitext;

		$watch = false;
		if ($wgUser->getID() > 0)
			$watch = $wgUser->isWatched($talkPage);

		if ($talkPage->getArticleId() > 0) {
			$article->updateArticle($wikitext, wfMessage('nab-rs-discussion-editsummary')->text(), true, $watch);
		} else {
			$article->insertNewArticle($wikitext, wfMessage('nab-rs-discussion-editsummary')->text(), true, $watch, false, false, true);
		}

		// add to fs feed page
		$wikitext = "";
		$article = "";
		$fsfeed = Title::newFromURL('wikiHow:Rising-star-feed');
		$rev = Revision::newFromTitle($fsfeed);
		$article = new Article($fsfeed);
		$wikitext = $rev->getText();

		$watch = false;
		if ($wgUser->getID() > 0) {
			$watch = $wgUser->isWatched($title->getTalkPage());
		}

		$wikitext .= "\n".  date('==Y-m-d==') . "\n" . $title->getCanonicalURL() . "\n";
		$article->updateArticle($wikitext, wfMessage('nab-rs-feed-editsummary')->text(), true, $watch);

	}

	private function displayNABConsole($dbw, $dbr, $target, $noSkin = false) {
		global $wgOut, $wgRequest, $wgUser, $wgParser;

		if ($noSkin) {
			$wgOut->clearHTML();
		}

		$not_found = false;
		$title = Title::newFromURL($target);
		if (!$title || !$title->exists()) {
			$articleName = $title ? $title->getFullText() : $target;
			$wgOut->addHTML("<p>Error: Article &ldquo;{$articleName}&rdquo; not found. Return to <a href='/Special:Newarticleboost'>New Article Boost</a> instead.</p>");
			$not_found = true;
		}

		if (!$not_found) {
			$rev = Revision::newFromTitle($title);
			if (!$rev) {
				$wgOut->addHTML("<p>Error: No revision for &ldquo;{$title->getFullText()}&rdquo;. Return to <a href='/Special:Newarticleboost'>New Article Boost</a> instead.</p>");
				$not_found = true;
			}
		}

		if (!$not_found) {
			$in_nab = $dbr->selectField(Newarticleboost::NAB_TABLE, 'count(*)', array('nap_page'=>$title->getArticleID()), __METHOD__) > 0;
			if (!$in_nab) {
				$wgOut->addHTML("<p>Error: This article is not in the NAB list.</p>");
				$not_found = true;
			}
		}

		if ($not_found) {
			$pageid = $wgRequest->getVal('page');
			if (strpos($target, ':') !== false && $pageid) {
				$wgOut->addHTML('<p>We can to try to <a href="/Special:NABClean/' . $pageid . '">delete this title</a> if you know this title exists in NAB yet is likely bad data.</p>');
			}
			if ($noSkin) {
				$wgOut->disable();
				echo $wgOut->getHTML();
			}
			return;
		}

		$locked = false;

		$min_timestamp = $dbr->selectField("revision", "min(rev_timestamp)", "rev_page=" . $title->getArticleId(), __METHOD__);
		$first_user = $dbr->selectField("revision", "rev_user_text", array("rev_page=" . $title->getArticleId(), 'rev_timestamp' => $min_timestamp), __METHOD__);
		$first_user_id = $dbr->selectField("revision", "rev_user", array("rev_page=" . $title->getArticleId(), 'rev_timestamp' => $min_timestamp), __METHOD__);
		$user = new User();
		if ($first_user_id) {
			$user->setId($first_user_id);
			$user->loadFromDatabase();
		} else {
			$user->setName($first_user);
		}

		$user_talk = $user->getTalkPage();
		$ut_id = $user_talk->getArticleID();
		$display_name = $user->getRealName() ? $user->getRealName() : $user->getName();

		$wgOut->setPageTitle(wfMessage('nap_title', $title->getFullText()));
		$count = $dbr->selectField('suggested_titles', array('count(*)'), array('st_title' => $title->getDBKey()), __METHOD__);
		$extra = $count > 0 ? ' - from Suggested Titles database' : '';

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$vars = array();
		$vars['low'] = $this->wantLow;
		$vars['old'] = $this->wantOld;
		$vars['sortValue'] = $this->sortValue;
		$vars['sortOrder'] = $this->sortOrder;
		$vars['articleTitle'] = $title->getFullText();
		$vars['authorInfo'] = wfMessage('nap_writtenby', $user->getName(), $display_name, $extra)->parse();
		$vars['articleId'] = $title->getArticleID();
		$vars['score'] = $dbr->selectField(Newarticleboost::NAB_TABLE, 'nap_atlas_score', array('nap_page' => $title->getArticleID()), __METHOD__);

		$nextTitle = $this->getNextUnpatrolledArticle($dbw, $title->getArticleID());

		if ($nextTitle && $nextTitle->exists()) {
			$vars['nextNabUrl'] = $this->getNabUrl($nextTitle->getPrefixedText(), true);
			$vars['nextNabTitle'] = wfMessage('nap_title', $nextTitle->getFullText());
		} else {
			$vars['nextNabUrl'] = NULL;
			$vars['nextNabTitle'] = NULL;
		}

		/// CHECK TO SEE IF ARTICLE IS LOCKED OR ALREADY PATROLLED
		$aid = $title->getArticleID();
		$half_hour_ago = wfTimestamp(TS_MW, time() - 30 * 60);

		$patrolled = $dbr->selectField(Newarticleboost::NAB_TABLE, 'nap_patrolled', array("nap_page=$aid"), __METHOD__);
		$lockedMsg = "";

		if (self::isDemoted($dbr,$aid)) {
			$patrolledMsg = $this->msg( 'nap_already_demoted' );
			$locked = true;
		} elseif ($patrolled) {
			$locked = true;
			$patrolledMsg = wfMessage("nap_patrolled")->parseAsBlock();
		} else {
			$user_co = $dbr->selectField(Newarticleboost::NAB_TABLE, 'nap_user_co', array('nap_page' => $aid, "nap_timestamp_co > '$half_hour_ago'"), __METHOD__);
			if ($user_co != '' && $user_co != 0 && $user_co != $wgUser->getId()) {
				$x = User::newFromId($user_co);
				$lockedMsg = wfMessage("nap_usercheckedout2", $x->getName())->parseAsBlock();
				$locked = true;
			} else {
				// CHECK OUT THE ARTICLE TO THIS USER
				$ts = wfTimestampNow();
				$dbw->update(Newarticleboost::NAB_TABLE, array('nap_timestamp_co' => $ts, 'nap_user_co' => $wgUser->getId()), array("nap_page = $aid"), __METHOD__);
			}
		}

		$externalLinkImg = '<img src="' . wfGetPad('/skins/common/images/external.png') . '"/>';

		/// SIMILAR RESULT
		$count = 0;
		$l = new LSearch();
		$hits  = $l->externalSearchResultTitles($title->getFullText(), 0, 5);
		if (sizeof($hits) > 0) {
			$html = "";
			foreach ($hits as $hit) {
				$t1 = $hit;
				$id = rand(0, 500);
				if ($t1 == null
					|| $t1->getFullURL() == $title->getFullURL()
					|| $t1->getNamespace() != NS_MAIN
					|| !$t1->exists())
				{
					continue;
				}
				$safe_title = htmlspecialchars(str_replace("'", "&#39;", $t1->getText()));

				$matches[] = array(
					'relatedLink' => Linker::link($t1, wfMessage('howto', $t1->getText()) ),
					'safeTitle' => $safe_title,
					'relatedId' => $t1->getArticleID(),
					'random' => $id,
					'count' => $count,

				);

				$count++;
			}
		}

		//$vars['titleTextUrl'] = urlencode($title->getFullText());
		$vars['matches']  = $matches;

		/// ARTICLE PREVIEW
		$popts = $wgOut->parserOptions();
		$popts->setTidy(true);
		$output = $wgParser->parse($rev->getText(), $title, $popts);
		$parserOutput = $output->getText();
		$magic = WikihowArticleHTML::grabTheMagic($rev->getText());
		$html = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => $title->getNamespace(), 'magic-word' => $magic));

		$vars['externalLinkImg'] = $externalLinkImg;
		$vars['fullUrl'] = $title->getFullURL();
		$vars['editUrl'] = $title->getEditURL();
		$vars['quickEditUrl'] = Title::makeTitle(NS_SPECIAL, "QuickEdit")->getFullURL() . "?type=editform&target=" . urlencode($title->getFullText()) . "&fromnab=1";;
		$vars['talkUrl'] = $title->getTalkPage()->getFullURL();
		$vars['articleHtml'] = $html;

		/// DISCUSSION PREVIEW
		$discText = '';
		$talkPage = $title->getTalkPage();
		if ($talkPage->getArticleID() > 0) {
			$rp = Revision::newFromTitle($talkPage);
			if ($rp) {
				$discText = $wgOut->parse($rp->getText());
				$discText = Avatar::insertAvatarIntoDiscussion($discText);
			}
		}
		if ($discText ==  '') $discText = wfMessage('nap_discussionnocontent');

		$postComment = new PostComment;
		$commentForm = $postComment->getForm(true, $talkPage, true);

		$vars['discText'] = $discText;
		$vars['commentForm'] = $commentForm;

		/// USER INFORMATION
		$used_templates = array();
		if ($ut_id > 0) {
			$res = $dbr->select('templatelinks', array('tl_title'), array('tl_from=' . $ut_id), __METHOD__);
			while($row = $dbr->fetchObject($res)) {
				$used_templates[] = strtolower($row->tl_title);
			}
			$dbr->freeResult($res);
		}

		$regDateTxt = "";
		if ($user->getRegistration() > 0) {
			preg_match('/^(\d{4})(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/D',$user->getRegistration(),$da);
			$uts = gmmktime((int)$da[4],(int)$da[5],(int)$da[6],
				(int)$da[2],(int)$da[3],(int)$da[1]);
			$regdate = gmdate('F j, Y', $uts);
			$regDateTxt = wfMessage('nap_regdatetext', $regdate) . ' ';
		}

		$key = 'nap_userinfodetails_anon';
		if ($user->getID() != 0) {
			$key = 'nap_userinfodetails2';
		}

		if (WikihowUser::getAuthorStats($first_user) < 50) {
			if ($user_talk->getArticleId() == 0) {
				$wgOut->addHTML(wfMessage('nap_newwithouttalkpage'));
			} else {
				$rp = Revision::newFromTitle($user_talk);
				$xtra = "";
				if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE 8.0") === false)
					$xtra = "max-height: 300px; overflow: scroll;";
				$output = $wgParser->parse($rp->getText(), $user_talk, $popts);
				$parserOutput = $output->getText();
				$userMsg = "<div style='border: 1px solid #eee; {$xtra}' id='nap_talk'>" . $parserOutput . "</div>";
			}
		}

		$vars['userInfo'] = wfMessage($key,
			$user->getName(),
			number_format(WikihowUser::getAuthorStats($first_user), 0, "", ","),
			$title->getFullText(),
			$regDateTxt)->parse();
		$vars['userTalkUrl'] = $user_talk->getFullURL();
		$vars['userMsg'] = $userMsg;
		$vars['userTalkComment'] = $postComment->getForm(true, $user_talk, true);

		// ACTION INFORMATION
		$maxrcid = $dbr->selectField('recentchanges', 'max(rc_id)', array('rc_cur_id=' . $aid), __METHOD__);

		//$vars['meUrl'] = $this->me->getFullURL();
		$vars['titleText'] = $title->getText();
		$vars['userName'] = $user->getName();
		$vars['maxrcid'] = $maxrcid;
		//$vars['actionMsg'] = $actionMsg;
		//$vars['locked'] = $locked;
		$vars['lockedMsg'] = $lockedMsg;
		$vars['patrolledMsg'] = $patrolledMsg;
		$tmpl->set_vars( $vars );
		$wgOut->addHTML($tmpl->execute('newarticleboost.tmpl.php'));

		if ($noSkin) {
			$wgOut->disable();
			echo $wgOut->getHTML();
		}

	}

	/**
	 * Special page class entry point
	 */
	public function execute($par) {
		global $wgRequest, $wgUser, $wgOut, $wgLanguageCode;

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		// set tidy on to avoid IE8 complaining about browser compatibility
		$opts = $wgOut->parserOptions();
		$opts->setTidy(true);
		$wgOut->parserOptions($opts);
		$wgOut->addMeta('X-UA-Compatible', 'IE=8');
		$wgOut->setRobotpolicy('noindex,nofollow');

		if ($wgLanguageCode == 'en') {
			$hasNABrights = in_array( 'newarticlepatrol', $wgUser->getRights() );
			$this->doAtlasScore = true;
		} else {
			$hasNABrights = in_array( 'staff', $wgUser->getGroups() )
				|| in_array( 'sysop', $wgUser->getGroups() );
			$this->doAtlasScore = false;
		}
		if ( !$hasNABrights ) {
			$wgOut->setArticleRelated(false);
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);
		$this->me = Title::makeTitle(NS_SPECIAL, "Newarticleboost");
		$this->can_newbie = in_array( 'newbienap', $wgUser->getRights() );
		$this->do_newbie = $wgRequest->getVal("newbie") == 1
			&& $this->can_newbie;
		$this->wantLow = $wgRequest->getVal("low") == "1";
		$this->sortValue = $wgRequest->getVal("sortValue", 'score');
		$this->sortOrder = $wgRequest->getVal("sortOrder", 'desc');
		$this->wantOld = $wgRequest->getVal("old") == "1";

		// We don't care about atlas score on int'l
		$this->do_score = $wgLanguageCode === 'en';

		$this->skin = $wgUser->getSkin();
		$wgOut->addModuleStyles('ext.wikihow.nab.styles');
		$wgOut->addModules('ext.wikihow.nab');
		$wgOut->addModules('ext.wikihow.UsageLogs');

		// We need to add the math styles here
		// because special pages technically don't contain math tags
		// so they're not loaded when we go to parse the article

		if ( class_exists( 'MathHooks') ) {
			$wgOut->addModules( array( 'ext.math.styles' ) );
			$wgOut->addModules( array( 'ext.math.desktop.styles' ) );
			$wgOut->addModules( array( 'ext.math.scripts' ) );
		}

		$target = isset($par) ? $par : $wgRequest->getVal('target');
		$wgOut->addJsConfigVars("isArticlePage", $target == true);

		if (!$target) {
			$llr = new NabQueryPage($this->can_newbie, $this->do_newbie, $this->sortValue, $this->sortOrder, $this->wantLow, $this->doAtlasScore, $this->wantOld);
			$llr->getList();
		} elseif ($wgRequest->wasPosted()) {
			$this->doNABAction($dbw);
		} else {
			$this->displayNABConsole($dbw, $dbr, $target, $wgRequest->getVal('noSkin') == true);
		}
	}

	/**
	 * Place the Rising-star-usertalk-msg on the user's talk page
	 * and emails the user
	 */
	public function notifyUserOfRisingStar($title, $name) {
		global $wgUser, $wgLang;
		$user = $wgUser->getName();
		$real_name = User::whoIsReal($wgUser->getID());
		if ($real_name == "") {
			$real_name = $user;
		}

		$wikitext = "";

		$userObj = new User();
		$userObj->setName($name);
		$user_talk = $userObj->getTalkPage();

		$comment = '{{subst:Rising-star-usertalk-msg|[['.$title->getText().']]}}' . "\n";
		$formattedComment = TalkPageFormatter::createComment( $this->getUser(), $comment );

		if ($user_talk->getArticleId() > 0) {
			$rev = Revision::newFromTitle($user_talk);
			$wikitext = $rev->getText();
		}
		$article = new Article($user_talk);

		$wikitext .= "\n\n$formattedComment\n\n";

		$article->doEdit( $wikitext, wfMessage('nab-rs-usertalk-editsummary')->text() );

		// Send author email notification
		AuthorEmailNotification::notifyRisingStar($title->getText(), $name, $real_name, $user);
	}

	public function existsInNab($article_id) {
		$dbr = wfGetDB(DB_SLAVE);
		$count = $dbr->selectField(Newarticleboost::NAB_TABLE,array('count(*)'),array('nap_page' => $article_id),__METHOD__);
		$result = ((int)$count > 0) ? true : false;
		return $result;
	}

	function sendPromotedMessage($userid, $page) {
		global $wgRequest;

		if (!$page) return;
		$t = $page->getTitle();

		//don't show when we're also going to give a rising star (that's yet another talk page message)
		if ($wgRequest && $wgRequest->getVal('cb_risingstar', null) == "on") return;

		//don't send this if this page won't get indexed (bad templates or some such thing)
		if (!RobotPolicy::isTitleIndexable($t)) return;

		//don't send this if this page is a redirect
		if ($t->isRedirect()) return;

		$creator = $page->getCreator();
		if (!$creator) return;
		if (!$creator->getOption('promotenotify')) return;

		$talkPage  = $creator->getUserPage()->getTalkPage();

		if ($talkPage) {
			$submitter = User::newFromID($userid);
			$text = '';
			$unformatted_comment = wfMessage('usertalk_promoted_article_message',$t->getText())->text();

			$comment = TalkPageFormatter::createComment(
				$submitter,
				$unformatted_comment,
				true,
				$t
			);

			// Send Echo notification
			if (class_exists( 'EchoEvent' )) {
				EchoEvent::create(
					array(
						'type' => 'edit-user-talk',
						'title' => $t,
						'extra' => array(
							'kudoed-user-id' => $creator->getId()
						),
						'agent' => $submitter
					) );
			}

			//add to existing?
			if ($talkPage->exists()) {
				$revision = Revision::newFromTitle($talkPage);
				$content = $revision->getContent(Revision::RAW);
				$text = ContentHandler::getContentText($content);
			}

			$text .= $comment;
			$page = WikiPage::factory($talkPage);
			$content = ContentHandler::makeContent($text, $talkPage);

			// Notify users of usertalk updates
			AuthorEmailNotification::notifyUserTalk($talkPage->getArticleID(), $submitter->getID(), $unformatted_comment);

			try {
				$page->doEditContent($content, '', EDIT_SUPPRESS_RC, false, $submitter);
			} catch (MWException $e) {
				wfDebugLog( 'CreateFirstArticle', 'exception in ' . __METHOD__ . ':' . $e->getText() );
			}
		}
	}
}

/**
 * AJAX server-side code for the NAB status check submission from NAB.
 */
class NABStatus extends SpecialPage {

	public function __construct() {
		parent::__construct('NABStatus');
	}

	public function execute($par) {
		global $wgTitle, $wgOut, $wgRequest, $wgUser;

		$target = isset($par) ? $par : $wgRequest->getVal('target');

		$wgOut->setHTMLTitle('New Article Boost Status - wikiHow');

		$sk = $wgUser->getSkin();
		$dbr = wfGetDB(DB_SLAVE);

		$wgOut->addHTML('<style type="text/css" media="all">/*<![CDATA[*/ @import "' . wfGetPad('/extensions/min/f/extensions/wikihow/nab/newarticleboost.css&' . Newarticleboost::REVISION) . '"; /*]]>*/</style>');
		$wgOut->addHTML(wfMessage('nap_statusinfo'));
		$wgOut->addHTML("<br/><center>");
		$days = $wgRequest->getVal('days', 1);
		if ($days == 1) {
			$wgOut->addHTML(" [". wfMessage('nap_last1day') . "] ");
			$wgOut->addHTML(" [" . Linker::link($wgTitle, wfMessage('nap_last7day'), array(), array('days' => 7)) . "] ");
			$wgOut->addHTML(" [" . Linker::link($wgTitle, wfMessage('nap_last30day'), array(), array('days' => 30)) . "] ");
		} else if ($days == 7) {
			$wgOut->addHTML(" [" . Linker::link($wgTitle, wfMessage('nap_last1day'), array(), array('days' => 1)) . "] ");
			$wgOut->addHTML(" [" . wfMessage('nap_last7day') . "] ");
			$wgOut->addHTML(" [" . Linker::link($wgTitle, wfMessage('nap_last30day'), array(), array('days' => 30)) . "] ");
		} else if ($days == 30) {
			$wgOut->addHTML(" [" . Linker::link($wgTitle, wfMessage('nap_last1day'), array(), array('days' => 1)) . "] ");
			$wgOut->addHTML(" [" . Linker::link($wgTitle, wfMessage('nap_last7day'), array(), array('days' => 7)) . "] ");
			$wgOut->addHTML(" [" . wfMessage('nap_last30day') . "] ");
		}

		$days_ago = wfTimestamp(TS_MW, time() - 60 * 60 * 24 * $days);
		$boosted = $dbr->selectField(array(Newarticleboost::NAB_TABLE, 'page'),
			array('count(*)'),
			array('page_id=nap_page', 'page_is_redirect=0', 'nap_patrolled=1', "nap_timestamp_ci > '$days_ago'"),
			__METHOD__);
		$newarticles = $dbr->selectField(array(Newarticleboost::NAB_TABLE),
			array('count(*)'),
			array("nap_timestamp > '$days_ago'"),
			__METHOD__);
		$na_boosted = $dbr->selectField(array(Newarticleboost::NAB_TABLE),
			array('count(*)'),
			array("nap_timestamp > '$days_ago'", "nap_patrolled"=>1),
			__METHOD__);

		$boosted = number_format($boosted, 0, "", ",");
		$newarticles = number_format($newarticles, 0, "", ",");
		$na_boosted = number_format($na_boosted, 0, "", ",");
		$per_boosted = $newarticles > 0 ? number_format($na_boosted/ $newarticles * 100, 2) : 0;
		$wgOut->addHTML("<br/><br/><div>
				<table width='50%' align='center' class='status'>
					<tr>
						<td>" . wfMessage('nap_totalboosted') . "</td>
						<td>$boosted</td>
					</tr>
					<tr>
						<td>" . wfMessage('nap_numnewboosted') . "</td>
						<td>$na_boosted</td>
					</tr>
					 <tr>
						<td>" . wfMessage('nap_numarticles') . "</td>
						<td>$newarticles</td>
					</tr>
					<tr>
						<td>" . wfMessage('nap_perofnewbosted') . "</td>
						<td>$per_boosted%</td>
					</tr>
				</table>
				</div>");
		$wgOut->addHTML("</center>");

		$wgOut->addHTML("<br/>" . wfMessage('nap_userswhoboosted') . "<br/><br/><center>
			<table width='500px' align='center' class='status'>" );

		$total = $dbr->selectField('logging', 'count(*)',  array ('log_type'=>'nap', "log_timestamp>'$days_ago'"), __METHOD__);

		$sql = "SELECT log_user, count(*) AS C
				  FROM logging WHERE log_type = 'nap'
					AND log_timestamp > '$days_ago'
				  GROUP BY log_user
				  ORDER BY C DESC
				  LIMIT 20";

		$res = $dbr->query($sql, __METHOD__);
		$index = 1;
		$wgOut->addHTML("<tr>
			<td></td>
				<td>User</td>
				<td  align='right'>" . wfMessage('nap_numboosted') . "</td>
				<td align='right'>" . wfMessage('nap_perboosted') . "</td>
				</tr>");
		while ( ($row = $dbr->fetchObject($res)) != null) {
			$user = User::newFromID($row->log_user);
			$percent = $total == 0 ? "0" : number_format($row->C / $total * 100, 0);
			$count = number_format($row->C, 0, "", ',');
			$log = Linker::link( Title::makeTitle( NS_SPECIAL, 'Log'), $count, array(), array('type' => 'nap', 'user' => $user->getName()) );
			$wgOut->addHTML("<tr>
				<td>$index</td>
				<td>" . Linker::link($user->getUserPage(), $user->getName()) . "</td>
				<td  align='right'>{$log}</td>
				<td align='right'> $percent % </td>
				</tr>
			");
			$index++;
		}
		$dbr->freeResult($res);
		$wgOut->addHTML("</table></center>");

	}

}

/**
 * AJAX server-side code for the Copyright check submission from NAB.
 */
class Copyrightchecker extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('Copyrightchecker', '', false, true);
	}

	public function execute($par) {
		global $wgRequest, $wgOut, $IP;
		$target = isset($par) ? $par : $wgRequest->getVal('target');

		if (is_null($target)) {
			$wgOut->addHTML("<b>Error:</b> No parameter passed to Copyrightchecker.");
			return;
		}

		$query = $wgRequest->getVal('query');


		$title = Title::newFromURL($target);
		$rev = Revision::newFromTitle($title);
		$wgOut->setArticleBodyOnly(true);

		if (!$query) {
			// Get the text and strip the steps header, any templates,
			// flatten it to HTML and strip the tags
			if (!$rev) {
				echo "Revision for article not found by copyright check";
				return;
			}
			$wikitext = $rev->getText();
			$wikitext = preg_replace("/^==[ ]+" . wfMessage('steps') . "[ ]+==/mix", "", $wikitext);
			$wikitext = preg_replace("/{{[^}]*}}/im", "", $wikitext);
			$wikitext = WikihowArticleEditor::textify($wikitext);
			$parts = preg_split("@\.@", $wikitext);
			shuffle($parts);
			$queries = array();
			foreach ($parts as $p) {
				$p = trim($p);
				$words = explode(" ", $p);
				if (sizeof($words) > 5) {
					if (sizeof($words) >  15) {
						$words = array_slice($words, 0, 15);
						$p = implode(" ", $words);
					}
					$queries[] = $p;
					if (sizeof($queries) == 2) {
						break;
					}
				}
			}
			$query = '"' . implode('" AND "',  $queries) . '"';
		}

		require_once(dirname(__FILE__) . '/GoogleAjaxSearch.class.php');
		$results = GoogleAjaxSearch::getGlobalWebResults($query, 8, null);

		// Filter out results from wikihow.com
		if (sizeof($results) > 0 && is_array($results)) {
			$newresults = array();
			for ($i = 0; $i < sizeof($results); $i++) {
				if (strpos($results[$i]['url'], "http://www.wikihow.com/") === 0
					|| strpos($results[$i]['url'], "http://m.wikihow.com/") === 0)
				{
					continue;
				}
				$newresults[] = $results[$i];
			}
			$results = $newresults;
		}

		// Process results
		if (sizeof($results) > 0 && is_array($results)) {
			$wgOut->addHTML(wfMessage("nap_copyrightlist", $query) . "<table width='100%'>");
			for ($i = 0; $i < 3 && $i < sizeof($results); $i++) {
				$match = $results[$i];
				$c = json_decode($match['content']);
				$wgOut->addHTML("<tr><td><a href='{$match['url']}' target='new'>{$match['title']}</a>
					<br/>$c
					<br/><font size='-2'>{$match['url']}</font></td><td style='width: 100px; text-align: right; vertical-align: top;'><a href='' onclick='return nap_copyVio(\"" . htmlspecialchars($match['url']) . "\");'>Copyvio</a></td></tr>");
			}
			$wgOut->addHTML("</table>");
		} else {
			$wgOut->addHTML(wfMessage('nap_nocopyrightfound', $query));
		}
	}
}


/**
 * AJAX server-side code for the "mark related" functionality from NAB.
 */
class Markrelated extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('Markrelated', '', false, true);
	}

	// adds a related wikihow to the article t1 to t2
	public function addRelated($t1, $t2, $summary = "Adding related wikihow from NAB", $top = false, $linkedtext = null) {

#echo "putting a link in '{$t1->getText()}' to '{$t2->getText()}'\n\n";
		if ($linkedtext)
			$link = "*[[{$t2->getText()}|" . wfMessage('howto', $linkedtext) . "]]";
		else
			$link = "*[[{$t2->getText()}|" . wfMessage('howto', $t2->getText()) . "]]";
		$article = new Article($t1);
		$wikitext = $article->getContent(true);
		for ($i = 0; $i < 30; $i++) {
			$s = $article->getSection($wikitext, $i);
			if (preg_match("@^==[ ]*" . wfMessage('relatedwikihows') . "@m", $s)) {
				if (preg_match("@{$t2->getText()}@m", $s)) {
					$found = true;
					break;
				}
				if ($top)
					$s = preg_replace("@==\n@", "==\n$link\n", $s);
				else
					$s .= "\n{$link}\n";
				$wikitext = $article->replaceSection($i, $s);
				$found = true;
				break;
			} else if (preg_match("@^==[ ]*(" . wfMessage('sources') . ")@m", $s)) {
				// we have gone too far
				$s = "\n== " . wfMessage('relatedwikihows') . " ==\n{$link}\n\n" . $s;
				$wikitext = $article->replaceSection($i, $s);
				$found = true;
				break;
			}
		}
		if (!$found) {
			$wikitext .= "\n\n== " . wfMessage('relatedwikihows') . " ==\n{$link}\n";
		}
		if (!$article->doEdit($wikitext, $summary))
			echo "Didn't save\n";
	}

	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		if ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) ) {
			$wgOut->setArticleRelated( false );
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$wgOut->disable();
		$p1 = $wgRequest->getVal('p1');
		$p2 = $wgRequest->getVal('p2');
		$t1 = Title::newFromID($p1);
		$t2 = Title::newFromID($p2);
		$this->addRelated($t1, $t2);
		$this->addRelated($t2, $t1);
	}

}

/**
 * AJAX server-side code for the "mark related" functionality from NAB.
 */
class NABClean extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('NABClean', '', false, true);
	}

	public function execute($par) {
		global $wgRequest, $wgOut, $wgMemc;
		$target = isset($par) ? $par : $wgRequest->getVal('page');
		$dbw = wfGetDB(DB_MASTER);
		$in_nab = $dbw->selectField(Newarticleboost::NAB_TABLE, 'count(*)', array('nap_page' => $target), __METHOD__);
		if ($in_nab) {
			$dbw->delete(Newarticleboost::NAB_TABLE, array('nap_page' => $target), __METHOD__);
			$wgMemc->delete( self::getNabbedCachekey($target) );
			$wgOut->addHTML('<p>Deleted from NAB.</p>');
		} else {
			$wgOut->addHTML('<p>Could not find in NAB!</p>');
		}
		$wgOut->addHTML('<p>Return to <a href="/Special:Newarticleboost">the NAB list</a>.</p>');
	}

}

/**
 * AJAX server-side code for the "nabbed" functionality from article pages.
 */
class NABPatrol extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('NABPatrol', '', false, true);
	}

	public function execute($par) {
		global $wgRequest;

		$out = $this->getContext()->getOutput();
		$user = $this->getUser();

		$out->setArticleBodyOnly(true);

		if ( !in_array( 'newarticlepatrol', $user->getRights()) && !in_array( 'sysop', $user->getGroups() ) ) {
			$result['err'] = true;
			$result['msg'] = "You do not have permission to promote this article.";
			echo json_encode($result);
			return;
		} else {
			$dbw = wfGetDB(DB_MASTER);
			$aid = $wgRequest->getVal("aid", null);
			if($aid == null) {
				$result['err'] = true;
				$result['msg'] = "No article ID provided.";
				echo json_encode($result);
				return;
			}
			if ($wgRequest->getVal("demote") == '1') {
				//DEMOTING
				$demoted = Newarticleboost::isDemotedNoDb($aid);
				if($demoted) {
					$result['err'] = true;
					$result['msg'] = "The article has already been demoted by another user.";
					echo json_encode($result);
					return;
				}
				Common::log('NABPatrol::execute - demote', $aid, Title::newFromID($aid));
				NewArticleBoost::demoteArticle($dbw, $aid, $user->getId(), false);

				$result['msg'] = wfMessage('nab_warning_demote_done')->text();

				$title = Title::newFromID($aid);
				if($title) {
					//need to log this demotion too!
					$params = array($aid);
					$log = new LogPage('nap', false);
					$log->addEntry( 'nap', $title, wfMessage('nap_logsummary_demote_admin', $title->getFullText())->text(), $params );
				}
			}
			else {
				//PROMOTING
				$patrolled = Newarticleboost::isNABbedNoDb($aid);
				if($patrolled) {
					$result['err'] = true;
					$result['msg'] = "The article has already been patrolled by another user.";
					echo json_encode($result);
					return;
				}
				Common::log('NABPatrol::execute - promote', $aid, Title::newFromID($aid));
				NewArticleBoost::markNabbed($dbw, $aid, $user->getId(), false);
				wfRunHooks("NABArticleFinished", array($aid));

				$result['msg'] = wfMessage('Nab_warning_done')->text();

				$title = Title::newFromID($aid);
				if($title) {
					//need to log this promotion too!
					$params = array($aid);
					$log = new LogPage('nap', false);
					$log->addEntry( 'nap', $title, wfMessage('nap_logsummary_admin', $title->getFullText())->text(), $params );
				}
			}

			$result['err'] = false;
			echo json_encode($result);
		}
	}

}

class NabQueryPage extends QueryPage {

	private $canNewbie;
	private $doNewbie;
	private $sortValue;
	private $sortOrder;
	private $wantLow;
	private $wantOld;

	function __construct($canNewbie, $doNewbie, $sortValue, $sortOrder, $wantLow, $doAtlasScore=true, $wantOld = false) {
		global $wgHooks;
		$this->canNewbie = $canNewbie;
		$this->doNewbie = $doNewbie;
		$this->sortValue = $sortValue;
		$this->sortOrder = $sortOrder;
		$this->wantLow = $wantLow;
		$this->doAtlasScore = $doAtlasScore;
		$this->wantOld = $wantOld;
		parent::__construct('Newarticleboost');
		$this->listoutput = false;


		$wgHooks['wgQueryPagesNoResults'][] = array("NabQueryPage::wfQueryNoResults");
	}

	function wfQueryNoResults(&$msg) {
		$msg = "nabnoresults";
		return true;
	}

	function sortDescending() {
		return $this->sortOrder == "desc";
	}

	function linkParameters() {
		return array('sortOrder' => $this->sortOrder, 'sortValue' => $this->sortValue, 'low' => $this->wantLow, 'old' => $this->wantOld);
	}

	function getName() {
		return "Newarticleboost";
	}

	function isSyndicated() { return false; }

	function getOrderFields() {
		switch ($this->sortValue) {
			case 'score':
				return array('nap_atlas_score', 'nap_page');
			case 'date':
				return array('nap_timestamp');
		}

	}

	function getSQL() {
		$req = $this->getRequest();
		if ($req && $req->getVal('scored')) {
			$score_opt = $this->doAtlasScore ? "AND nap_atlas_score >= 0" : '';
		} else {
			$six_hours_ago = wfTimestamp(TS_MW, time() - 6 * 60 * 60);
			$score_opt = $this->doAtlasScore ? "AND (nap_atlas_score >= 0 OR nap_timestamp < '$six_hours_ago')" : '';
		}
		$newbie_opt = $this->doNewbie ? 'AND nap_newbie = 1' : '';
		$low_opt = $this->wantLow ? ' AND nap_atlas_score < 30' : '';
		$old_opt = $this->wantOld ? ' AND nap_timestamp <= "' . Newarticleboost::BACKLOG_DATE . '" ' : ' AND nap_timestamp > "' . Newarticleboost::BACKLOG_DATE . '" ';

		$one_hour_ago = wfTimestamp(TS_MW, time() - 60 * 60);

		$sql = "SELECT page_namespace, page_title, nap_timestamp,
					nap_page, nap_atlas_score
				  FROM " . Newarticleboost::NAB_TABLE . ", page
					LEFT JOIN templatelinks ON tl_from = page_id
					  AND tl_title = 'Inuse'
				  WHERE page_id = nap_page
					AND page_is_redirect = 0
					AND nap_patrolled = 0
					AND nap_timestamp < '$one_hour_ago'
					AND nap_demote = 0
					AND tl_title is NULL
					{$score_opt} {$newbie_opt} {$low_opt} {$old_opt}";
		return $sql;
	}

	function getColumnHeader($param) {
		switch ($param) {
			case "page":
				return "Article";
			case "score":
				return "Score";
			case "date":
				return "Created";
		}
	}

	function openList( $offset ) {
		$html = "\n<ol start='" . ( $offset + 1 ) . "' class='special nablist section_text'>\n";
		$html .= "<li class='toprow'><span>Article</span><span>";

		$pageTarget = "/Special:Newarticleboost?sortValue=";
		$linkClass = "active {$this->sortOrder}";
		$low = $this->wantLow ? '&low=1' : '';
		$old = $this->wantOld ? '&old=1' : '';

		foreach(array('score', 'date') as $param) {
			$class = "";
			$order = "";
			if($this->sortValue == $param) {
				$class = $linkClass;
				if($this->sortOrder == "desc") {
					$order = "&sortOrder=asc";
				}
			}
			$html .= "<a class='{$class}' href='{$pageTarget}{$param}{$order}{$low}{$old}'>" . $this->getColumnHeader($param) . "</a></span><span>";
		}

		return $html;
	}

	function formatResult( $skin, $result ) {
		$title = Title::makeTitle($result->page_namespace, $result->page_title);
		$specialPage = SpecialPage::getTitleFor( 'Newarticleboost', $title->getPrefixedText() );
		if ($this->doNewbie) {
			$linkAttribs = array(
				"newbie" => 1,
				"page" => $result->nap_page);
		} else {
			$linkAttribs = array(
				"page" => $result->nap_page,
				"sortValue" => $this->sortValue,
				"sortOrder" => $this->sortOrder);
			if ($this->wantLow) $linkAttribs["low"] = 1;
			if ($this->wantOld) $linkAttribs["old"] = 1;
		}
		$nabResultLink = Linker::link($specialPage, $title->getText(), array(), $linkAttribs);
		$scoreText = $result->nap_atlas_score < 0 ? "<i>unscored</i>" : $result->nap_atlas_score;
		$html =  "<span class='link'>" . $nabResultLink . "</span>";
		$html .= "<span class='nap_score'>" . $scoreText . "</span>";
		if ($result->nap_timestamp != '') {
			$dateStr = date('n/j/Y', wfTimestamp(TS_UNIX, $result->nap_timestamp));
			$html .= "<span class='nap_date'>" . $dateStr . "</span>";
		}

		return $html;
	}

	function getList() {
		list( $limit, $offset ) = wfCheckLimits();
		$this->limit = $limit;
		$this->offset = $offset;
		$out = $this->getOutput();
		$out->addHTML("<div class='minor_section'>");
		if($this->wantOld) {
			$out->addHTML("Total left: " . Newarticleboost::getNABCountOld());
		}
		$lowUrl =  "/Special:Newarticleboost?".($this->doNewbie ? "newbie=1&":"")."sortValue={$this->sortValue}&sortOrder={$this->sortOrder}" . (!$this->wantLow?"&low=1":"") . ($this->wantOld?"&old=1":"");
		$out->addHTML("<span id='low_wrap'>Low Rated <input id='low_url' type='hidden' value='{$lowUrl}' /><input type='checkbox' id='nap_low' " . ($this->wantLow?"checked":"") . " /></span>");
		if ($this->canNewbie) {
			$btn_class = "style='margin-bottom: 10px; margin-top:10px; clear:right;' class='button secondary buttonright'";
			if ($this->doNewbie) {
				$out->addHTML("<a $btn_class href='/Special:Newarticleboost'>All articles</a>");
			} else {
				$out->addHTML("<a $btn_class href='/Special:Newarticleboost?newbie=1'>Newbie articles</a>");
			}
		}

		parent::execute('');

		$out->addHTML("</div>");

		return;
	}
}
