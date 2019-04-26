<?php

require_once __DIR__ . '/../WHMaintenance.php';

/**
 * Recalculate the indexation directives of category pages and report any changes by email
 */
class RecalcCategPolicies extends WHMaintenance {

	protected $emailRecipients = 'alberto@wikihow.com, reuben@wikihow.com, adriana@wikihow.com';

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Recalculate category index policies";
	}

	public function execute()
	{
		global $wgLanguageCode, $wgIsDevServer;

		parent::execute();

		if ($wgIsDevServer) {
			$this->emailRecipients = 'alberto@wikihow.com';
		}

		# Get all category pages

		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['page', 'index_info'];
		$fields = ['page_id', 'page_title', 'ii_policy'];
		$where = [ 'page_namespace' => NS_CATEGORY ];
		$opts = [ 'ORDER BY' => 'page_id ASC' ];
		$join = [ 'index_info' => [ 'LEFT JOIN', [ 'page_id = ii_page' ] ] ];
		$rows = $dbr->select($tables, $fields, $where, __METHOD__, $opts, $join);

		# Recalculate policies and keep track of changes

		$changes = [ 'Indexed'=>[], 'Deindexed'=>[] ];
		foreach ($rows as $row) {
			$title = Title::newFromID($row->page_id, NS_CATEGORY);
			if (!$title || !$title->exists()) {
				continue;
			}

			$oldPolicy = $row->ii_policy ?? 0; // It will be NULL if the row doesn't exist
			$newPolicy = RobotPolicy::recalcArticlePolicyBasedOnTitle($title, $this->debug);
			$isIndexableOld = RobotPolicy::isIndexablePolicy($oldPolicy);
			$isIndexableNew = RobotPolicy::isIndexablePolicy($newPolicy);

			if ($isIndexableOld != $isIndexableNew) {
				$type = $isIndexableNew ? 'Indexed' : 'Deindexed';
				$url = PROTO_HTTPS . wfCanonicalDomain() . urldecode($title->getLocalURL());
				$changes[$type][$row->page_id] = $url;
			}
		}

		if (!$changes['Indexed'] && !$changes['Deindexed']) {
			$this->echo("Done (no changes)");
			return;
		}

		# Send email report

		$summary = '';
		foreach ($changes as $type => $categs) {
			$summary .= "{$type}:\n";
			if (!$categs) {
				$summary .= "- None\n";
			} else foreach ($categs as $id => $name) {
				$summary .= "- {$name}\n";
			}
		}
		$this->mail("Category page indexation updates ($wgLanguageCode)", $summary);

		# Log changes

		$dir = '/var/log/wikihow/categ_index/';
		@mkdir($dir, 0775, true);
		$logFile = $dir . date('Y-m-d') . ".{$wgLanguageCode}.log";
		file_put_contents($logFile, $summary);

		$this->echo("Done (details in $logFile)");
	}

}

$maintClass = "RecalcCategPolicies";
require_once RUN_MAINTENANCE_IF_MAIN;
