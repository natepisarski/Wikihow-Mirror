<?php

/*
after this, run these:

alter table qa_answerer_helpfulness drop index qah_answerer_user_id, add UNIQUE qah_answerer_user_id (`qah_answerer_user_id`);
*/


require_once __DIR__ . '/../../Maintenance.php';

class fixDuplicatesInQaAnswerHelpfulness extends Maintenance {

	const THIS_TABLE = 'qa_answerer_helpfulness';

	public function __construct() {
		parent::__construct();
		$this->mDescription = "One-time fix script for duplicates in qa_answerer_helpfulness.";
	}

	public function execute() {
		$db = wfGetDB(DB_MASTER);
		$res = $db->select(
			self::THIS_TABLE,
			'*',
			[],
			__METHOD__,
			[
				'ORDER BY' => [
					'qah_answerer_user_id',
					'qah_last_emailed DESC'
				]
			]
		);

		$last_user = 0;
		$delete_rows = [];
		foreach ($res as $row) {
			if ($last_user == $row->qah_answerer_user_id) {
				$delete_rows[] = $row->qah_id;
			}

			$last_user = $row->qah_answerer_user_id;
		}

		if (!empty($delete_rows)) {
			$res = $db->delete(
				self::THIS_TABLE,
				['qah_id' => $delete_rows],
				__METHOD__
			);
		}
		else {
			$res = '';
		}

		print $res ? "\nDuplicate rows deleted.\n" : "\nError\n";
	}
}

$maintClass = 'fixDuplicatesInQaAnswerHelpfulness';
require_once RUN_MAINTENANCE_IF_MAIN;
