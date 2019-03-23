<?php

# Check to make sure Titus has run recently on a somewhat reasonable number of articles

require_once(__DIR__ . '/../../commandLine.inc');
require("$IP/extensions/wikihow/titus/Titus.class.php");

global $wgActiveLanguages;

$error = "";
try {
	$issueLangs = array();
	$titus = new TitusDB();
	$langs = $wgActiveLanguages;
	$langs[] = 'en';
	foreach ( $langs as $lang ) {
		$sql = 'select count(*) as ct from titus_intl where ti_language_code="' . $lang . '" AND ti_timestamp > date_sub(now(), interval 25 hour)';
		$res = $titus->performTitusQuery($sql, 'read', __METHOD__);
		$found = false;
		foreach ( $res as $row ) {
			if ( $row->ct > 30 ) {
				$found = true;
			}
		}
		if ( !$found ) {
			$issueLangs[] = $lang;
		}
	}
	$sql = 'select count(*) as ct from titus_intl where ti_last_edit_timestamp > date_sub(now(), interval 2 day)';
	$res = $titus->performTitusQuery($sql, 'read', __METHOD__);
	$editedArticles = 0;

	foreach ( $res as $row ) {
		$editedArticles = $row->ct;
	}
} catch ( Exception $e ) {
	$error = print_r($e, true);
}
if ( $issueLangs || $editedArticles < 100 || $error ) {
	$to = new MailAddress("eng@wikihow.com");
	$from = new MailAddress("alerts@wikihow.com");
	$subject = "Errors with most recent Titus run";
	$msg = "titusSanityCheck.php has detected the following issues with Titus:";

	if ( $issueLangs ) {
		$msg .= "\nTitus appear to have not run recently in the following languages: " . print_r($issueLangs, true);
	}
	if ( $editedArticles < 100 ) {
		$msg .= "\nLess than 100 articles have been edited in the last two days according to Titus. " . $editedArticles . " have been edited";
	}
	if ( $error ) {
		$msg .= "\nException while running script titusSanityCheck.php " . $error;
	}
	UserMailer::send($to,$from, $subject, $msg);
	print wfTimestampNow() . $msg;
}
else {
	print wfTimestampNow() . ": Passed titus sanity check\n";
}
