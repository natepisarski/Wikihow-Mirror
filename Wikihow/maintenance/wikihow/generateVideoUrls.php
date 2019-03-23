<?php

require_once __DIR__ . '/../commandLine.inc';
global $IP;
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

$dbr = wfGetDB( DB_REPLICA );

// Get a list of alt-domain page IDs
$domainIds = [];
if ( class_exists( 'AlternateDomain' ) && AlternateDomain::isAltDomainLang() ) {
	$domainIds = AlternateDomain::getAllPages();
}

/**
 * Converts Database Timestamp to ISO8601 Date.
 *
 * Copied from generateUrls.php
 *
 * @param string $time Database timestamp
 * @return string ISO8601 Date
 */
function iso8601_date( $time ) {
	$date = substr( $time, 0, 4 )  . "-"
		  . substr( $time, 4, 2 )  . "-"
		  . substr( $time, 6, 2 )  . "T"
		  . substr( $time, 8, 2 )  . ":"
		  . substr( $time, 10, 2 ) . ":"
		  . substr( $time, 12, 2 ) . "Z" ;
	return $date;
}

$rows = $dbr->select(
	[ 'article_meta_info', 'page' ],
	[ 'ami_summary_video', 'ami_summary_video_updated', 'page_id', 'page_title' ],
	[ 'ami_summary_video != \'\'' ],
	'generateVideoSitemap',
	[],
	[ 'page' => [ 'INNER JOIN', [ 'ami_id=page_id' ] ] ]
);

$lines = [];
foreach ( $rows as $row ) {
	// Skip alt-domain summary videos
	if ( isset( $domainIds[$row->page_id] ) ) {
		continue;
	}
	// Append relative URL and updated date
	$lines[] = '/Video/' . urlencode( $row->page_title ) .
		' lastmod=' . iso8601_date( $row->ami_summary_video_updated );
}

foreach ( $lines as $line ) {
	print "$line\n";
}
