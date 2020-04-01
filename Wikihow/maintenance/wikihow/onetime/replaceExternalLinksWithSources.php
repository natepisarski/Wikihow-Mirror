<?php
require_once( "commandLine.inc" );

$wgUser->setID(1236204);
$dbr = wfGetDB( DB_REPLICA );
$dbw = wfGetDB( DB_MASTER );
$res = $dbr->select('page',
		array( 'page_title', 'page_namespace'),
		array ('page_is_redirect' => 0
			//, 'page_id=184078'
		),
		"findInlineImages"
		);
$count = 0;
foreach ($res as $row) {
	$title = Title::makeTitle( $row->page_namespace, $row->page_title );
	$wgTitle = $title;
	$revision = Revision::newFromTitle($title);
	$text = ContentHandler::getContentText( $revision->getContent() );
	if (preg_match('/^==[ ]*' . wfMessage('externallinks') . '[ ]*==/im', $text) ) {
		$text = preg_replace('/^==[ ]*' . wfMessage('externallinks') . '[ ]*==/im', '== ' . wfMessage('sources') . ' ==', $text);
		$a = new Article($title);
		$a->updateArticle($text, "Changing External Links to Sources and Citations", false, false);
		echo "{$title->getFullURL()} updated\n";
		if ($count % 50 == 0) {
			echo "updating recentchanges to mark it all as patrolled...\n";
			$dbw->query("update recentchanges set rc_patrolled=1 where rc_user=1236204");
		}
		$count++;
		//exit;
	} else {
		//echo "no matches?\n";
	}
}
echo "found $count matching\n";
