<?php

/**
 * grab the latest diffs for the past n days
 * Syntax: php getLatestDiffs.php [-n number of days to go back ]
 **/

require_once __DIR__ . '/../../Maintenance.php';

class getLatestDiffs extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Grab the latest diffs for the past n days";
		$this->addOption('numOfDays', 'Number of days to go back', true, true, 'n');
	}

	public function execute() {
		$csv_rows = [];

		//get all the pages that have been edited recently
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select(
			'page',
			[
				'page_id',
				'page_title'
			],
			[
				'page_touched >= DATE_SUB(CURDATE(), INTERVAL '.$this->getOption("numOfDays").' DAY)',
				'page_touched <= CURDATE()',
				'page_namespace' => NS_MAIN
			],
			__METHOD__,
			[
				'ORDER BY' => 'page_touched DESC'
			]
		);

		foreach($res as $row) {
			$revs = self::getRevs($dbr, $row->page_id);
			$csv_row = self::formatLine($revs, $row->page_title);
			if (!empty($csv_row)) $csv_rows[] = $csv_row;
		}

		self::makeFile($csv_rows);
	}


	private static function getRevs($dbr, $page_id) {
		$revs = [];

		$res = $dbr->select(
			'revision',
			[
				'rev_id',
				'rev_user_text'
			],
			[ 'rev_page' => $page_id ],
			__METHOD__,
			[
				'ORDER BY' => 'rev_timestamp DESC',
				'LIMIT' => 2
			]
		);

		foreach ($res as $row) {
			$revs[$row->rev_id] = $row->rev_user_text;
		}

		return $revs;
	}

	private static function formatLine($revs, $page_title) {
		$ids = array_keys($revs);
		if (count($ids) < 2) return '';

		$new_id = $ids[0];
		$old_id = $ids[1];

		$users = array_values($revs);
		$username = $users[0];

		$url = 'https://www.wikihow.com/'.$page_title.'?oldid='.$old_id.'&diff='.$new_id;
		return $url.','.$username;
	}

	private static function makeFile($csv_rows) {;
		$file = '/tmp/latest_diffs.csv';
		$header = 'diff,user';

		echo "writing output to $file...\n";

		$fp = fopen($file, 'w');
		fputs($fp, "$header\n");

		foreach ($csv_rows as $row) {
			fputs($fp, "$row\n");
		}
		fclose($fp);

		echo "done.\n";
	}

}

$maintClass = 'getLatestDiffs';
require_once RUN_MAINTENANCE_IF_MAIN;
