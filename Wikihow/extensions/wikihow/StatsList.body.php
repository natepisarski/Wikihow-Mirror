<?php

class StatsList extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'StatsList' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		//$out->setArticleBodyOnly(true);

		$startdate = '000000';
		$startdate31 = strtotime('31 days ago');
		$startdate7 = strtotime('7 days ago');
		$startdate24 = strtotime('24 hours ago');

		$starttimestamp31 = date('YmdG',$startdate31) . floor(date('i',$startdate31)/10) . '00000';
		$starttimestamp7 = date('YmdG',$startdate7) . floor(date('i',$startdate7)/10) . '00000';
		$starttimestamp24 = date('YmdG',$startdate24) . floor(date('i',$startdate24)/10) . '00000';

		$out->addHtml("<table cellspacing='10'>");
		$out->addHtml("<tr><td>Requests Answered (last 24 hours)</td><td>" . $this->getNumRequestsAnswered($starttimestamp24) . "</td></tr>");
		$out->addHtml("<tr><td>Requests Answered (7 days)</td><td>" . $this->getNumRequestsAnswered($starttimestamp7) . "</td></tr>");
		$out->addHtml("<tr><td>Requests Answered (last 31 days)</td><td>" . $this->getNumRequestsAnswered($starttimestamp31) . "</td></tr>");

		$out->addHtml("<tr><td>Spellchecker Edits (last 24 hours)</td><td>" . $this->getNumSpellcheckerEdits($starttimestamp24) . "</td></tr>");
		$out->addHtml("<tr><td>Spellchecker Edits (7 days)</td><td>" . $this->getNumSpellcheckerEdits($starttimestamp7) . "</td></tr>");
		$out->addHtml("<tr><td>Spellchecker Edits (last 31 days)</td><td>" . $this->getNumSpellcheckerEdits($starttimestamp31) . "</td></tr>");

		$out->addHtml("</table>");
	}

	private function getNumRequestsAnswered($starttimestamp) {
		global $wgMemc;

		$cacheKey = wfMemcKey("StatsList_requests" . $starttimestamp);
		$result = $wgMemc->get($cacheKey);
		if (is_string($result)) {
			return $result;
		}
		$dbr = wfGetDB(DB_REPLICA);
		$starttimestamp = $dbr->addQuotes($starttimestamp);
		$sql = "SELECT count(page_title) AS count " .
					"FROM firstedit " .
					"LEFT JOIN page ON fe_page = page_id " .
					"LEFT JOIN suggested_titles ON page_title = st_title " .
					"WHERE fe_timestamp >= $starttimestamp AND st_isrequest IS NOT NULL " .
					"LIMIT 1";
		$res = $dbr->query($sql, __METHOD__);
		$count = 0;
		foreach ($res as $row) {
			$count = $row->count;
			break;
		}
		$wgMemc->set($cacheKey, $count);
		return $count;
	}

	private function getNumSpellcheckerEdits($starttimestamp) {
		global $wgMemc;

		$cacheKey = wfMemcKey("StatsListSpellchecker" . $starttimestamp);
		$result = $wgMemc->get($cacheKey);
		if (is_string($result)) {
			return $result;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField('logging',
			array('count(*)'),
			array('log_type' => 'spellcheck', "log_timestamp >= '{$starttimestamp}'"),
			__METHOD__);

		$wgMemc->set($cacheKey, $count);

		return $count;
	}

}
