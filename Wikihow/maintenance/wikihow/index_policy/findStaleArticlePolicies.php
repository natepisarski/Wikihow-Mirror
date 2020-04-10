<?php

require_once __DIR__ . '/../WHMaintenance.php';

/**
 * Alerts by email about stale indexation policies in index_info
 */
class FindStaleArticlePolicies extends WHMaintenance {

	protected $emailRecipients = 'alberto@wikihow.com';

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Find and alert about stale article indexation policies";
		$this->addOption('fix', "Recalculate policies that should be updated in the DB");
	}

	public function execute()
	{
		global $wgLanguageCode;

		parent::execute();

		$fix = (bool)$this->getOption('fix', false);
		$fixed = [];

		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['page', 'index_info'];
		$fields = ['page_id', 'ii_policy'];
		$where = [ 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ];
		$opts = [];
		$join = [ 'index_info' => [ 'JOIN', [ 'page_id = ii_page' ] ] ];

		// We only check all articles on Saturdays, but we still want to check a subset
		// every day, to quickly detect potential bugs that affect the indexation policy.
		$isSaturday = (date('w') === '6');
		if ( !$isSaturday ) {
			$limit = ($wgLanguageCode == 'en') ? 10000 : 1000;
			$opts = [ 'ORDER BY' => 'RAND()', 'LIMIT' => $limit ];
		}

		$rows = $dbr->select($tables, $fields, $where, __METHOD__, $opts, $join);
		$changes = [ 'indexed' => [], 'deindexed' => [] ];

		foreach ($rows as $row) {
			$title = Title::newFromID($row->page_id, NS_MAIN);
			if ( !$title || !$title->exists() ) {
				continue;
			}

			$oldPolicy = (int)$row->ii_policy;
			$newPolicy = RobotPolicy::recalcArticlePolicyBasedOnTitle($title, true /* $dry */ );
			$isIndexableOld = RobotPolicy::isIndexablePolicy($oldPolicy);
			$isIndexableNew = RobotPolicy::isIndexablePolicy($newPolicy);

			if ( $isIndexableOld != $isIndexableNew ) {
				$type = $isIndexableNew ? 'indexed' : 'deindexed';
				$url = PROTO_HTTPS . wfCanonicalDomain() . urldecode($title->getLocalURL());
				$changes[$type][$row->page_id] = $url;

				if ( $fix ) {
					RobotPolicy::recalcArticlePolicyBasedOnTitle($title, false /* $dry */ );
					$fixed[$row->page_id] = $url;
				}
			}
		}

		if ( $changes['indexed'] || $changes['deindexed'] || $this->debug ) {
			$this->reportChanges($changes, $fixed);
		}

		$this->echo("Done ($wgLanguageCode)");
	}

	private function reportChanges(array $changes, array $fixed): void
	{
		global $wgLanguageCode;

		$summary = '';
		foreach ( $changes as $type => $articles ) {
			$count = count($articles);
			$summary .= "Should be {$type} ($count):\n";
			if ( !$articles ) {
				$summary .= "- None\n";
			} else foreach ($articles as $id => $name) {
				$summary .= "- {$name} ($id)\n";
			}
		}

		if ( $fixed ) {
			$summary .= "\nThis script was called with the --fix option. The following policies were corrected:\n";
			foreach ($fixed as $id => $name) {
				$summary .= "- {$name} ($id)\n";
			}
		}

		if ( $this->debug ) {
			$this->echo("Summary:\n$summary");
		} else {
			$this->mail("Stale Index Policies Report ($wgLanguageCode)", $summary);
		}
	}

}

$maintClass = "FindStaleArticlePolicies";
require_once RUN_MAINTENANCE_IF_MAIN;
