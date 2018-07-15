<?php
// Does a purge of the cache over time
// Written By Gershon Bialer

// How many articles at once to purge
$ARTICLES_AT_ONCE = 10000;
// How long to pause between purges
$PAUSE_TIME = 60;

require_once( 'commandLine.inc' );
//require_once( "$IP/extensions/wikihow/WikiPhoto.class.php" );

print "WARNING: this script can take almost a full day to run. exiting...\n";
exit;

function purgeTitles($titles) {
	foreach ($titles as $title) {
		print $title->getText() . "\n";
		$title->purgeSquid();
	}
}

$dbr = wfGetDB( DB_SLAVE );

// get all pages
$pages = WikiPhoto::getAllPages( $dbr );

$n = 0;
$titles = array();

$start = microtime(true);
foreach ( $pages as $page ) {
	$n++;
	$title = Title::newFromDBkey($page['key']);
	if ($title) $titles[] = $title;

	if ( ( $n % $ARTICLES_AT_ONCE ) == 0 ) {
		purgeTitles( $titles );
		$titles = array();
		print "Sleeping $PAUSE_TIME seconds ...\n";
		sleep( $PAUSE_TIME );
	}
}
purgeTitles( $titles );
print "Purged $n articles in " . sprintf('%.2f', microtime(true) - $start) . "s\n";
