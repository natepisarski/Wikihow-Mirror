<?php

// Add a query for use in Titus stored queries
require_once( __DIR__ . '/../Maintenance.php' );
class StoreTitusQuery extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption('query', 'Query to store', true, true, 'q');
	}
	public function execute() {
		require_once( __DIR__ . '/../../extensions/wikihow/titus/Titus.class.php');
		$query = $this->getOption('query');
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->query("select uuid() as uuid");
		$row = $dbr->fetchObject($res);
		$uuid = $row->uuid;
		$titusDB = new TitusDB();
		$dbw = $titusDB->getTitusDB();
		$dbw->insert('stored_query' , array('sq_query' => $query, 'sq_uuid' => $uuid), __METHOD__);
		print $uuid . "\n";
	}
}

$maintClass = "StoreTitusQuery";
require_once( RUN_MAINTENANCE_IF_MAIN );                                                                                                                   
