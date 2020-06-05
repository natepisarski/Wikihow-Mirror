<?php
/**
 * This class contains the maintenance logic that maintains our gsc database on andy
 */

/*
Schema (found dev and db8 for use with Andy):

CREATE DATABASE gsc;
CREATE TABLE `gsc_data` (
  `gsc_country` varbinary(3) NOT NULL,
  `gsc_device` varbinary(10) NOT NULL,
  `gsc_query` varbinary(1024) NOT NULL,
  `gsc_page` varbinary(1024) NOT NULL,
  `gsc_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `gsc_clicks` float DEFAULT NULL,
  `gsc_impressions` float DEFAULT NULL,
  `gsc_ctr` float DEFAULT NULL,
  `gsc_position` float DEFAULT NULL,
  `gsc_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gsc_page`,`gsc_date`,`gsc_device`,`gsc_country`,`gsc_query`),
  KEY `gsc_date` (`gsc_date`) USING BTREE,
  KEY `gsc_query` (`gsc_query`)
) ENGINE=InnoDB DEFAULT CHARSET=binary
*/

class GSCDataMaintenance {
	const MAX_ROWS = 25000;
	const DB_NAME = 'gsc';
	const DB_DATA_TABLE_NAME = 'gsc_data';

	public function import($siteUrl, $startDate, $endDate) {
		$startDate = new DateTime($startDate);
		$endDate = new DateTime($endDate);
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
				$queryRequest->setDimensions(['page', 'date','device','country','query']);
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

	protected function storeData($rows) {
		$rows = $this->transformSingleDateData($rows);
		$dbw = wfGetDB(DB_MASTER);
		$dbw->selectDB(self::DB_NAME);
		$dbw->insert(
			self::DB_DATA_TABLE_NAME,
			$rows,
			__METHOD__,
			['IGNORE']
		);
		$dbw->selectDB('wikidb_112');

	}

	protected function transformSingleDateData($rows) {
		$transformedRows = [];
		foreach ($rows as $row) {
			$transformedRows []= [
				'gsc_page' => $row->getKeys()[0],
				'gsc_date' => $row->getKeys()[1],
				'gsc_device' => $row->getKeys()[2],
				'gsc_country' => $row->getKeys()[3],
				'gsc_query' => $row->getKeys()[4],
				'gsc_clicks' => $row->getClicks(),
				'gsc_impressions' => $row->getImpressions(),
				'gsc_ctr' => $row->getCtr(),
				'gsc_position' => $row->getPosition()
			];
		}
		return $transformedRows;
	}
}