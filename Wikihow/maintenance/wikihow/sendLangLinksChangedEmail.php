<?php
/**
 * Send an email about language links, where one or more of the articles has been moved,
 * deleted, or redirected.
 */

require_once __DIR__ . '/../commandLine.inc';

/**
 * Get the textual representation of the page for the email
 * We will use the page title if available, and the page id
 */
function textFromPage($page) {
	if (isset($page['page_title']) && isset($page['lang'])) {
		return Misc::getLangBaseUrl($page['lang']) . '/' . $page['page_title'] . " (" . $page['page_id'] . ")";
	} else {
		return $page['lang'] . $page['page_id'];
	}
}

$dbh = wfGetDB(DB_SLAVE);
$today = date('Ymd');
$oneDayAgo = strtotime( "-1 day", strtotime($today) );
$lowDate = wfTimestamp(TS_MW, $oneDayAgo);
$moved = "";
$redirected = "";
$deleted = "";
global $wgIsDevServer;
if ($wgIsDevServer) {
	$allLangs = array("en","es");
} else {
	$allLangs = $wgActiveLanguages;
	$allLangs[] = "en";
}

foreach ($allLangs as $lang) {
	$langDB = Misc::getLangDB($lang);

	//
	// MOVE_TYPE
	//
	$sql = "SELECT tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid
			FROM " . $langDB . ".daily_edits de
			JOIN wikidb_112.translation_link tl
				ON (tl.tl_from_aid=de.de_page_id AND tl_from_lang=" . $dbh->addQuotes($lang) . ")
					OR (tl.tl_to_lang=" . $dbh->addQuotes($lang) . " AND tl.tl_to_aid=de.de_page_id)
			WHERE de_timestamp > " . $dbh->addQuotes($lowDate) .  " AND de_edit_type=" . DailyEdits::MOVE_TYPE;
	$res = $dbh->query($sql, __FILE__);

	$fromIds = array();
	$toIds = array();
	foreach ($res as $row) {
		$fromIds[] = array('lang'=> $row->tl_from_lang, 'id' => $row->tl_from_aid);
		$toIds[] = array('lang'=> $row->tl_to_lang, 'id' => $row->tl_to_aid);
	}

	if (count($fromIds) > 0) {
		$fromPages = Misc::getPagesFromLangIds($fromIds);
		$toPages = Misc::getPagesFromLangIds($toIds);

		$n = 0;
		while ($n < count($fromPages)) {
			$fromPages[$n]->page_id;
			$moved .= textFromPage($fromPages[$n]) . " to " . textFromPage($toPages[$n]) . "\n ";

			$n += 1;
		}
	}

	//
	// DELETE_TYPE
	//
	$sql = "SELECT tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid
			FROM " . $langDB . ".daily_edits de
			JOIN wikidb_112.translation_link tl
				ON (tl.tl_from_aid=de.de_page_id AND tl_from_lang='$lang')
					OR (tl.tl_to_lang='$lang' AND tl.tl_to_aid=de.de_page_id)
			WHERE de_timestamp > '$lowDate' AND de_edit_type=" . DailyEdits::DELETE_TYPE;
	$res = $dbh->query($sql, __FILE__);

	$fromIds = array();
	$toIds = array();
	foreach ($res as $row) {
		$fromIds[] = array('lang'=> $row->tl_from_lang, 'id' => $row->tl_from_aid);
		$toIds[] = array('lang'=> $row->tl_to_lang, 'id' => $row->tl_to_aid);
	}

	if (count($fromIds) > 0 ) {
		$fromPages = Misc::getPagesFromLangIds($fromIds);
		$toPages = Misc::getPagesFromLangIds($toIds);

		$n = 0;
		while ($n < count($fromPages)) {
			$deleted .= textFromPage($fromPages[$n]) . " to " . textFromPage($toPages[$n]) . "\n ";
			$n += 1;
		}
	}

	//
	// EDIT_TYPE
	//
	$sql = "SELECT tl_from_lang, tl_from_aid, tl_to_lang, tl_to_aid
			FROM " . $langDB . ".daily_edits de
			JOIN wikidb_112.translation_link tl
				ON (tl.tl_from_aid=de.de_page_id AND tl_from_lang='$lang')
					OR (tl.tl_to_lang='$lang' AND tl.tl_to_aid=de.de_page_id)
			JOIN " . $langDB . ".page p on p.page_id=de.de_page_id
			WHERE de_timestamp > '$lowDate' AND p.page_is_redirect=1 AND de_edit_type=" . DailyEdits::EDIT_TYPE;
	$res = $dbh->query($sql, __FILE__);

	$fromIds = array();
	$toIds = array();
	foreach ($res as $row) {
		$fromIds[] = array('lang'=> $row->tl_from_lang, 'id' => $row->tl_from_aid);
		$toIds[] = array('lang'=> $row->tl_to_lang, 'id' => $row->tl_to_aid);
	}

	if (count($fromIds) > 0 ) {
		$fromPages = Misc::getPagesFromLangIds($fromIds);
		$toPages = Misc::getPagesFromLangIds($toIds);

		$n = 0;
		while ($n < count($fromPages)) {
			$redirected .= textFromPage($fromPages[$n]) . " to " . textFromPage($toPages[$n]) . "\n ";

			$n += 1;
		}
	}
}

// Send report
if ($moved || $deleted || $redirected) {
	$msg = substr($lowDate,0,4) . "-" . substr($lowDate,4,2) . "-" . substr($lowDate,6,2)  . " to " . substr($today,0,4) . "-" . substr($today,4,2) . "-" . substr($today,6,2) .  "\n ";
	if ($redirected) {
		$msg .= "\nOne or both articles in each of the following translation links were redirected:\n " . $redirected;
	}

	if ($moved) {
		$msg .= "\nOne or both articles in each of the following translation links were moved:\n " . $moved;
	}

	if ($deleted) {
		$msg .= "\nOne or both articles in each of the following translation links were deleted:\n " . $deleted;
	}
	$subject = "Translation Links: Moved/redirected/deleted articles";
	$from = new MailAddress("reports@wikihow.com");
	global $wgIsDevServer;
	if ($wgIsDevServer) {
		$to = new MailAddress("YOURNAME@wikihow.com");
	} else {
		$to = new MailAddress("international@wikihow.com, chris@wikihow.com, elizabeth@wikihow.com");
	}
	print "Sending email to to:$to\nfrom:$from\nsubject:$subject\n$msg\n ";
	UserMailer::send($to, $from,$subject, $msg);
}

