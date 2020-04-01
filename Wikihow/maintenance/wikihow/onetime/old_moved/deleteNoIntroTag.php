<?php                                                                                                                                                                                               

require_once('commandLine.inc');
$dbw = wfGetDB(DB_MASTER);

$wgUser = User::newFromName('MiscBot');

// get first x pages. 
$pages = WikiPhoto::getAllPages($dbw);

$count = 0;
foreach ($pages as $page) {
	$rev = Revision::loadFromPageId($dbw, $page['id']);
	if ($rev) {
		$wikitext = $rev->getText();
		$intro = Wikitext::getIntro($rev->getText());
		if ( strpos($intro, "{{nointroimg}}") !== false ) {
			$intro = str_replace("{{nointroimg}}", "", $intro);

			$wikitext = Wikitext::replaceIntro($wikitext, $intro, true);
			$title = Title::newFromID($page['id']);

			print "Removing from: ".$title."\n";
			$article = new Article($title);
			$article->doEdit($wikitext, "Removing nointroimg template");

			$count++;
		}
	}
}

print "Deleted from $count articles\n";
