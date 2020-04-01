<?php

require_once __DIR__ . '/../../Maintenance.php';

/**
 * https://wikihow.lighthouseapp.com/projects/97771/tickets/2974#ticket-2974-21
 */
class RecalcCustomTitles extends Maintenance {

	/**
	 * Define how many titles to recalculate per day.
	 * The daily limits are intended to make the recalculation last ~3 months.
	 * The totals are based on SELECT count(*) FROM custom_titles WHERE ct_timestamp < '20191216123000'.
	 */
	private static $dailyLimits = [
	//  lang    limit     total   days
		'en' => 3139,   # 282585 / 90
		'ar' => 165,    # 14939 / 90
		'cs' => 58,     # 5277 / 90
		'de' => 455,    # 41032 / 90
		'es' => 835,    # 75189 / 90
		'fr' => 396,    # 35729 / 90
		'hi' => 55,     # 5006 / 90
		'id' => 324,    # 29170 / 90
		'it' => 570,    # 51385 / 90
		'ja' => 60,     # 5430 / 90
		'ko' => 60,     # 5440 / 90
		'nl' => 222,    # 20027 / 90
		'pt' => 708,    # 63799 / 90
		'ru' => 589,    # 53043 / 90
		'th' => 87,     # 7872 / 90
		'tr' => 20,     # 1846 / 90
		'vi' => 110,    # 9936 / 90
		'zh' => 166,    # 14946 / 90
	];

	public function execute() {
		global $wgLanguageCode;

		$dbr = wfGetDB(DB_REPLICA);
		$table = 'custom_titles';
		$where = "ct_timestamp < '20191216123000'";
		$fields = [ 'ct_pageid', 'ct_page', 'ct_custom', 'ct_timestamp' ];
		$opts = [
			'ORDER BY' => 'RAND()',
			'LIMIT' => self::$dailyLimits[$wgLanguageCode]
		];
		$rows = $dbr->select($table, $fields, $where, __METHOD__, $opts);

		foreach ($rows as $row) {
			$aid = (int)$row->ct_pageid;
			$title = Title::newFromID($aid);
			if ( !$title || !$title->exists() ) {
				$this->log("ERROR | Title does not exist: $aid -> {$row->ct_page}");
				continue;
			}

			$ct = CustomTitle::newFromTitle($title);
			$tBefore = $ct->getTitle();
			$tAfter = $ct->getDefaultTitle()[0];
			$changed = strcasecmp($tBefore, $tAfter) === 0 ? False : True;
			if ($changed) {
				$this->log("CHANGE | {$tBefore}\t{$tAfter}");
			}
		}
	}

	private function log(string $msg): void {
		global $wgLanguageCode;
		$date = date('Y-m-d');
		$this->output("$date | $wgLanguageCode | $msg\n");
	}

}

$maintClass = 'RecalcCustomTitles';
require_once RUN_MAINTENANCE_IF_MAIN;
