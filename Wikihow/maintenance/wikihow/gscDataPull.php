<?php

require_once __DIR__ . '/../Maintenance.php';

/**
 * Pull some GSC data for Chris to do some analysis
 *
 * Two methods:
 *
 * 1) Aggregate.  Pulls all url data for a given date range and writes it to a csv
 *
 * Ex:
 * whrun -- gscDataPull.php --siteUrl="https://www.wikihow.com/" --startDate="2020-02-17" --endDate="2020-03-01" --aggregate
 *
 * 2) Non-aggregate.  Pull all urls daily and store data as rows in a table. (Table currently just on the dev db)
 *
 * Ex:
 * whrun -- gscDataPull.php --siteUrl="https://www.wikihow.com/" --startDate="2020-02-17" --endDate="2020-03-01"
 */

/*
Schema:

CREATE TABLE `gsc_data` (
  `gsc_url` varbinary(1024) NOT NULL,
  `gsc_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `gsc_clicks` float DEFAULT NULL,
  `gsc_impressions` float DEFAULT NULL,
  `gsc_ctr` float DEFAULT NULL,
  `gsc_position` float DEFAULT NULL,
  PRIMARY KEY (`gsc_url`,`gsc_date`),
  KEY `gsc_date` (`gsc_date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=binary
 */

class GSCDataPull extends Maintenance {

	const TABLE = 'gsc_data';
	const API_KEY = 'AIzaSyBoQzp4ushHIyC37GRCjEz4j7jJBHPe-m8';
	const CSV_HEADER_ROW = ['url', 'clicks', 'impressions', 'ctr', 'position'];
	const MAX_ROWS = 25000;
	var $currentDate = null;
	var $aggregate = false;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Pulls GSC data by url/day for a given date range and domain store in a database table';

		$this->addOption(
			'siteUrl', // long form
			'name of site to pull', // description
			true, // required
			true, // takes arguments
			's' // short form
		);

		$this->addOption(
			'aggregate', // long form
			'name of site to pull', // description
			false, // required
			false, // takes arguments
			'a' // short form
		);

		$this->addOption(
			'startDate', // long form
			'start date of range to pull', // description
			true, // required
			true, // takes arguments
			'b' // short form
		);

		$this->addOption(
			'endDate', // long form
			'end date of range to pull', // description
			true, // required
			true, // takes arguments
			'e' // short form
		);
	}

	public function execute() {
		$siteUrl = $this->getOption('siteUrl');
		$startDate = $this->getOption('startDate');
		$endDate = $this->getOption('endDate');

		$aggregate = $this->getOption('aggregate', false);
		if ($aggregate) {
			$i = 0;
			$rows = [];
			do {
				echo "Requesting aggregate range: $startDate - $endDate " . self::MAX_ROWS . " rows, starting at row "
					. $i * self::MAX_ROWS . "\n";

				$queryRequest = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
				$queryRequest->setStartDate($startDate);
				$queryRequest->setEndDate($endDate);
				$queryRequest->setDimensions(['page']);
				$queryRequest->setRowLimit(self::MAX_ROWS);
				$queryRequest->setStartRow($i * self::MAX_ROWS);

				$gsc = GoogleWebmasters::getService();
				$result = $gsc->searchanalytics->query($siteUrl, $queryRequest);
				$rows = array_merge($rows, $result->getRows());
				$i++;
			} while(count($result->getRows()) !== 0);

			$this->writeCSV($startDate, $endDate, $rows);
		} else {
			$startDate = new DateTime($this->getOption('startDate'));
			$endDate = new DateTime($this->getOption('endDate'));
			$endDate->modify('+1 day');

			$interval = DateInterval::createFromDateString('1 day');
			$period = new DatePeriod($startDate, $interval, $endDate);
			foreach ($period as $dt) {
				$this->currentDate = $currentDate = $dt->format("Y-m-d");
				$i = 0;
				$rows = [];
				do {
					echo "date: $currentDate - requesting " . self::MAX_ROWS . " rows, starting at row "
						. $i * self::MAX_ROWS . "\n";

					$queryRequest = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
					$queryRequest->setStartDate($currentDate);
					$queryRequest->setEndDate($currentDate);
					$queryRequest->setDimensions(['page']);
					$queryRequest->setRowLimit(self::MAX_ROWS);
					$queryRequest->setStartRow($i * self::MAX_ROWS);

					$gsc = GoogleWebmasters::getService();
					$result = $gsc->searchanalytics->query($siteUrl, $queryRequest);
					$rows = array_merge($rows, $result->getRows());
					$i++;
				} while(count($result->getRows()) !== 0);
				echo "date: $currentDate, Rows to store: " . count($rows) . "\n";
				$this->storeData($rows);
			}
		}

	}

	protected function storeData($rows) {
		$rows = $this->transformSingleDateData($rows);
//		var_dump($rows);
		$dbw = wfGetDB(DB_MASTER);
		$dbw->upsert(
			self::TABLE,
			$rows,
			['gsc_url', 'gsc_date'],
			[
				'gsc_clicks = VALUES(gsc_clicks)',
				'gsc_impressions = VALUES(gsc_impressions)',
				'gsc_ctr = VALUES(gsc_ctr)',
				'gsc_position = VALUES(gsc_position)'
			]
		);

	}

	protected function transformSingleDateData($rows) {
		$transformedRows = [];
		foreach ($rows as $row) {
			$transformedRows []= [
				'gsc_url' => $row->getKeys()[0],
				'gsc_date' => $this->currentDate,
				'gsc_clicks' => $row->getClicks(),
				'gsc_impressions' => $row->getImpressions(),
				'gsc_ctr' => $row->getCtr(),
				'gsc_position' => $row->getPosition()
			];
		}
		return $transformedRows;
	}

	protected function transformDateRangeData($rows) {
		$transformedRows = [];
		foreach ($rows as $row) {
			$transformedRows []= [
				'gsc_url' => $row->getKeys()[0],
				'gsc_clicks' => $row->getClicks(),
				'gsc_impressions' => $row->getImpressions(),
				'gsc_ctr' => $row->getCtr(),
				'gsc_position' => $row->getPosition()
			];
		}
		return $transformedRows;
	}

	/**
	 * @param $startDate
	 * @param $endDate
	 * @param $rows
	 */
	private function writeCSV($startDate, $endDate, $rows) {
		$rows = $this->transformDateRangeData($rows);
		$fp = fopen("gsc_data_range_{$startDate}_{$endDate}.csv", 'w');
		fputcsv($fp, self::CSV_HEADER_ROW);


		// Loop through file pointer and a line
		foreach ($rows as $row) {
			fputcsv($fp, array_values($row));
		}

		fclose($fp);
	}
}

$maintClass = 'GSCDataPull';

require_once RUN_MAINTENANCE_IF_MAIN;
