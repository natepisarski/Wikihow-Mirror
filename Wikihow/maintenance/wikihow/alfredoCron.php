<?php
/**
 * Written By Gershon Bialer
 *
 * Does the image transfer for a given language. Records are pulled from the
 * image_transfer_job table, and put onto the wiki pages.
 *
 * In dry run mode, it will only simulate and it won't update database tables
 * or make real edits. The queries and edits will be shown in the standard output.
 *
 * Syntax: alfredoCron.php [live]
 */

require_once(__DIR__ . "/../commandLine.inc");
global $wgIsDevServer;
if($wgIsDevServer) {
	define('WH_API_HOST', 'gershon2.doh.wikidiy.com');
	define('WH_API_PROTOCOL', 'http');
}
else {
	define('WH_API_HOST', 'alfredo.wikiknowhow.com');
	define('WH_API_PROTOCOL', 'https');
}
require_once("extensions/wikihow/alfredo/ImageTransfer.class.php");

$dryRun = ($argv[0] != "live");
if($dryRun) {
	print("Dry run in $wgLanguageCode\n");
}
else {
	print("Running live in $wgLanguageCode\n");
}
global $wgUser;
$wgUser = User::newFromName("AlfredoBot");
$dbr = wfGetDB(DB_SLAVE);

$its = ImageTransfer::getUpdatesForLang($wgLanguageCode);
$errors = array();
$successes = array();
$creators = array();

$langIds = array();
foreach($its as $it) {
	$langIds[] = array('lang' => $it->fromLang, 'id' => $it->fromAID);
	$langIds[] = array('lang' => $it->toLang, 'id' => $it->toAID);
}
//Look up URLs of ids
$pages = Misc::getPagesFromLangIds($langIds);
$lip = array();
foreach($pages as $page) {
	$lip[$page['lang']][$page['page_id']] = $page;
}
foreach($its as $it) {
	$fromPage = $lip[$it->fromLang][$it->fromAID];
	$toPage = $lip[$it->toLang][$it->toAID];
	print("Adding images to article:" . Misc::getLangBaseURL($toPage['lang']) . '/' . $toPage['page_title'] . ' (' . $it->toLang . ' ' . $it->toAID . ') based off ' . Misc::getLangBaseURL($fromPage['lang']) . '/' . $fromPage['page_title'] .  ' (' . $it->fromLang . ' ' . $it->fromAID . ")\n");
	if(!$it->addImages($dryRun)) {
		print("Failed with error:" . $it->error . "\n");
		$errors[$it->creator][] = $it;
	}
	else {
		$successes[$it->creator][] = $it;
		print("Success\n");
	}
	$creators[] = $it->creator;
}

$errorURLs = ImageTransfer::getErrorURLsByCreator($wgLanguageCode, $dryRun);

if(!empty($errorURLs)) {
	foreach($errorURLs as $creator => $urls) {
		$creators[] = $creator;
	}
}
$creators = array_unique($creators);

// Send email to each person who entered images about what happened with them
foreach($creators as $creator) {
	$user = User::newFromName($creator);
	$email = $user->getEmail();
	if($email == NULL || $email == "") {
		next;
	}
	$msg = "<table><thead><tr><td>Inputted URL</td><td>Translated URL</td><td>Error</td></tr></thead>\n";
	if(isset($errorURLs[$creator])) {
		foreach($errorURLs[$creator] as $url) {
			$msg .= "<tr><td>" . $url . "</td><td></td><td>URL Not Found</td></tr>\n";
		}
	}

	if(isset($errors[$creator])) {
		foreach($errors[$creator] as $it) {
			$fromPage = $lip[$it->fromLang][$it->fromAID];
			$toPage = $lip[$it->toLang][$it->toAID];

			$msg .= "<tr><td>" . Misc::getLangBaseURL($fromPage['lang']) . "/" . $fromPage['page_title'] . "</td><td>" . (isset($toPage['page_title']) ? (Misc::getLangBaseURL($toPage['lang']) . "/" . $toPage['page_title']) : "") . "</td><td>" . $it->error . "</td></tr>\n";
		}
	}

	if(isset($successes[$creator])) {
		foreach($successes[$creator] as $it) {
			$fromPage = $lip[$it->fromLang][$it->fromAID];
			$toPage = $lip[$it->toLang][$it->toAID];

			$msg .= "<tr><td>" . Misc::getLangBaseURL($fromPage['lang']) . "/" . $fromPage['page_title'] . "</td><td>" . Misc::getLangBaseURL($toPage['lang']) . "/" . $toPage['page_title'] . "</td><td>" . ($it->error !=''?$it->error:"Success") . "</td></tr>\n";
		}
	}
	$msg .= "</table>";
	$from = new MailAddress("alerts@wikihow.com");
	$to = new MailAddress($email);
	$subject = "Image Transfers Complete";
	$content_type = "text/html; charset=UTF-8";
	print("Sending email:\n from:alerts@wikihow.com\nto:$email\nSubject:$subject\n$msg");
	UserMailer::send($to,$from,$subject,$msg, false,$content_type);
}
