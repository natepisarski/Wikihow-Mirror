<?php

class StatsList extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'StatsList' );
	}

	function execute($par) {
		global $wgOut, $wgRequest;
		//$wgOut->setArticleBodyOnly(true);

		$startdate = '000000';
		$startdate31 = strtotime('31 days ago');
		$startdate7 = strtotime('7 days ago');
		$startdate24 = strtotime('24 hours ago');

		$starttimestamp31 = date('YmdG',$startdate31) . floor(date('i',$startdate31)/10) . '00000';
		$starttimestamp7 = date('YmdG',$startdate7) . floor(date('i',$startdate7)/10) . '00000';
		$starttimestamp24 = date('YmdG',$startdate24) . floor(date('i',$startdate24)/10) . '00000';

		$wgOut->addHtml("<table cellspacing='10'>");
		$wgOut->addHtml("<tr><td>Requests Answered (last 24 hours)</td><td>" . self::getNumRequestsAnswered($starttimestamp24) . "</td></tr>");
		$wgOut->addHtml("<tr><td>Requests Answered (7 days)</td><td>" . self::getNumRequestsAnswered($starttimestamp7) . "</td></tr>");
		$wgOut->addHtml("<tr><td>Requests Answered (last 31 days)</td><td>" . self::getNumRequestsAnswered($starttimestamp31) . "</td></tr>");

		$wgOut->addHtml("<tr><td>Spellchecker Edits (last 24 hours)</td><td>" . self::getNumSpellcheckerEdits($starttimestamp24) . "</td></tr>");
		$wgOut->addHtml("<tr><td>Spellchecker Edits (7 days)</td><td>" . self::getNumSpellcheckerEdits($starttimestamp7) . "</td></tr>");
		$wgOut->addHtml("<tr><td>Spellchecker Edits (last 31 days)</td><td>" . self::getNumSpellcheckerEdits($starttimestamp31) . "</td></tr>");

		$wgOut->addHtml("</table>");

	}

	private function getNumRequestsAnswered($starttimestamp) {
		global $wgMemc;

		$cacheKey = wfMemcKey("StatsList_requests" . $starttimestamp);
		$result = $wgMemc->get($cacheKey);
		if (is_string($result)) {
			return $result;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$starttimestamp = $dbr->addQuotes($starttimestamp);
		$sql = "SELECT count(page_title) as count ".
					"FROM firstedit left join page on fe_page = page_id left join suggested_titles on page_title= st_title " .
					"WHERE fe_timestamp >= $starttimestamp AND st_isrequest IS NOT NULL";
		$res = $dbr->query($sql);
		while($row = $dbr->fetchObject($res)){
			$wgMemc->set($cacheKey, $row->count);
			return $row->count;
		}

	}

	private function getNumSpellcheckerEdits($starttimestamp){
		global $wgMemc;

		$cacheKey = wfMemcKey("StatsListSpellchecker" . $starttimestamp);
		$result = $wgMemc->get($cacheKey);
		if(is_string($result)) {
			return $result;
		}

		$dbr = wfGetDB(DB_SLAVE);
		$count = $dbr->selectField('logging', array('count(*) as C'), array('log_type' => 'spellcheck', "log_timestamp >= '{$starttimestamp}'"),  __FUNCTION__);

		$wgMemc->set($cacheKey, $count);

		return $count;
	}

}
