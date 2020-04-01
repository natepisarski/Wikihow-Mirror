<?php
/*
 * Initially process all titles to get their folded redirect strings
 */

require_once __DIR__ . '/../../Maintenance.php';

class PopulateFoldedRedirects extends Maintenance {

    public function execute() {
		global $wgLanguageCode;

		$this->output(basename(__FILE__) . " running for '$wgLanguageCode'\n");

		$dbr = wfGetDB(DB_REPLICA);
		$where = [ 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ];
		$total = $dbr->selectField('page', 'count(*)', $where);
		$count = 0;
		$rows = DatabaseHelper::batchSelect('page', ['page_title', 'page_id'], $where);

		wfGetDB(DB_MASTER)->delete('redirect_page', '*');

		foreach ($rows as $row) {
			$title = Title::newFromDBkey($row->page_title);
			if ($title) {
				PageHooks::modify404Redirect($row->page_id, $title);
			}
			if (++$count % 1000 == 0) {
				$this->output("$count / $total\n");
			}
		}
	}

}

$maintClass = 'PopulateFoldedRedirects';
require_once RUN_MAINTENANCE_IF_MAIN;
