<?php
/*
 * Written By Gershon Bialer
 * Translation link between articles. These are stored in the database in the translation_link table
 *
 */
class TranslationLink {

	//These actions are written to the translation_link_log table
	//Retrieve an article by name to be translated
	const ACTION_NAME="n";
	//Save a link to to the translation_link table
	const ACTION_SAVE="s";
	//Delete an translation link
	const ACTION_DELETE="d";
	//Add an interwiki link based off the translation_link table
	const ACTION_INTERWIKI="i";
	//Delete an interwiki link
	const ACTION_INTERWIKI_DELETE="e";
	// Language and article id translated from
	public $fromLang;
	public $fromAID;
	public $fromURL;

	// Language and article id translated to
	public $toLang;
	public $toAID;
	public $toURL;

	// Status in database is unknown
	const TL_STATUS_UNKNOWN=0;
	// Not yet added to database
	const TL_STATUS_NEW=1;
	// Link is in database
	const TL_STATUS_SAVED=2;
	// Link is updatable (I.E. only one side of the link is the same in the database)
	const TL_STATUS_UPDATEABLE=3;
	// Link interferes with the link in the database
	const TL_STATUS_NON_UPDATEABLE=4;

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

	public $iwStatus;

	public function  __construct() {
		$this->tlStatus = self::TL_STATUS_UNKNOWN;
		$this->oldTlStatus = self::TL_STATUS_UNKNOWN;
		$this->iwStatus = self::IW_STATUS_UNKNOWN;
		$this->fromLang = NULL;
		$this->toLang = NULL;
		$this->toAID = NULL;
		$this->fromAID = NULL;
	}
	/**
	 * Update the translation link status, saving the old status */
	private function setTlStatus($status) {
		$this->oldTlStatus = $this->tlStatus;
		$this->tlStatus = $status;
	}
	/**
	 * Check that all basic fields aren't null */
	public function isValid() {
		return($this->fromLang != NULL && $this->toLang != NULL && $this->toAID != NULL && $this->fromAID != NULL);
	}

	/**
	 * Create a translation from a translation_link database row
	 */
	public static function newFromRow($row) {
		$tl = new TranslationLink();
		$tl->fromLang = $row->tl_from_lang;
		$tl->fromAID = $row->tl_from_aid;
		$tl->toLang = $row->tl_to_lang;
		$tl->toAID = $row->tl_to_aid;

		return($tl);
	}
	/**
	 * Get the from page from the respective URL
	 */
	public function getFromPage() {
		return(Misc::fullUrlToPartial($this->fromURL));
	}
	/**
	 * Get the to page from the respective URL
	 */
	public function getToPage() {
		return(Misc::fullUrlToPartial($this->toURL));
	}

	/**
	 * Updates database
	 */
	public function updateDB() {
		$dbr = wfGetDB(DB_SLAVE);
		$sql = "select tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid FROM " . WH_DATABASE_NAME . ".translation_link where tl_from_lang=" . $dbr->addQuotes($this->fromLang) . " AND tl_from_aid="  . $dbr->addQuotes($this->fromAID) . " AND tl_to_lang=" . $dbr->addQuotes($this->toLang);
		$res = $dbr->query($sql, __METHOD__);
		$row = $dbr->fetchObject($res);
		$sql = "select tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid FROM " . WH_DATABASE_NAME . ".translation_link where tl_from_lang=" . $dbr->addQuotes($this->fromLang) . " AND tl_to_lang=" . $dbr->addQuotes($this->toLang) . " AND tl_to_aid=" . $dbr->addQuotes($this->toAID);
		$res2 = $dbr->query($sql, __METHOD__);
		$row2 = $dbr->fetchObject($res2);
		//If there are other links for both the translation links
		if($row && $row2) {
			$this->setTlStatus(self::TL_STATUS_NON_UPDATEABLE);
			return 0;
		}
		if($row) {
			$dbw = wfGetDB(DB_MASTER);
			$sql = "update " . WH_DATABASE_NAME . ".translation_link set tl_to_aid=" . $dbw->addQuotes($this->toAID) . ", tl_timestamp=" . wfTimestampNow(TS_MW) . " where tl_from_lang=" . $dbw->addQuotes($row->tl_from_lang) . " AND tl_from_aid=" . $dbw->addQuotes($row->tl_from_aid) . " AND tl_to_lang=" . $dbw->addQuotes($this->toLang) ;
			$dbw->query($sql, __METHOD__);
			$this->setTlStatus(self::TL_STATUS_SAVED);
			return 2;
		}
		elseif($row2) {
			$dbw = wfGetDB(DB_MASTER);
			$sql = "update " . WH_DATABASE_NAME . ".translation_link set tl_from_aid=" . $dbw->addQuotes($this->fromAID) . ", tl_timestamp=" . wfTimestampNow(TS_MW) . " where tl_from_lang=" . $dbw->addQuotes($row2->tl_from_lang) . " AND tl_to_lang=" . $dbw->addQuotes($row2->tl_to_lang) . " AND tl_to_aid=" . $dbw->addQuotes($row2->tl_to_aid);
			$dbw->query($sql, __METHOD__);
			$this->setTlStatus(self::TL_STATUS_SAVED);
			return 2;
		}
		else {
			$this->setTlStatus(self::TL_STATUS_NEW);
			return 1;
		}
	}
	public function insert() {
    global $wgLanguageCode;

    $dbr = wfGetDB(DB_MASTER);
		$sql = "insert ignore into " . WH_DATABASE_NAME . ".translation_link(tl_from_lang, tl_from_aid, tl_to_lang,tl_to_aid,tl_timestamp) values(" . $dbr->addQuotes($this->fromLang) . "," . intVal($this->fromAID) . "," . $dbr->addQuotes($this->toLang) . "," . intVal($this->toAID) . "," . $dbr->addQuotes(wfTimestampNow( TS_MW))  . ") ";
		$dbr->query($sql, __METHOD__);
		$this->setTlStatus(self::TL_STATUS_SAVED);
		return true;
	}
	/**
	 * Bulk insert of translation links. If a translation link is updateable, we will do an update
	 */
	static function batchAddTranslationLinks(&$links) {
		$dbr = wfGetDB(DB_MASTER);
		$sql = "insert ignore into " . WH_DATABASE_NAME . ".translation_link(tl_from_lang, tl_from_aid, tl_to_lang,tl_to_aid,tl_timestamp) values";
		$first = true;
		$updateLinks = array();
		foreach($links as &$link) {
			if($link->tlStatus == self::TL_STATUS_NEW) {
				if(!$first) {
					$sql .= ",";
				}
				$sql .= "(" . $dbr->addQuotes($link->fromLang) . "," . $dbr->addQuotes($link->fromAID) . "," . $dbr->addQuotes($link->toLang) . "," . $dbr->addQuotes($link->toAID) . "," . $dbr->addQuotes(wfTimestampNow(TS_MW)) . ")";
				$first = false;
			}
			elseif($link->tlStatus == self::TL_STATUS_UPDATEABLE) {
				$updateLinks[] = $link;
			}
		}
		try {
			// Do query we have at least element to insert into translation link table
			if(!$first) {
				$dbr->query($sql, __METHOD__);
				foreach($links as &$link) {
					if($link->tlStatus == self::TL_STATUS_NEW) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					}
				}
			}
			foreach($updateLinks as $link) {
				$link->updateDB();
			}

		}
		catch(Exception $e) {
			return(false);
		}
		return(true);
	}
	/**
	 * Inputs an array of translation links, and set the object's tlStatus, which tells how they are in-sync or out-of-sync with the database
	 */
	static function batchUpdateTLStatus(&$links) {
		$dbr = wfGetDB(DB_SLAVE);
		$sql="select tl_from_lang,tl_from_aid,tl_to_lang,tl_to_aid from " . WH_DATABASE_NAME . ".translation_link where (tl_from_lang,tl_from_aid) in (";
		$first = true;
		foreach($links as $link) {
			if($link->isValid()) {
				if(!$first) {
					$sql .= ",";
				}
				$sql .= "(" . $dbr->addQuotes($link->fromLang) . "," . $dbr->addQuotes($link->fromAID) .  ")";
				$first = false;
			}
		}
		$lh = array();
		$rh = array();

		// Can't run query because we aren't querying for anything
		if(!$first) {
			$sql .= ")";
			$res = $dbr->query($sql, __METHOD__);
			while($row = $dbr->fetchObject($res)) {
				$lh[$row->tl_from_lang . $row->tl_from_aid . $row->tl_to_lang][] = $row->tl_to_aid;
			}
		}

		$sql="select tl_from_lang,tl_from_aid,tl_to_lang,tl_to_aid from " . WH_DATABASE_NAME . ".translation_link where (tl_to_lang,tl_to_aid) in (";
		$first = true;
		foreach($links as $link) {
			if($link->isValid()) {
				if(!$first) {
					$sql .= ",";
				}
				$sql .= "(" . $dbr->addQuotes($link->toLang) . "," . $dbr->addQuotes($link->toAID) .  ")";
				$first = false;
			}
		}
		if(!$first) {
			$sql .= ")";
			$res = $dbr->query($sql, __METHOD__);
			while($row = $dbr->fetchObject($res)) {
				$rh[$row->tl_from_lang . $row->tl_to_aid . $row->tl_to_lang][] = $row->tl_from_aid;
			}
		}

		foreach($links as &$link) {
			if($link->isValid()) {
				$lhl = $lh[$link->fromLang . $link->fromAID . $link->toLang];
				$rhl = $rh[$link->fromLang . $link->toAID . $link->toLang];

				if(isset($lhl) && isset($rhl)) {
					if(in_array($link->toAID, $lhl) || in_array($link->fromAID,$rhl)) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					} else {
						$link->setTlStatus(self::TL_STATUS_NON_UPDATEABLE);
					}
				}
				elseif(isset($lhl)) {
					if(in_array($link->toAID, $lhl)) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					}
					else {
						$link->setTlStatus(self::TL_STATUS_UPDATEABLE);
					}
				}
				elseif(isset($rhl)) {
					if(in_array($link->fromAID, $rhl)) {
						$link->setTlStatus(self::TL_STATUS_SAVED);
					}
					else {
						$link->setTlStatus(self::TL_STATUS_UPDATEABLE);
					}
				}
				else {
					$link->setTlStatus(self::TL_STATUS_NEW);
				}
			}
		}
	}
	/**
	  * Update the interwiki status on a bunch of links.
		* This tells us whether the links are on interwiki pages
		*/
	static function batchUpdateIWStatus(&$links) {
		$ll = array();
		$fromLangs = array();
		$toLangs = array();
		$iwl = array();
		$iwlf = array();
		foreach($links as &$link) {
			$ll[$link->fromLang][$link->toLang][] = $link;
		}
		$dbr = wfGetDB(DB_SLAVE);
		foreach($ll as $lang => $llfrom) {
			foreach($llfrom as $lang2 => $llinks) {
				$langDB = Misc::getLangDB($lang);
				$langDB2 = Misc::getLangDB($lang2);
				$sql = "select ll_from,ll_lang, page_id from $langDB.langlinks LEFT JOIN page on ll_title=page_title WHERE ll_lang=" . $dbr->addQuotes($lang) ." AND ll_from in (" . implode(array_map($llinks,function($l) {
					return($l->fromAID);
				}),',') . ") or page_id in (" . implode(array_map($llinks,function($l){
					return($l->toAID);
				}),',') . ")";
				$res = $dbr->query($sql, __METHOD__);
				while($row = $dbr->fetchObject($res)) {
					$iwl[$lang . $lang2 . $row->ll_from][] = $row->page_id;
					if($row->page_id != null && is_numeric($row->page_id)) {
						$iwlf[$lang2 . $lang . $row->page_id][] = $row->ll_from;
					}
				}


				$sql = "select ll_from,ll_lang, page_id from $langDB2.langlinks LEFT JOIN page on ll_title=page_title WHERE ll_lang=" . $dbr->addQuotes($lang2) . " AND ll_from in (" . implode(array_map($llinks,function($l){
					return($l->toAID);
				}),',') . ") or page_id in (" . implode(array_map($llinks,function($l){
					return($l->fromAID);
				}),',') . ")";
				$res2 = $dbr->query($sql, __METHOD__);
				while($row2 = $dbr->fetchObject($res2)) {
					$iwl[$lang . $lang2 . $row2->ll_from][] = $row2->page_id;
					if($row2->page_id != null && is_numeric($row2->page_id)) {
						$iwlf[$lang . $lang2 . $row2->page_id][] = $row->ll_from;
					}
				}
			}
		}
		$this->iwStatus = self::IW_STATUS_NONE;
		foreach($links as &$link) {
			foreach($iwlf[$link->fromLang . $link->toLang . $link->fromAID] as $iw) {
				if($link->page_id == $link->fromAID) {
					$this->iwStatus |= self::IW_STATUS_FROM;
				}
				else {
					$this->iwStatus |= self::IW_STATUS_OTHER_FROM;
				}
			}
			foreach($iwl[$link->fromLang . $link->toLang . $link->page_id] as $iw) {
				if($link->page_id == $link->toAID) {
					$this->iwStatus |= self::IW_STATUS_TO;
				}
				else {
					$this->iwStatus |= self::IW_STATUS_OTHER_TO;
				}
			}
		}
	}
	/**
	 * Get links that have been removed where one end of the link has been removed
	 *
	 */
	/*
	static function batchGetRemovedLinks($lang, $forward) {
		$dbr = wfGetDB(DB_SLAVE);
		if($forward) {
			$sql = "select otl.tl_from_aid as tl_from_aid, otl.tl_from_lang as tl_from_lang, otl.tl_to_aid as tl_to_aid, otl.tl_to_lang as tl_to_lang from " . WH_DATABASE_NAME_EN . ".old_translation_link otl LEFT JOIN " . WH_DATABASE_NAME_EN . ".translation_link tl on otl.tl_from_lang=tl.tl_from_lang AND otl.tl_from_aid=tl.tl_from_aid WHERE  tl.tl_from_lang is NULL AND otl.tl_from_lang=" . $dbr->addQuotes($lang);
		}
		else {
			$sql = "select otl.tl_from_aid as tl_from_aid, otl.tl_from_lang as tl_from_lang, otl.tl_to_aid as tl_to_aid, otl.tl_to_lang as tl_to_lang from " . WH_DATABASE_NAME_EN . ".old_translation_link otl LEFT JOIN " . WH_DATABASE_NAME_EN . ".translation_link tl on otl.tl_to_lang=tl.tl_to_lang AND otl.tl_to_aid=tl.tl_to_aid WHERE  tl.tl_to_lang is NULL AND otl.tl_to_lang=" . $dbr->addQuotes($lang);
		}
		$res = $dbr->query($sql, __METHOD__);
		$tls = array();
		foreach($res as $row) {
			$tl = new TranslationLink();
			$tl->fromAID = $row->tl_from_aid;
			$tl->fromLang = $row->tl_from_lang;
			$tl->toAID = $row->tl_to_aid;
			$tl->toLang = $row->tl_to_lang;
			$tls[] = $tl;
		}
		return($tls);
	}
	*/

	/**
	 * Set the fromURL and toURL for a bunch of links
	 * @param List of translation links to add URLs for
	 * @param fullUrl If true, we get the full URL. If false, we get lang:title as the URL
	 * @param skipNonActive Exclude titles that aren't main namespace or are redirects
	 */
	static function batchPopulateURLs(&$links, $fullUrl=true, $skipNonActive = false) {
		$bl=array();
		foreach($links as $link) {
			$bl[] = array('id' => $link->fromAID, 'lang'=>$link->fromLang);
			$bl[] = array('id' => $link->toAID, 'lang'=>$link->toLang);
		}
		$pages = Misc::getPagesFromLangIds($bl);
		$ll = array();
		foreach($pages as $b) {
			if(isset($b['page_title'])
				&& (!$skipNonActive || ($b['page_namespace'] == 0 && $b['page_is_redirect'] == 0)) ) {
				if ($fullUrl) {
					$ll[$b['lang'] . $b['page_id']] = Misc::getLangBaseURL($b['lang']) . '/' . $b['page_title'];
				} else {
					$ll[$b['lang'] . $b['page_id']] = $b['lang'] . ':' . str_replace('-',' ',$b['page_title']);
				}
			}
		}
		foreach($links as &$link) {
			if(isset($ll[$link->fromLang . $link->fromAID])) {
				$link->fromURL = $ll[$link->fromLang . $link->fromAID];
			}
			if(isset($ll[$link->toLang . $link->toAID])) {
				$link->toURL = $ll[$link->toLang . $link->toAID];
			}
		}
	}

	/**
	 * Check if removal of interwiki links has rolled out, and if so delete the interwiki links from the given article
	 * the database if the translation links have gone live for the page.
	 * @param $forward If true, we remove links from the from, if false, we remove links from the to
	 * @param $dryRun if true, we don't actually edit out the links
	 * @return array('status' => 0 if no article or not rolled out, 1 if no iw links, 2 if successful in removing links, 'linksRemoved' => number of links removed)
	 */
	function removeInterwikis($forward, $dryRun=true) {
		if ($forward) {
			$title = Title::newFromId($this->fromAID);
			$lang = $this->fromLang;
		}
		else {
			$title = Title::newFromId($this->toAID);
			$lang = $this->toLang;
		}
		$ret = array('status' => 0, 'linksRemoved' => 0);
		if (!$title || !$title->exists()) {
			return $ret;
		}
		$r = Revision::newFromTitle($title);
		if (!$r) {
			return $ret;
		}
		$txt = $r->getText();
		$txt = preg_replace("@\[\[[a-zA-Z][a-z-A-Z]:[^\]]+\]\]@","",$txt, -1, $count);
		$ret['status'] = 1;
		$ret['linksRemoved'] = $count;
		if ($count == 0) {
			return $ret;
		}
		$ret['status'] = 2;
		$article = new Article($title);
		if (!$dryRun) {
			$article->doEdit($txt, wfMessage('removeiw-editsummary')->plain());
		}
		return $ret;
	}
	/**
	 * Add interwiki links between page A, and page B
	 * @param forward Do a forward link? Otherwise, we do a link from toAID to fromAID
	 * @param dryRun Run through adding the links without actually adding the links. Instead, just output as if we added the links.
	 * @return array('status'=>,'dup'=>) Status is 0 unable to add, 1 already added, 2 Overrode other links in same language  3 add successfully. dup is an array of other links to the same language, which are overriden if dryRun is set to to false
	 */
	function addLink($forward, $dryRun=true) {
		global $wgLanguageCode;

		$ret = array('status'=>0,'dup'=>array());
		// Make sure we are adding a link between working URLs
		if(intVal($this->fromAID) <= 0 || intVal($this->toAID) <= 0) {
			return $ret;
		}
		if($this->fromURL == NULL || $this->toURL == NULL ) {
			return $ret;
		}
		if($forward) {
			$fromURL = urldecode($this->fromURL);
			$toURL = urldecode($this->toURL);
			$fromAID = $this->fromAID;
			$toAID = $this->toAID;
			$fromLang = $this->fromLang;
			$toLang = $this->toLang;
		}
		else {
			$fromURL = urldecode($this->toURL);
			$toURL = urldecode($this->fromURL);
			$toAID = $this->fromAID;
			$fromAID = $this->toAID;
			$fromLang = $this->toLang;
			$toLang = $this->fromLang;
		}
		// We can only add links for our language code
		if(!preg_match('@' . preg_quote(Misc::getLangBaseURL($wgLanguageCode),"@") . '/(.+)@',$fromURL, $matches)) {
			return $ret;
		}
		$fromPage = Misc::fullUrlToPartial($fromURL);
		$toPage = Misc::fullUrlToPartial($toURL);

		$fromTitle = Title::newFromId($fromAID);
		if(!$fromTitle || $fromTitle->getNamespace() != NS_MAIN) {
			return($ret);
		}
		$r = Revision::newFromTitle($fromTitle);
		if(!$r) {
			return $ret;
		}
		$text = $r->getText();
		$linkText="\n[[" . $toLang . ":" . str_replace("-"," ",$toPage) . "]]";
		$linkTextRE="\[\[" . $toLang . ":(?:" . preg_quote($toPage,"/") . "|" . preg_quote(urlencode($toPage),"/") . ")\]\]";
		$linkTextRE=str_replace("\-","[ -]",$linkTextRE);
		//Duplicate
		if(preg_match("/" . $linkTextRE . "/", $text, $matches)) {
			$ret['status'] = 1;
			return($ret);
		}
		// If other links to the same language, replace them all
		elseif(preg_match_all("/\[\[" . $toLang . ":[^\]]+\]\]/i",$text, $matches)) {
			$ret['status'] = 2;
			$ret['dup'] = $matches[0];
			foreach($matches[0] as $match) {
				$text=preg_replace("@[\r\n]*" . preg_quote($match) . "@","",$text);
				$text=str_replace($match,"",$text);
			}
		}
		else {
			$ret['status'] = 3;
		}
		$text .= $linkText;
		$article = new Article($fromTitle);

		if(!$dryRun) {
			$article->doEdit($text, wfMessage('addll-editsummary'));
			self::writeLog(self::ACTION_SAVE, $fromLang, $r->getId(), $fromAID, $fromPage,$toLang,$toPage,$toAID,"interwiki");
		}
		return $ret;
	}
	/**
	 * Remove a translation link from interwiki page
	 * @return True on successfully deleting links, and false otherwise
	 */
	public function removeLink($forward, $dryRun = true) {
		global $wgLanguageCode;
		if($forward) {
			$fromURL = urldecode($this->fromURL);
			$toURL = urldecode($this->toURL);
			$fromAID = $this->fromAID;
			$toAID = $this->toAID;
			$fromLang = $this->fromLang;
			$toLang = $this->toLang;
		}
		else {
			$fromURL = urldecode($this->toURL);
			$toURL = urldecode($this->fromURL);
			$toAID = $this->fromAID;
			$fromAID = $this->toAID;
			$fromLang = $this->toLang;
			$toLang = $this->fromLang;
		}
		// Make sure we are adding a link between working URLs
		if($fromAID <= 0 || $toLang == NULL) {
			return(false);
		}
		// We can only add links for our language code
		if($wgLanguageCode != $fromLang) {
			return false;
		}
		$fromPage = Misc::fullUrlToPartial($fromURL);
		$toPage = Misc::fullUrlToPartial($toURL);

		$fromTitle = Title::newFromId($fromAID);
		if ( !$fromTitle || !$fromTitle->exists() || $fromTitle->isRedirect() ) {
			return false;
		}
		$r = Revision::newFromTitle($fromTitle);
		if(!$r) {
			return false;
		}
		$article =new Article($fromTitle);
		$text = $r->getText();
		$linkText="[[" . $toLang . ":" . $toPage . "]]";
		$linkTextRE="\[\[" . $toLang . ":(?:" . preg_quote($toPage,"/") . "|" . preg_quote(urlencode($toPage),"/") . ")\]\]";
		$linkTextRE=str_replace("\-","[ -]",$linkTextRE);

		// If other links to the same language, replace them all
		if(preg_match_all("/\[\[" . $toLang . ":[^\]]+\]\]/",$text, $matches)) {
			$ret['status'] = 2;
			$ret['dup'] = $matches[0];
			foreach($matches[0] as $match) {
				$text=preg_replace("@[\r\n]*" . preg_quote($match) . "@","",$text);
				$text=str_replace($match,"",$text);
			}
			if(!$dryRun) {
				$article->doEdit($text, wfMessage('removell-editsummary'));
				self::writeLog(self::ACTION_INTERWIKI_DELETE, $fromLang, $r->getId(), $fromAID, $fromPage,$toLang,$toPage,$toAID,"interwiki");
			}
		}
		return true;
	}
	/**
	 * Gets all the links between two languages satisfying
	 * various query parameters
	 */
	static function getLinks($fromLang, $toLang, $where=array()) {
		$fromPageTable = Misc::getLangDB($fromLang) . ".page";
		$toPageTable = Misc::getLangDB($toLang) . ".page";

		$dbr = wfGetDB(DB_SLAVE);
		$sql = "";
		if ( $fromLang == "en"  || $toLang == "en" ) {
			$sql = "select tl_from_aid, tl_to_aid, fd.page_title as to_title, d.page_title as from_title FROM " . WH_DATABASE_NAME_EN . ".translation_link tl LEFT JOIN " . $fromPageTable . " d on tl_from_aid=d.page_id LEFT JOIN " . $toPageTable . " as fd on tl_to_aid = fd.page_id WHERE tl_from_lang=" . $dbr->addQuotes($fromLang) . " AND tl_to_lang=" . $dbr->addQuotes($toLang) ;
		}
		else {
			$sql = "select tl.tl_to_aid as tl_from_aid, tl2.tl_to_aid, fd.page_title as to_title, d.page_title as from_title FROM " . WH_DATABASE_NAME_EN . ".translation_link tl join " . WH_DATABASE_NAME_EN . ".translation_link tl2 on tl.tl_from_aid=tl2.tl_from_aid and tl2.tl_from_lang='en' LEFT JOIN " . $fromPageTable . " d on tl.tl_to_aid=d.page_id LEFT JOIN " . $toPageTable . " as fd on tl2.tl_to_aid = fd.page_id WHERE tl.tl_to_lang=" . $dbr->addQuotes($fromLang) . " AND tl.tl_from_lang='en' and tl2.tl_to_lang=" . $dbr->addQuotes($toLang) ;
		}
		if(!empty($where)) {
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
		foreach($res as $row) {
			$tl = new TranslationLink();
			if($row->from_title != null) {
				$tl->fromURL = $baseURLA . $row->from_title;
			}
			$tl->fromAID = $row->tl_from_aid;
			$tl->fromLang = $fromLang;
			if($row->to_title != null) {
				$tl->toURL = $baseURLB . $row->to_title;
			}
			$tl->toAID = $row->tl_to_aid;
			$tl->toLang = $toLang;

			$tls[] = $tl;
		}
		return($tls);
	}

	/**
	 * Get all the translation links that connect to a given article
	 * @param fromLang The language of the page we are finding links to/from
	 * @param fromPageId The page id of the page to get links to/from
	 * @param getTitles Get the titles associated with the links
	 * @param timeOrder Order by time
     */
	static function getLinksTo($fromLang, $fromPageId, $getTitles = true, $indirectLinks = false, $timeOrder = false) {
		$dbr = wfGetDB(DB_SLAVE);
		$enTrLinkTable = WH_DATABASE_NAME_EN . '.translation_link';
		$fromPageId = (int) $fromPageId;
		$safeFromLang = $dbr->addQuotes($fromLang);

		$sql = "
		SELECT tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid
		  FROM {$enTrLinkTable}
		 WHERE (tl_from_lang = {$safeFromLang} AND tl_from_aid = {$fromPageId})
		    OR (tl_to_lang   = {$safeFromLang} AND tl_to_aid   = {$fromPageId})";
		if ($timeOrder) {
			$sql .= " order by tl_timestamp asc";
		}
		$res = $dbr->query($sql, __METHOD__);
		$tls = [];
		foreach ($res as $row) {
			$tl = new TranslationLink();
			$tl->fromLang = $row->tl_from_lang;
			$tl->fromAID = $row->tl_from_aid;
			$tl->toLang = $row->tl_to_lang;
			$tl->toAID = $row->tl_to_aid;
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
			$pageId = wfGetDB(DB_SLAVE)->selectField("{$dbName}.index_info", 'ii_page',
				[ 'ii_page' => ($flip ? $tl->fromAID : $tl->toAID), 'ii_policy' => $indexablePolicies ]
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

	 * @param action TranslationLink::ACTION_NAME, TranslationLink::ACTION_SAVE, or TranslationLink::ACTION_INTERWIKI
	 */
	static function writeLog($action, $fromLang,$fromRevisionId, $fromAID, $fromTitleName,$toLang, $toTitleName, $toAID = NULL, $toolName=TranslateEditor::TOOL_NAME) {
		global $wgLanguageCode, $wgUser;
		$dbr = wfGetDB(DB_MASTER);
		$query = "insert into " . WH_DATABASE_NAME . ".translation_link_log(tll_from_lang, tll_from_aid, tll_from_title, tll_from_revision_id, tll_to_lang, tll_to_aid, tll_to_title, tll_user, tll_tool, tll_action, tll_timestamp) values(" . $dbr->addQuotes($fromLang) . "," . $dbr->addQuotes($fromAID) . "," . $dbr->addQuotes($fromTitleName) . "," . $dbr->addQuotes($fromRevisionId) . "," . $dbr->addQuotes($toLang) . "," . $dbr->addQuotes($toAID) . "," . $dbr->addQuotes($toTitleName) . "," . $dbr->addQuotes($wgUser->getName()) . "," . $dbr->addQuotes($toolName) . "," . $dbr->addQuotes($action) . "," . $dbr->addQuotes(wfTimestampNow( TS_MW)) . ")";
		$dbr->query($query, __METHOD__);
	}
	/**
	 * Delete a link
	 */
	function delete() {
		global $wgActiveLanguages;
		$langs = $wgActiveLanguages;
		$langs[] = 'en';
		if($this->fromLang == NULL || $this->fromAID == NULL || $this->toLang == NULL || $this->toAID == NULL || !in_array($this->fromLang, $langs) || !in_array($this->toLang, $langs)) {
			return false;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$query = "select * from " . WH_DATABASE_NAME . ".translation_link where tl_from_lang=" . $dbr->addQuotes($this->fromLang) . " AND tl_from_aid=" . $dbr->addQuotes($this->fromAID) . " AND tl_to_lang=" . $dbr->addQuotes($this->toLang) . " AND tl_to_aid=" . $dbr->addQuotes($this->toAID);
		$res = $dbr->query($query, __METHOD__);
		if(!$dbr->fetchObject($res)) {
			return false;
		}
		$dbw = wfGetDB(DB_MASTER);
		$query = "delete from " . WH_DATABASE_NAME . ".translation_link where tl_from_lang=" . $dbw->addQuotes($this->fromLang) . " AND tl_from_aid=" . $dbw->addQuotes($this->fromAID) . " AND tl_to_lang=" . $dbw->addQuotes($this->toLang) . " AND tl_to_aid=" . $dbw->addQuotes($this->toAID) . " LIMIT 1";
		$dbw->query($query, __METHOD__);
		return true;
	}

	/**
	 * Hook to use interwiki links as translation links throughout the site
	 */
	public static function beforePageDisplay(OutputPage &$out, Skin &$skin) {
		global $wgLanguageCode, $wgTitle;

		$profiler2 = new ProfileSection('TranslationLink::beforePageDisplay-rollout');
		unset($profiler2);

		$profiler2 = new ProfileSection('TranslationLink::beforePageDisplay-getTitle');
		$t = $out->getTitle();
		$aid = $t->getArticleId();
		unset($profiler2);
		$profiler2 = new ProfileSection('TranslationLink::beforePageDisplay-getLinks');
		$tls = self::getLinksTo($wgLanguageCode, $aid, false, true, true);
		unset($profiler2);
		$profiler2 = new ProfileSection('TranslationLink::beforePageDisplay-populateURLs');
		self::batchPopulateURLs($tls, false, true);
		unset($profiler2);
		$profiler2 = new ProfileSection('TranslationLink::beforePageDisplay-setArray');
		$lls = array();
		foreach ($tls as $tl) {
			wfRunHooks('TranslationLinkAddLanguageLink', [$tl]);

			if (!$tl->isIndexable) {
				continue;
			}
			if ($tl->fromLang == $wgLanguageCode && $tl->fromAID == $aid && $tl->toURL) {
				$lls[] = $tl->toURL;
			}
			elseif ($tl->toLang == $wgLanguageCode && $tl->toAID == $aid && $tl->fromURL) {
				$lls[] = $tl->fromURL;
			}
		}
		unset($profiler2);
		$profiler2 = new ProfileSection('TranslationLink::beforePageDisplay-setLL');
		if ($lls) {
			$out->setLanguageLinks($lls);
		}
		unset($profiler2);

		return true;
	}
}
