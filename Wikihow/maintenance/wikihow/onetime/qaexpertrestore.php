<?php



require_once __DIR__ . '/../../Maintenance.php';

class qaExpertRestore extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "";
	}

	public function execute() {
		$dbr = wfGetDB(DB_REPLICA);

		//grab all the rows were the backup doesn't have a 0 but the current db does have 0 for the verifier id
		$res = $dbr->query("select * from bkup.qa_articles_questions as o where o.qa_id in (select qa_id from wikidb_112.qa_articles_questions as n where n.qa_verifier_id = 0) and  o.qa_verifier_id != 0 limit 500;");
		echo "Found " . $dbr->numRows($res) . " rows with discrepancies.\n";

		$verifiers = [];
		$qas = [];
		while($row = $dbr->fetchRow($res)) {
			$verifiers[] = $row['qa_verifier_id'];
			if(!array_key_exists($row['qa_verifier_id'], $qas)) {
				$qas[$row['qa_verifier_id']] = ['qa_ids' => [], 'qa_verifier_id_old' => $row['qa_verifier_id']];
			}
			$qas[$row['qa_verifier_id']]['qa_ids'][] = $row['qa_id'];
		}

		//now grab all the verifier info for the associated verifier ids, use the vi_user_name field to connect them
		$res2 = $dbr->query("select n.vi_id as new_id, o.vi_id as old_id, n.vi_name from wikidb_112.verifier_info as n left join bkup.verifier_info as o on n.vi_user_name = o.vi_user_name where o.vi_id IN (" . $dbr->makeList($verifiers) . ")");

		while($row = $dbr->fetchRow($res2)) {
			$qas[$row['old_id']]['qa_verifier_id_new'] = $row['new_id'];
		}

		$totalUpdated = 0;
		//now update the new table using the new verifier id #. Use the qa_id to select the row and make sure that the verifier id is currently 0.
		$dbw = wfGetDB(DB_MASTER);
		foreach($qas as $row) {
			foreach($row['qa_ids'] as $id) {
				$dbw->update("qa_articles_questions", ['qa_verifier_id' => $row['qa_verifier_id_new']], ['qa_id' => $id, 'qa_verifier_id' => 0], __FILE__);
				$totalUpdated += $dbr->affectedRows();
			}
		}

		echo "{$totalUpdated} rows have been updated\n";
	}

}

$maintClass = 'qaExpertRestore';
require_once RUN_MAINTENANCE_IF_MAIN;
