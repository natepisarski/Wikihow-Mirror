<?php

require_once dirname(__FILE__) . '/../commandLine.inc';

$wgUser = User::newFromName('WRM');
$dbr = wfGetDB(DB_REPLICA);
$dbw = wfGetDB(DB_MASTER);

$limit = preg_replace("@[^0-9]@", "", wfMessage("wrm_hourly_limit"));

if ($limit == 0) {
	echo "error: wrm_hourly_limit MW message needs to be set to a number\n";
	exit;
}

$res = $dbr->select( 'import_articles',
	array('ia_id', 'ia_text', 'ia_title'),
	array('ia_published' => 0),
	__FILE__,
	array("ORDER BY"=>"rand()", "LIMIT"=>$limit) );

echo "Starting: " . date("r") . ", doing $limit\n";
foreach ($res as $row) {
	$id = $row->ia_id;
	$title = Title::makeTitle(NS_MAIN, $row->ia_title);
	if (!$title) {
		echo("Couldn't make title out of {$row->ia_title} \n");
		$dbw->update('import_articles', array('ia_publish_err'=>1), array('ia_id'=>$row->ia_id));
		continue;
	}
	$wikiPage = WikiPage::factory($title);
	if ($title->getArticleID() && !$wikiPage->isRedirect()) {
		echo "Can't overwrite non-redirect article {$title->getText()}\n";
		$dbw->update('import_articles', array('ia_publish_err'=>1), array('ia_id'=>$row->ia_id));
		continue;
	}
	$content = ContentHandler::makeContent($row->ia_text, $title);
	if ($wikiPage->doEditContent($content, "Creating new article", EDIT_FORCE_BOT)) {
		// success
		echo "Published {$title->getText()}\n";
	} else {
		echo "Couldn't save {$title->getText()}\n";
		$dbw->update('import_articles', array('ia_publish_err'=>1), array('ia_id'=>$row->ia_id));
		continue;
	}
	$dbw->update('import_articles', array('ia_published' => 1, 'ia_published_timestamp' => wfTimestampNow(TS_MW)), array('ia_id'=>$id));
	$dbw->update('recentchanges', array('rc_patrolled' => 1), array('rc_user_text'=>'WRM'));
	Hooks::run("WRMArticlePublished", array($title->getArticleID()));
}
echo "Finished: " . date("r") . "\n";
