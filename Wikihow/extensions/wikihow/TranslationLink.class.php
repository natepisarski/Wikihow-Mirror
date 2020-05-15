<?php

/*
 ALTER TABLE `translation_link` ADD COLUMN `tl_translated` tinyint default 1;
 */

/*
 * Defines Translation Links between articles in different languages. These are stored in the
 * database in the translation_link table.
 */
class TranslationLink {

	// These actions are written to the translation_link_log table
	// Retrieve an article by name to be translated
	const ACTION_NAME = "n";
	// Save a link to to the translation_link table
	const ACTION_SAVE = "s";
	// Delete an translation link
	const ACTION_DELETE = "d";
	// Add an interwiki link based off the translation_link table
	const ACTION_INTERWIKI = "i";
	// Delete an interwiki link
	const ACTION_INTERWIKI_DELETE = "e";
	// Language and article id translated from
	public $fromLang;
	public $fromAID;
	public $fromURL;

	// Language and article id translated to
	public $toLang;
	public $toAID;
	public $toURL;

	// Status in database is unknown
	const TL_STATUS_UNKNOWN = 0;
	// Not yet added to database
	const TL_STATUS_NEW = 1;
	// Link is in database
	const TL_STATUS_SAVED = 2;
	// Link is updatable (I.E. only one side of the link is the same in the database)
	const TL_STATUS_UPDATEABLE = 3;
	// Link interferes with the link in the database
	const TL_STATUS_NON_UPDATEABLE = 4;

	// Status with regards to lang links table
	public $tlStatus;
	// Status before database is updated
	public $oldTlStatus;

	// Unkown status of interwiki links
	const IW_STATUS_UNKNOWN = -1;
	// No interwiki links in either direction
	const IW_STATUS_NONE = 0;
	// Interwiki link on from page
	const IW_STATUS_TO = 1;
	// Interwiki link on to page
	const IW_STATUS_FROM = 2;
	// Other interwiki link on from page
	const IW_STATUS_OTHER_FROM = 4;
	// Other interwiki link on to page
	const IW_STATUS_OTHER_TO = 8;

	// Whether or not the article has actually been translated yet (or just stubbed)
	public $isTranslated;

	// Status on the site (some articles are created and stubbed, so we don't really consider them to be translated
	const TL_STUBBED = 0;
	const TL_TRANSLATED = 1;

	// Translation date
	public $translationDate;

	public $iwStatus;

	public function  __construct() {
		$this->tlStatus = self::TL_STATUS_UNKNOWN;
		$this->oldTlStatus = self::TL_STATUS_UNKNOWN;
		$this->iwStatus = self::IW_STATUS_UNKNOWN;
		$this->fromLang = NULL;
		$this->toLang = NULL;
		$this->toAID = NULL;
		$this->fromAID = NULL;
		$this->isTranslated = self::TL_TRANSLATED;
	}

	// Update the translation link status, saving the old status
	private function setTlStatus($status) {
		$this->oldTlStatus = $this->tlStatus;
		$this->tlStatus = $status;
	}

	// Check that all basic fields aren't null
	private function isValid() {
		return $this->fromLang && $this->toLang && $this->toAID && $this->fromAID;
	}

	/**
	 * Get the from page from the respective URL
	 */
	public function getFromPage() {
		return Misc::fullUrlToPartial($this->fromURL);
	}

	/**
	 * Get the to page from the respective URL
	 */
	public function getToPage() {
		return Misc::fullUrlToPartial($this->toURL);
	}

	/**
	 * Updates database
	 */
	private function updateDB() {
		$dbr = wfGetDB(DB_REPLICA);
		$columns = [ 'tl_from_lang', 'tl_from_aid', 'tl_to_lang', 'tl_to_aid' ];
		$table = WH_DATABASE_NAME . '.translation_link';

		$row = $dbr->selectRow( $table,
			$columns,
			[ 'tl_from_lang' => $this->fromLang,
			  'tl_from_aid' => $this->fromAID,
			  'tl_to_lang' => $this->toLang ],
			__METHOD__ );
		$row2 = $dbr->selectRow( $table,
			$columns,
			[ 'tl_from_lang' => $this->fromLang,
			  'tl_to_lang' => $this->toLang,
			  'tl_to_aid' => $this->toAID ],
			__METHOD__ );

		// If there are other links for both the translation links
		if ($row && $row2) {
			$this->setTlStatus(self::TL_STATUS_NON_UPDATEABLE);
			return 0;
		}

		if ($row) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update( $table,
				[ 'tl_to_aid' => $this->toAID,
				  'tl_timestamp' =>  wfTimestampNow(TS_MW) ],
				[ 'tl_from_lang' => $row->tl_from_lang,
				  'tl_from_aid' => $row->tl_from_aid,
				  'tl_to_lang' => $this->toLang ],
				__METHOD__ );
			$this->setTlStatus(self::TL_STATUS_SAVED);
			return 2;
		} elseif ($row2) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update( $table,
				[ 'tl_from_aid' => $this->fromAID,
				  'tl_timestamp' =>  wfTimestampNow(TS_MW) ],
				[ 'tl_from_lang' => $row2->tl_from_lang,
				  'tl_to_lang' => $row2->tl_to_lang,
				  'tl_to_aid' => $row2->tl_to_aid ],
				__METHOD__ );
			$this->setTlStatus(self::TL_STATUS_SAVED);
			return 2;
		} else {
			$this->setTlStatus(self::TL_STATUS_NEW);
			return 1;
		}
	}

	public function insert() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->upsert(WH_DATABASE_NAME . ".translation_link",
			[
				'tl_from_lang' => $this->fromLang,
				'tl_from_aid' => (int)$this->fromAID,
				'tl_to_lang' => $this->toLang,
				'tl_to_aid' => (int)$this->toAID,
				'tl_timestamp' => wfTimestampNow(TS_MW),
				'tl_translated' => $this->isTranslated
			],
			[],
			[
				'tl_translated' => $this->isTranslated,
				'tl_timestamp' => wfTimestampNow(TS_MW)
			],
			__METHOD__
		);
		$this->setTlStatus(self::TL_STATUS_SAVED);
		return true;
	}

	public static function updateTranslationStatusComplete($aid, $toLang) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update( WH_DATABASE_NAME . ".translation_link",
			['tl_translated' => self::TL_TRANSLATED],
			[
				'tl_from_lang' => "en",
				'tl_to_lang' => $toLang,
				'tl_from_aid' => $aid
			],
			__METHOD__
		);
	}

	/**
	 * Bulk insert of translation links. If a translation link is updateable, we will do an update
	 */
	// NOTE: used in TranslationLinkOverride
	public static function batchAddTranslationLinks(&$links) {
		$dbw = wfGetDB(DB_MASTER);
		// TODO: convert this to Mediawiki Database interface
		$sql = "INSERT IGNORE INTO " .
				WH_DATABASE_NAME . ".translation_link " .
				"(tl_from_lang, tl_from_aid, tl_to_lang,tl_to_aid,tl_timestamp) " .
				"VALUES";
		$first = true;
		$updateLinks = array();
		foreach ($links as &$link) {
			if ($link->tlStatus == self::TL_STATUS_NEW) {
				if (!$first) {
					$sql .= ",";
				}
				$sql .= "(" . $dbw->addQuotes($link->fromLang) . "," . $dbw->addQuotes($link->fromAID) . "," .
					$dbw->addQuotes($link->toLang) . "," . $dbw->addQuotes($link->toAID) . "," .
					$dbw->addQuotes(wfTimestampNow(TS_MW)) . ")";
				$first = false;
			} elseif ($link->tlStatus == self::TL_STATUS_UPDATEABLE) {
				$updateLinks[] = $link;
			}
		}
		try {
			// Do query we have at least element to insert into translation link table
			if (!$first) {
				$dbw->query($sql, __METHOD__);
				foreach ($links as &$link) {
					if ($link->tlStatus == self::TL_STATUS_NEW) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					}
				}
			}
			foreach ($updateLinks as $link) {
				$link->updateDB();
			}

		}
		catch(Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Inputs an array of translation links, and set the object's tlStatus, which tells how they are in-sync or out-of-sync with the database
	 */
	// Note: used in TranslationLinkOverride
	public static function batchUpdateTLStatus(&$links) {
		$dbr = wfGetDB(DB_REPLICA);
		// TODO: convert this to Mediawiki Database interface
		$prefixSql =
				"SELECT tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid " .
				"FROM " . WH_DATABASE_NAME . ".translation_link " .
				"WHERE (tl_from_lang, tl_from_aid) IN (";
		$sql = $prefixSql;
		$first = true;
		foreach ($links as $link) {
			if ($link->isValid()) {
				if (!$first) {
					$sql .= ",";
				}
				$sql .= "(" . $dbr->addQuotes($link->fromLang) . "," . $dbr->addQuotes($link->fromAID) .  ")";
				$first = false;
			}
		}
		$lh = array();
		$rh = array();

		// Can't run query because we aren't querying for anything
		if (!$first) {
			$sql .= ")";
			$res = $dbr->query($sql, __METHOD__);
			foreach ($res as $row) {
				$lh[$row->tl_from_lang . $row->tl_from_aid . $row->tl_to_lang][] = $row->tl_to_aid;
			}
		}

		$sql = $prefixSql;
		$first = true;
		foreach ($links as $link) {
			if ($link->isValid()) {
				if (!$first) {
					$sql .= ",";
				}
				$sql .= "(" . $dbr->addQuotes($link->toLang) . "," . $dbr->addQuotes($link->toAID) .  ")";
				$first = false;
			}
		}
		if (!$first) {
			$sql .= ")";
			$res = $dbr->query($sql, __METHOD__);
			foreach ($res as $row) {
				$rh[$row->tl_from_lang . $row->tl_to_aid . $row->tl_to_lang][] = $row->tl_from_aid;
			}
		}

		foreach ($links as &$link) {
			if ($link->isValid()) {
				$lhl = $lh[$link->fromLang . $link->fromAID . $link->toLang];
				$rhl = $rh[$link->fromLang . $link->toAID . $link->toLang];

				if (isset($lhl) && isset($rhl)) {
					if (in_array($link->toAID, $lhl) || in_array($link->fromAID,$rhl)) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					} else {
						$link->setTlStatus(self::TL_STATUS_NON_UPDATEABLE);
					}
				} elseif (isset($lhl)) {
					if (in_array($link->toAID, $lhl)) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					}
					else {
						$link->setTlStatus(self::TL_STATUS_UPDATEABLE);
					}
				} elseif (isset($rhl)) {
					if (in_array($link->fromAID, $rhl)) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					} else {
						$link->setTlStatus(self::TL_STATUS_UPDATEABLE);
					}
				} else {
					$link->setTlStatus(self::TL_STATUS_NEW);
				}
			}
		}
	}

	/**
	 * Set the fromURL and toURL for a bunch of links
	 * @param List of translation links to add URLs for
	 * @param fullUrl If true, we get the full URL. If false, we get lang:title as the URL
	 * @param skipNonActive Exclude titles that aren't main namespace or are redirects
	 */
	// NOTE: used in Alfredo too.
	public static function batchPopulateURLs(&$links, $fullUrl = true, $skipNonActive = false) {
		$bl = array();
		foreach ($links as $link) {
			$bl[] = array('id' => $link->fromAID, 'lang' => $link->fromLang);
			$bl[] = array('id' => $link->toAID, 'lang' => $link->toLang);
		}
		$pages = Misc::getPagesFromLangIds($bl);
		$ll = array();
		foreach ($pages as $b) {
			if (isset($b['page_title'])
				&& (!$skipNonActive || ($b['page_namespace'] == 0 && $b['page_is_redirect'] == 0)) ) {
				if ($fullUrl) {
					$ll[$b['lang'] . $b['page_id']] = Misc::getLangBaseURL($b['lang']) . '/' . $b['page_title'];
				} else {
					$ll[$b['lang'] . $b['page_id']] = $b['lang'] . ':' . str_replace('-',' ',$b['page_title']);
				}
			}
		}
		foreach ($links as &$link) {
			if (isset($ll[$link->fromLang . $link->fromAID])) {
				$link->fromURL = $ll[$link->fromLang . $link->fromAID];
			}
			if (isset($ll[$link->toLang . $link->toAID])) {
				$link->toURL = $ll[$link->toLang . $link->toAID];
			}
		}
	}

	/**
	 * Gets all the links between two languages satisfying
	 * various query parameters
	 */
	public static function getLinks($fromLang, $toLang, $where = array()) {
		$fromPageTable = Misc::getLangDB($fromLang) . ".page";
		$toPageTable = Misc::getLangDB($toLang) . ".page";

		$dbr = wfGetDB(DB_REPLICA);
		$sql = "";
		if ( $fromLang == "en"  || $toLang == "en" ) {
			$sql = "SELECT " .
					"  tl_translated, tl_from_aid, tl_to_aid, fd.page_title AS to_title, d.page_title AS from_title " .
					"FROM " . WH_DATABASE_NAME_EN . ".translation_link tl " .
					"LEFT JOIN " . $fromPageTable . " d ON tl_from_aid = d.page_id " .
					"LEFT JOIN " . $toPageTable . " AS fd ON tl_to_aid = fd.page_id " .
					"WHERE tl_from_lang = " . $dbr->addQuotes($fromLang) .
					"  AND tl_to_lang=" . $dbr->addQuotes($toLang);
		} else {
			$sql = "SELECT " .
					"  tl.tl_to_aid AS tl_from_aid, tl.tl_translated as tl_translated, tl2.tl_to_aid, fd.page_title AS to_title, " .
					"  d.page_title as from_title " .
					"FROM " . WH_DATABASE_NAME_EN . ".translation_link tl " .
					"JOIN " . WH_DATABASE_NAME_EN . ".translation_link tl2 " .
					"  ON tl.tl_from_aid = tl2.tl_from_aid AND tl2.tl_from_lang = 'en' " .
					"LEFT JOIN " . $fromPageTable . " d ON tl.tl_to_aid = d.page_id " .
					"LEFT JOIN " . $toPageTable . " AS fd ON tl2.tl_to_aid = fd.page_id " .
					"WHERE tl.tl_to_lang = " . $dbr->addQuotes($fromLang) .
					"  AND tl.tl_from_lang='en'" .
					"  AND tl2.tl_to_lang=" . $dbr->addQuotes($toLang);
		}
		if (!empty($where)) {
			$where2 = array();
			foreach ( $where as $w ) {
				$where2[] = preg_replace("@^tl_@", "tl.tl_", $w);
			}
			$sql .= " AND " . implode(" AND ",$where2);
		}
		$res = $dbr->query($sql, __METHOD__);

		$baseURLA = Misc::getLangBaseUrl($fromLang) . '/';
		$baseURLB = Misc::getLangBaseUrl($toLang) . '/';

		$tls = array();
		foreach ($res as $row) {
			$tl = new TranslationLink();
			if ($row->from_title) {
				$tl->fromURL = $baseURLA . $row->from_title;
			}
			$tl->fromAID = $row->tl_from_aid;
			$tl->fromLang = $fromLang;
			if ($row->to_title) {
				$tl->toURL = $baseURLB . $row->to_title;
			}
			$tl->toAID = $row->tl_to_aid;
			$tl->toLang = $toLang;
			$tl->isTranslated = $row->tl_translated;

			$tls[] = $tl;
		}
		return $tls;
	}

	/**
	 * Get all the translation links that connect to a given article
	 * @param fromLang The language of the page we are finding links to/from
	 * @param fromPageId The page id of the page to get links to/from
	 * @param getTitles Get the titles associated with the links
	 * @param timeOrder Order by time
	 */
	public static function getLinksTo($fromLang, $fromPageId, $getTitles = true, $indirectLinks = false, $timeOrder = false) {
		$dbr = wfGetDB(DB_REPLICA);
		$enTrLinkTable = WH_DATABASE_NAME_EN . '.translation_link';
		$fromPageId = (int)$fromPageId;
		$safeFromLang = $dbr->addQuotes($fromLang);

		// TODO: convert this to Mediawiki Database interface
		$sql = "
		SELECT tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid, tl_translated, tl_timestamp
		  FROM {$enTrLinkTable}
		  WHERE (tl_from_lang = {$safeFromLang} AND tl_from_aid = {$fromPageId})
		    OR (tl_to_lang   = {$safeFromLang} AND tl_to_aid   = {$fromPageId})";
		if ($timeOrder) {
			$sql .= " ORDER BY tl_timestamp ASC";
		}

		$res = $dbr->query($sql, __METHOD__);
		$tls = [];
		foreach ($res as $row) {
			$tl = new TranslationLink();
			$tl->fromLang = $row->tl_from_lang;
			$tl->fromAID = $row->tl_from_aid;
			$tl->toLang = $row->tl_to_lang;
			$tl->toAID = $row->tl_to_aid;
			$tl->isTranslated = $row->tl_translated;
			$tl->translationDate = $row->tl_timestamp;
			$tls[] = $tl;
		}

		if ($fromLang != 'en' && $indirectLinks) {
			$sql = "
			SELECT tl.tl_to_lang AS tl_from_lang, tl.tl_to_aid AS tl_from_aid,
				   tl2.tl_to_lang, tl2.tl_to_aid
			 FROM {$enTrLinkTable} tl
			 JOIN {$enTrLinkTable} tl2
			   ON tl.tl_from_aid = tl2.tl_from_aid
			  AND tl.tl_from_lang = 'en'
			  AND tl2.tl_from_lang = 'en'
			  AND tl2.tl_to_lang <> tl.tl_to_lang
			WHERE tl.tl_to_lang = {$safeFromLang}
			AND tl.tl_to_aid = {$fromPageId}";
			if ($timeOrder) {
				$sql .= " order by tl2.tl_timestamp asc";
			}

			$res = $dbr->query($sql, __METHOD__);
			foreach ($res as $row) {
				$tl = new TranslationLink();
				$tl->fromLang = $row->tl_from_lang;
				$tl->fromAID = $row->tl_from_aid;
				$tl->toLang = $row->tl_to_lang;
				$tl->toAID = $row->tl_to_aid;
				$tls[] = $tl;
			}
		}

		// Check indexability of destination articles, to avoid creating "hreflang" links to deindexed ones
		$indexablePolicies = [ RobotPolicy::POLICY_DONT_CHANGE, RobotPolicy::POLICY_INDEX_FOLLOW ];
		foreach ($tls as $tl) {
			// Links from INTL to EN have "from" and "to" reversed (i.e. $tl->fromLang == 'en')
			$flip = $fromLang != 'en' && $tl->fromLang == 'en';
			$dbName = Misc::getLangDB($flip ? $tl->fromLang : $tl->toLang);
			if ( !$dbName ) {
				continue;
			}
			$pageId = wfGetDB(DB_REPLICA)->selectField(
				"{$dbName}.index_info",
				'ii_page',
				[ 'ii_page' => ($flip ? $tl->fromAID : $tl->toAID), 'ii_policy' => $indexablePolicies ],
				__METHOD__
			);
			$tl->isIndexable = (bool)$pageId;
		}
		if ($getTitles) {
			self::batchPopulateURLs($tls);
		}

		return $tls;
	}

	/**
	 * Log translater actions
	 *
	 * @param action TranslationLink::ACTION_NAME, TranslationLink::ACTION_SAVE, or TranslationLink::ACTION_INTERWIKI
	 */
	public static function writeLog(
		$action, $fromLang, $fromRevisionId, $fromAID, $fromTitleName,
		$toLang, $toTitleName, $toAID = NULL,
		$toolName = TranslateEditor::TOOL_NAME
	) {
		$user = RequestContext::getMain()->getUser();

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert( WH_DATABASE_NAME . ".translation_link_log",
			[
				'tll_from_lang' => $fromLang,
				'tll_from_aid' => $fromAID,
				'tll_from_title' => $fromTitleName,
				'tll_from_revision_id' => $fromRevisionId,
				'tll_to_lang' => $toLang,
				'tll_to_aid' => $toAID,
				'tll_to_title' => $toTitleName,
				'tll_user' => $user->getName(),
				'tll_tool' => $toolName,
				'tll_action' => $action,
				'tll_timestamp' => wfTimestampNow(TS_MW)
			],
			__METHOD__ );
	}

	/**
	 * Delete a link. Returns true if and only if it deletes something.
	 */
	// NOTE: I can't tell if this method is actually used outside this class.
	// There are too many similarly named methods.
	function delete() {
		global $wgActiveLanguages;
		$langs = $wgActiveLanguages;
		$langs[] = 'en';
		if (!$this->fromLang
			|| !$this->fromAID
			|| !$this->toLang
			|| !$this->toAID
			|| !in_array($this->fromLang, $langs)
			|| !in_array($this->toLang, $langs)
		) {
			return false;
		}

		$dbw = wfGetDB(DB_MASTER);

		$conds = [
			'tl_from_lang' => $this->fromLang,
			'tl_from_aid' => $this->fromAID,
			'tl_to_lang' => $this->toLang,
			'tl_to_aid' => $this->toAID,
		];

		$count = $dbw->selectField( WH_DATABASE_NAME . ".translation_link",
			'count(*)',
			$conds,
			__METHOD__ );
		if ($count == 0) {
			return false;
		}

		$dbw->delete( WH_DATABASE_NAME . ".translation_link",
			$conds,
			__METHOD__,
			[ 'LIMIT' => 1 ] );

		return true;
	}

	/**
	 * Hook to use interwiki links as translation links throughout the site
	 */
	public static function beforePageDisplay(OutputPage &$out, Skin &$skin) {
		$langCode = RequestContext::getMain()->getLanguage()->getCode();

		$t = $out->getTitle();
		$aid = $t->getArticleId();

		$tls = self::getLinksTo($langCode, $aid, false, true, true);

		self::batchPopulateURLs($tls, false, true);

		$lls = array();
		foreach ($tls as $tl) {
			Hooks::run('TranslationLinkAddLanguageLink', [$tl]);

			if (!$tl->isIndexable) {
				continue;
			}
			if ($tl->fromLang == $langCode && $tl->fromAID == $aid && $tl->toURL) {
				$lls[] = $tl->toURL;
			}
			elseif ($tl->toLang == $langCode && $tl->toAID == $aid && $tl->fromURL) {
				$lls[] = $tl->fromURL;
			}
		}

		if ($lls) {
			$out->setLanguageLinks($lls);
		}

		return true;
	}
}
