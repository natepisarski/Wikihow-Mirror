<?php
/**
 * Script for periodic off-peak updating of the search index
 *
 * Usage: php updateSearchResultsSupplementGSA.php [-s START] [-e END] [-p POSFILE] [-l LOCKTIME] [-q]
 * Where START is the starting timestamp
 * END is the ending timestamp
 * POSFILE is a file to load timestamps from and save them to, searchUpdate.pos by default
 * LOCKTIME is how long the searchindex and cur tables will be locked for
 * -q means quiet
 *
 * @addtogroup Maintenance
 */

/** */
$optionsWithArgs = array( 's', 'e', 'p' );

require_once dirname(__FILE__) . '/../commandLine.inc';
require_once dirname(__FILE__) . '/updateSearchResultsSupplement.inc.php';

if ( isset($options['p']) ) {
	$posFile = $options['p'];
} else {
	$scriptDir = dirname( realpath(__FILE__) );
	$posFile = $scriptDir . '/searchResultsUpdate.pos';
}

global $wgLanguageCode;
$posFile .= ".$wgLanguageCode";

if ( isset($options['e']) ) {
	$end = $options['e'];
} else {
	$end = wfTimestampNow();
}

if ( isset($options['s']) ) {
	$start = intval($options['s']);
} else {
	if (!file_exists($posFile) || !is_writeable($posFile)) {
		// when the param is not specified explicitly, this causes load problems
		// when $posFile cannot be written and always starts at 0!
		print "Error: you must specify either -s or $posFile must be writable!\n";
		exit;
	}

	$start = file_get_contents($posFile);
	$start = intval($start);
}

$quiet = (bool)(@$options['q']);

updateSearchResultsSupplement($start, $end, $quiet);

$result = @file_put_contents($posFile, "$end");
if ($result === false) {
	$subject = "ERROR: unable to write date to $posFile";
	print "$subject\n";

	$to = new MailAddress( WH_ALERTS_EMAIL );
	$sender = new MailAddress( WH_ALERTS_EMAIL );
	$body = "$subject\n\nSent from " . gethostname() . " in " . __FILE__ . "\n";
	UserMailer::send( $to, $sender, $subject, $body );
}

