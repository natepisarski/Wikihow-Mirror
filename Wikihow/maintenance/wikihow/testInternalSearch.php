<?php

require_once __DIR__ . '/../Maintenance.php';

class TestInternalSearch extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Tool to sanity test internal search";
	}

	public function execute() {
		$search = new LSearch();
		$queries = ['dogs', 'cats', 'dogs and cats', 'tie a tie', 'make french toast', 'how to rap', 'lskjdflksjdflskjfsdlkj'];
		foreach ($queries as $query) {
			$titles = $search->externalSearchResultTitles($query, 0, 10, 0, LSearch::SEARCH_INTERNAL);
			echo "Query: $query, numresults: " . count($titles) . "\n";
		}
	}
}

$maintClass = 'TestInternalSearch';
require_once RUN_MAINTENANCE_IF_MAIN;


