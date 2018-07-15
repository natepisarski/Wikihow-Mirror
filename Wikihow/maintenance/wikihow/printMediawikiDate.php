<?php
/**
 * Take in a unix timestamp, output a mediawiki-style date
 */

require_once __DIR__ . '/../Maintenance.php';

class PrintMediawikiDate extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->addOption('unix', 'The unix timestamp (# seconds since Jan 1, 1970) you want to print', false, true);
        $this->addOption('human', 'A string describing the date to strtotime(), like "2 days ago"', false, true);
    }

    public function execute() {
		$unix = $this->getOption('unix');
		$dateString = $this->getOption('human');

		if ((int)$unix > 0) {
			$time = (int)$unix;
		} else {
			$time = strtotime($dateString);
			if ($time === false) {
				print "error: specify either a unix timestamp or a human date that can be understood by strtotime()\n";
				return;
			}
		}
		print wfTimestamp(TS_MW, $time) . "\n";
    }

}

$maintClass = 'PrintMediawikiDate';
require_once RUN_MAINTENANCE_IF_MAIN;
