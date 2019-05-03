<?php

require_once dirname(__FILE__) . "/../commandLine.inc";
require_once "$IP/extensions/wikihow/common/copyscape_functions.php";

/*
CREATE TABLE `copyviocheck` (
  `cv_page` int(8) unsigned NOT NULL,
  `cv_timestamp` varchar(14) NOT NULL DEFAULT '',
  `cv_checks` int(10) unsigned DEFAULT '0',
  `cv_copyvio` tinyint(3) unsigned DEFAULT '0',
  UNIQUE KEY `cv_page` (`cv_page`)
);
*/

// ignore articles created in last hour
$cutoff = wfTimestamp(TS_MW, time() - 3600);

// the percentage of words match threshold
$threshold = 0.25;

$wgUser = User::newFromName("Copyviocheckbot");

$dbr = wfGetDB(DB_REPLICA);

$tags = array("Category", "Image");

$checkstoday = $dbr->selectField('copyviocheck', array('count(*)'), array("cv_timestamp > '" . wfTimestamp(TS_MW, time() - 24 * 3600) . "'"));
echo "Have done $checkstoday API checks in last 24 hours\n";

$limit = max(1000 - $checkstoday, 0);
if ($limit == 0) {
	echo "reached our limit, exiting\n";
}

// get all of the new pages, newest first that aren't redirects and haven't been already checked
$res = $dbr->query("SELECT * FROM recentchanges
	LEFT JOIN copyviocheck ON cv_page=rc_cur_id
	LEFT JOIN page ON rc_cur_id = page_id
	WHERE rc_namespace = 0 AND rc_new = 1 AND page_is_redirect=0  "
	. (isset($argv[0]) ? " and page_id =  " . $argv[0] : " AND cv_page is null ")
	. " ORDER BY page_id desc LIMIT $limit");

$index = 0;
$found = 0;
foreach ($res as $row) {

	if ($checkstoday > 1000) {
		echo "We have done $checkstoday in the last 24 hours, so we are going to call it a day so we don't kill copyscape.\n";
	}

	// build the title and check to see we are sane
	// rc_title stays the same when the page moves, so that's why we used page_title
	$t = Title::makeTitle($row->page_namespace, $row->page_title);
	if (!$t) {
		echo "Can't make title out of {$row->rc_title}\n";
	}
	$r = Revision::newFromTitle($t);
	if (!$r) {
		echo "Can't make title out of {$row->rc_title}\n";
	}

	// build the text to send to copyscape
	$text = ContentHandler::getContentText( $r->getContent() );

	// skip redirects, we'll get them anyway
	if (preg_match("@^#REDIRECT@m", $text)) {
		echo "{$t->getCanonicalURL()} is a redirect\n";
		continue;
	}
	// does it already have a copyvio?
	if (preg_match("@\{\{copyvio@i", $text)) {
		echo "{$t->getCanonicalURL()} has a copyvio tag already\n";
		continue;
	}

	// only focus on the steps and intro
	$sections = preg_split("@(^==[^=]*==)@m", $text, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$text = "";
	if (!preg_match("@^==@", $sections[0])) {
		// do the intro
		$text = trim(array_shift($sections)) . "\n";
	}
	while (sizeof($sections) > 0) {
		if (preg_match("@^==[ ]*" . wfMessage('steps') . "@", $sections[0])) {
			$text .= $sections[1];
			break;
		}
		array_shift($sections);
	}

	// take out category and image links
	$text = preg_replace("@^#[ ]*@m", "", $text);
	foreach ($tags as $tag) {
		$text = preg_replace("@\[\[{$tag}:[^\]]*\]\]@", "", $text);
	}

	// take out internal links
	preg_match_all("@\[\[[^\]]*\|[^\]]*\]\]@", $text, $matches);
	foreach ($matches[0] as $m) {
		$n = preg_replace("@.*\|@", "", $m);
		$n = preg_replace("@\]\]@", "", $n);
		$text = str_replace($m, $n, $text);
	}

	// do the search
	$copyviourl = null;
	$match = null;
	$results = copyscape_api_text_search_internet($text, 'ISO-8859-1', 2);
	$checkstoday++;
	if (isset($results['count']) && $results['count']) {
		$words = $results['querywords'];
		$index = 0;
		foreach ($results['result'] as $r) {
			if (!preg_match("@^https?://[a-z0-9]*.(wikihow|whstatic|youtube).com@i", $r['url'])) {
				if ($r['minwordsmatched'] / $words > $threshold) {
					// can we find a reference to us?
					$f = file_get_contents($r['url']);
					if (strpos($f, $t->getCanonicalURL()) !== false) {
						echo "Got a reference to {$t->getCanonicalURL()} on {$r['url']}\n";
						continue;
					}
					$match = number_format($r['minwordsmatched'] / $words, 2);
					echo "{$t->getCanonicalURL()}\t{$r['url']}: $words,{$r['minwordsmatched']}, $match\n";
					$copyviourl = $r['url'];
					break;
				}
			}
		}
	}

	// apply the template if we found a violation
	if ($copyviourl) {
		// grab a fresh one from the fridge in case that the api is slow
		$r = Revision::newFromTitle($t);
		$text = "{{copyviobot|" . preg_replace("@=@", "%3F", $copyviourl) . "|date=" . date("Y-m-d") . "|match={$match}}}\n" . ContentHandler::getContentText( $r->getContent() );
		$wikiPage = WikiPage::factory($t);
		$content = ContentHandler::makeContent($text, $t);
		$wikiPage->doEditContent($content, "The Copyviocheckbot has found a potential copyright violation");
		$found++;
	}

	// log it so we don't check it again
	$dbw = wfGetDB(DB_MASTER);
	$dbw->query("INSERT INTO copyviocheck VALUES ({$t->getArticleID()}, '" . wfTimestampNow() . "', 1, " . ($copyviourl == null? 0 : 1) . ")
		on DUPLICATE KEY update cv_timestamp='" . wfTimestampNow() . "', cv_checks = cv_checks + 1");

	if ($found == 10) {
//		break;
	}
	$index++;
}
