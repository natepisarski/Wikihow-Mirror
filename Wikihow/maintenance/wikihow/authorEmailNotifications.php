<?php

require_once __DIR__ . '/../commandLine.inc';

$dbr = wfGetDB(DB_SLAVE);
$dbw = wfGetDB(DB_MASTER);

// PROCESS FA articles
$fas = FeaturedArticles::getFeaturedArticles(1, 2);
foreach ($fas as $fa) {
	$url = urldecode(preg_replace("@http://www.wikihow.com/@", "", $fa[0]));
	$t = Title::newFromURL($url);
	if (!$t) {
		echo "Can't make title";
		print_r($fa); continue;
	}
	echo "sending notification for FA for {$t->getFullText()}\n";
	AuthorEmailNotification::notifyFeatured($t);
}

// PROCESS VIEWERSHIP EMAILS
$ts = wfTimestamp(TS_MW, time()-86400);
$sql = "SELECT page_namespace, page_title, page_counter, en_viewership, en_user
		FROM email_notifications
		LEFT JOIN page ON en_page=page_id
		WHERE en_viewership_email IS NULL
			OR en_viewership_email < '{$ts}'";
$res = $dbr->query($sql, __FILE__);
$milestones = array(10000, 5000, 1000, 500, 100);
foreach ($res as $row) {
	$send = false;
	if (!$row->page_title) continue;
	if ($row->page_counter >= 10000 && $row->page_counter - $row->en_viewership >= 10000) {
		$milestone = floor($row->page_counter / 10000) * 10000;
		$send = true;
	} else {
		foreach ($milestones as $m) {
			if ($row->page_counter >= $m && $row->en_viewership < $m) {
				$milestone = $m;
				$send = true;
				break;
			}
		}
	}

	if ($send) {
		$title = Title::makeTitle($row->page_namespace, $row->page_title);
		$user = User::newFromID($row->en_user);
		$user->load();
		AuthorEmailNotification::notifyViewership($title, $user, $milestone, $milestone, null);
	}
}


