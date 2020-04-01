<?php

#define('WH_USE_BACKUP_DB', true);

require_once('commandLine.inc');

global $IP, $wgUser;
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");


$whitelist = array(
	"example.com",
	"wikihow.com",
	"wikipedia.org",
	"youtube.com",
	"wikia.com",
	"blogger.com",
	"blogspot.com",
	"about.me",
	"aboutme.com",
	"me.com",
	"mac.com",
	"tumblr.com",
	"twitter.com",
	"4chan.com",
	"uncyclopedia.org",
	"myspace.com",
	"wikiHow.com",
	"fanfiction.net",
	"deviantart.com",
	"google.com",
	"facebook.com",
	"pinterest.com",
	"posterous.com",
	"wordpress.com",
	"livejournal.com",
	"wordpress.org",
	"typepad.com",
	"wikimedia.org",
	"wiktionary.org",
	"flickr.com",
	"dailybooth.com",
	"creativecommons.com",
	"bebo.com",
	"linkedin.com",
	"freenode.net",
	"photobucket.com",
	"yahoo.com",
	"vimeo.com",
	"stumbleupon.com",
	"delicious.com",
	"ning.com",
	"digg.com",
	"reddit.com",
	"msn.com",
	"microsoft.com",
	"huffingtonpost.com",
	"answers.com",
	"live.com",
	"comcast.net",
	"aol.com",
	"examiner.com",
	"legacy.com",
	"monster.com",
	"apple.com",
	"bleacherreport.com",
	"cafemom.com",
	"yardbarker.com",
	"buzzfeed.com",
	"path.com",
	"instagram.com",
	"43things.com",
	"aviary.com",
	"backflip.com",
	"badoo.com",
	"bebo.com",
	"blogcatalog.com",
	"blurb.com",
	"buzznet.com",
	"cafemom.com",
	"current.com",
	"dailybooth.com",
	"dailymotion.com",
	"delicious.com",
	"digg.com",
	"diigo.com",
	"epinions.com",
	"esqueak.com",
	"facebook.com",
	"fark.com",
	"fotolog.com",
	"friendfeed.com",
	"friendster.com",
	"funnyordie.com",
	"gather.com",
	"gawkk.com",
	"hellotxt.com",
	"hi5.com",
	"hootsuite.com",
	"huffingtonpost.com",
	"jaiku.com",
	"last.fm",
	"livevideo.com",
	"mixx.com",
	"multiply.com",
	"myspace.com",
	"ping.fm",
	"plurk.com",
	"propeller.com",
	"reddit.com",
	"revver.com",
	"ryze.com",
	"seesmic.com",
	"sphinn.com",
	"stumbleupon.com",
	"tipjoy.com",
	"tinychat.com",
	"tribe.net",
	"tumblr.com",
	"twitter.com",
	"xanga.com",
	"yahoo.com",
	"gmail.com",
);

$wgUser = User::newFromName("MiscBot");

$maxAge = 60*60*24*14; // 2 weeks

$dbr = wfGetDB(DB_REPLICA);

echo "Checking user pages  on " . date("F j, Y") . "\n";

$articles = DatabaseHelper::batchSelect('page', array('page_id', 'page_title', 'page_counter'), array('page_namespace' => NS_USER, 'page_is_redirect' => 0),
	__METHOD__, array(), DatabaseHelper::DEFAULT_BATCH_SIZE, $dbr);

/*****
//TESTING CODE//
$res = $dbr->select('page', array('page_id', 'page_title'), array('page_namespace' => NS_USER, 'page_is_redirect' => 0, "page_title like 'A%'"), __FUNCTION__, array('LIMIT' => 100000));

echo "SQL command done.\n";
$articles = array();
while($row = $dbr->fetchObject($res)) {
	$articles[] = $row;
}
/*****/

echo "About to check " . count($articles) . " pages\n";

$i = 0;
$j = 0;
foreach($articles as $article) {
	$i++;

	//first check to see if the page in not anonymous user
	if(filter_var($article->page_title, FILTER_VALIDATE_IP) === true) {
		continue;
	}
	// Only check valid users
	$u = User::newFromName($article->page_title);
	if (!$u) {
		continue;
	}

	$u->load();
	if (!$u->getId()) {
		continue;
	}

	// Look for nonUser revisions
	$nonUserRev = $dbr->select(array('revision', 'page'),
		array('rev_timestamp'), array('rev_user_text' => $u->getName(), 'rev_page = page_id', 'page_namespace != ' . NS_USER, 'page_is_redirect' => 0), __FUNCTION__, array('ORDER' => 'rev_timestamp DESC', 'LIMIT' => '1'));
	
	if ($revision = $dbr->fetchObject($nonUserRev)) {
		// we found an edit, don't delete the user page
	} else {
		if (true || !$u->isFacebookUser()) {
			$timestamp = $dbr->selectField('revision', array('rev_timestamp'), array('rev_id' => $article->page_id));	
			if (!empty($timestamp) && oldEdit($timestamp, $maxAge)) {
				$aid = $article->page_id;
				$badLink = findBadLink($u, $aid);
				$maxViews = 15;
				if (!empty($badLink) || $article->page_counter > $maxViews) {
					//printTalkPage($aid, $timestamp, $badLink);
					deleteUserPage($article, "$timestamp\t$badLink");
					$j++;
				}
			}
		}
	}
}

echo "Finished. " . $i . " user pages.\n";
echo $j . " user pages to remove.\n";

function oldEdit(&$timestamp, $maxAge) {
	$revTime = wfTimestamp(TS_UNIX, $timestamp);
	$nowTime = wfTimestamp();
	return ($nowTime - $revTime) >= $maxAge;
}

function printTalkPage($aid, &$timestamp, &$badLink) {
	$title = Title::newFromID($aid);
	if($title) {
		$article = new Article($title);
		if($article) {
			echo $title->getFullURL() . "\t$timestamp\t$badLink\n";
		}
	}
}

function findBadLink(&$u, $userPageArticleId) {
	$links = getExternalLinks($userPageArticleId);
	$link = getProfileBoxLink($u);
	if (strlen($link)) {
		$links[] = $link;
	}

	$badLink = "";
//var_dump($links);
	foreach ($links as $link) {
		if (!isGoodLink($link)) {
			$badLink = $link;
			break;	
		}	
	}
	return $badLink;
}

function getProfileBoxLink(&$u) {
	$link = "";
	$t = Title::newFromText($u->getUserPage() . '/profilebox-occupation');
	if ($t->getArticleId() > 0) {
		$r = Revision::newFromTitle($t);
		$pbLink = trim(ContentHandler::getContentText( $r->getContent() ));
		$parsedUrl = parse_url($pbLink);
		if ($parsedUrl['host']) {
			$link = $pbLink;
		}
	}
	return $link;
}

function getExternalLinks($aid) {
	$dbr = wfGetDB(DB_REPLICA);
	$extLinks = $dbr->select(array('externallinks'), array('el_to'), array('el_from' => $aid), __FUNCTION__, array('LIMIT' => '2'));
	$links = array();
	foreach ($extLinks as $link) {
		$links[] = $link->el_to;
	}
	return $links;
}

/*
function isGoodLink(&$link) {
	global $whitelist;
	if (preg_match("@^(http://)?([^\.]+\.)?([^.]+\.[^\/$]+)(\/|$)@", $link, $matches)) {
			 return in_array($matches[3], $whitelist);
	} else {
		return false;
	}	
}
*/

function isGoodLink(&$link) {
	global $whitelist;

	$isGoodLink = false;
	foreach ($whitelist as $goodDomain) {
		if(stripos($link, $goodDomain)) {
			$goodLink = true;
			break;
		}
	}

	return $goodLink;
}

/**
 *
 * @param $article - object with the following fields (page_id and page_title)
 * @param $reason - reason for the deletion 
 */
function deleteUserPage($article, $reason) {
	$title = Title::newFromID($article->page_id);
	$profileTitles = array("/profilebox-live", "/profilebox-aboutme", "/profilebox-occupation");
	if($title) {
		$article = new Article($title);
		if($article) {
			echo $title->getFullURL() . "\t$reason\n";
			//$article->doDelete($reason);
			foreach($profileTitles as $pTitle){
				$t = Title::newFromText($title->getBaseText() . $pTitle, NS_USER);
				if($t){
					$a = new Article($t);
					if($a->exists()){
						//$success = $a->doDeleteArticle($reason);
						$success = true;
						if($success) {
							echo $t->getFullURL() . "\n";
						}
					}
				}
			}
		}


	}
}
