<?php

use MethodHelpfulness\ArticleMethod;

/**
 * List the ratings of some set of pages
 */
class ListRatings extends QueryPage {

	public function __construct( $name = 'ListRatings' ) {
		parent::__construct( $name );
		//is this for articles or samples?
		if (strpos(strtolower($_SERVER['REQUEST_URI']),'sample')) {
			$this->forSamples = true;
			$this->tablePrefix = 'rats_';
			$this->tableName = 'ratesample';
		} else {
			$this->forSamples = false;
			$this->tablePrefix = 'rat_';
			$this->tableName = 'rating';
		}
		list( $limit, $offset ) = RequestContext::getMain()->getRequest()->getLimitOffset(50, 'rclimit');
		$this->limit = $limit;
		$this->offset = $offset;
	}

	var $targets = array();
	var $tablePrefix = '';

	public function execute( $par ) {
		$action = $this->getRequest()->getVal('action','');
		if ($action == 'csv') return $this->getSampleCSV();
		parent::execute($par);
	}

	function getName() {
		return 'ListRatings';
	}

	function isExpensive( ) { return false; }

	function isSyndicated() { return false; }

	function getOrderFields() {
		return array('R');
	}

	function getSQL() {
		return "SELECT {$this->tablePrefix}page, AVG({$this->tablePrefix}rating) as R, count(*) as C FROM {$this->tableName} WHERE {$this->tablePrefix}isDeleted = '0' GROUP BY {$this->tablePrefix}page";
	}

	function formatResult($skin, $result) {
		if ($this->forSamples) {
			$t = Title::newFromText('Sample/'.$result->rats_page);
		} else {
			$t = Title::newFromId($result->rat_page);
		}

		if ($t == null)
			return "";

		if ($this->forSamples) {
			//need to tell the linker that the title is known otherwise it adds redlink=1 which eventually breaks the link
			return Linker::linkKnown($t, $t->getFullText()) . " ({$result->C} votes, {$result->R} average)";
		} else {
			return Linker::link($t, $t->getFullText()) . " ({$result->C} votes, {$result->R} average)";
		}
	}

	function getPageHeader( ) {
		if ($this->forSamples) {
			$out = $this->getOutput();
			$out->setPageTitle('List Rated Sample Pages');
			$csvLink = $this->getSampleCSVLink();
			return '<div style="float:right;font-size:.8em;">'.$csvLink.'</div>';
		}
		return;
	}

	private function getSampleCSV() {
		global $wgCanonicalServer;

		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="data.csv"');

		$this->getOutput()->disable();

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			'ratesample',
			[
				'rats_page',
				'AVG(rats_rating) as R',
				'count(*) as C'
			],
			[
				'rats_isdeleted' => 0
			],
			__METHOD__,
			[
				"GROUP BY" => "rats_page",
				"LIMIT" => 50000
			]
		);

		$lines = [];

		foreach ($res as $row) {
			$t = Title::newFromText('Sample/'.$row->rats_page);
			if (empty($t)) continue;

			$link = str_replace(" ", "-", "$wgCanonicalServer/".$t->getFullText());

			$line = [$link, $row->C, $row->R];
			$lines[] = implode(",", $line);
		}

		print("page,votes,average\n");
		print(implode("\n", $lines));
	}

	private function getSampleCSVLink() {
		if (!$this->getUser()->hasGroup('staff')) return '';
		$queryParams = ["action"=>"csv"];
		$html = Linker::linkKnown($this->getTitle(), "download .csv [last 50k entries only]", [], $queryParams);
		return $html;
	}
}
