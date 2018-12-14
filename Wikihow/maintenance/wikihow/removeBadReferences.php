<?php

require_once __DIR__ . '/../Maintenance.php';

class removeBadReferences extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "remove checked 404 pages from article references";
		$this->addOption( 'limit', 'number of items to process', false, true, 'l' );
		$this->addOption( 'verbose', 'print verbose info', false, false, 'v' );
    }


	private function processItems() {
		$items = $this->getItems();
		decho("items", $items);
	}

	private function getItems() {
		$items = array();
		$dbr = wfGetDb( DB_SLAVE );
		$query = "SELECT page_title, el_from, el_to FROM `externallinks`,`page` WHERE (el_from = page_id) AND (el_id IN (select eli_el_id from externallinks_link_info, link_info where eli_li_id = li_id and li_code >= 400 && li_user_checked > 0)) LIMIT 10";
		$res = $dbr->query( $query,__METHOD__ );
		foreach ( $res as $row ) {
			$item = array(
				'pageId' => $row->el_from,
				'url' => $row->el_to
			);
			$items[] = $item;
		}
		return $items;
	}

	public function execute() {
		$this->processItems();
	}
}


$maintClass = "removeBadReferences";
require_once RUN_MAINTENANCE_IF_MAIN;

