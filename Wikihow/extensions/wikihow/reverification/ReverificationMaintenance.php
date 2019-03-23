<?php

/**
 * Maintenance class for reverifications
 */
class ReverificationMaintenance {
	const WORKSHEET_EXPERT = 'expert';
	const WORKSHEET_ACADEMIC = 'academic';

	/*
	 * Updates the reverification queue with articles that need reverification
	 */
	public function updateQueue() {
		$db = ReverificationDB::getInstance();
		$db->purgeStaleQueueItems();

		$vds = $this->getVerifyData();
		$reverifications = [];
		foreach ($vds as $vd) {
			$reverifications []= ReverificationData::newFromVerifyData($vd);
		}
		$db->insert($reverifications);
	}

	protected function getVerifyData() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( VerifyData::ARTICLE_TABLE,
			'*',
			array(),
			__METHOD__
		);

		$vds = [];
		foreach ($res as $row) {
			$vd = VerifyData::newArticleFromRow($row);
			if ($vd->worksheetName == self::WORKSHEET_EXPERT
				|| $vd->worksheetName == self::WORKSHEET_ACADEMIC) {
				$vds []= $vd;
			}
		}

		return $vds;
	}

	protected function checkEmails() {

	}
}
