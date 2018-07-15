<?php

global $IP;
require_once('commandLine.inc');
//require_once("$IP/extensions/wikihow/authors/ArticleAuthors.class.php");

global $wgMemc;
$key = wfMemckey('pb-fa-list');
$wgMemc->delete($key);

//$title = Title::newFromText('wikiHow:Autoconfirmed-users');

// if ($title) {
	// print $title->getText()."\n";
	// $articleID = $title->getArticleID();
		
	// $authors = ArticleAuthors::getAuthors($articleID);
	
	// //logged out
	// $showAllLink = false;
	// $link = false;
	// $authors_hash = md5( print_r($authors, true) . print_r($showAllLink,true) . print_r($link,true));
	// $memkey = wfMemcKey('authors', $articleID, $authors_hash);
	// $wgMemc->delete($memkey);
	
	// //logged in
	// $showAllLink = true;
	// $link = true;
	// $authors_hash = md5( print_r($authors, true) . print_r($showAllLink,true) . print_r($link,true));
	// $memkey = wfMemcKey('authors', $articleID, $authors_hash);
	// $wgMemc->delete($memkey);
	
	// //dump the full author list cache
	// $memkey = ArticleAuthors::getLoadAuthorsCachekey($articleID);
	// $wgMemc->delete($memkey);
// }
print "done\n";