<?php

require_once( __DIR__ . '/../Maintenance.php' );

/*
CREATE TABLE `event_log` (
	`el_page_id` int(11) NOT NULL,
	`el_domain` varchar(50) NOT NULL,
	`el_date` datetime NOT NULL,
	`el_action` varchar(50) NOT NULL,
	`el_count` int(11) NOT NULL,
	KEY `page_id` (`el_page_id`),
	UNIQUE KEY `unique_idx` (`el_page_id`,`el_domain`,`el_date`,`el_action`)
);
CREATE TABLE `clear_event` (
	`ce_page_id` int(11) NOT NULL,
	`ce_domain` varchar(50) NOT NULL,
	`ce_date` datetime NOT NULL,
	`ce_action` varchar(50) NOT NULL,
	KEY `page_id` (`ce_page_id`),
	UNIQUE KEY `unique_idx` (`ce_page_id`,`ce_domain`,`ce_date`,`ce_action`)
);
CREATE TABLE `item_rating` (
	`ir_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
	`ir_page_id` int(8) unsigned NOT NULL DEFAULT '0',
	`ir_type` varchar(50) NOT NULL,
	`ir_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`ir_rating` tinyint(1) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`ir_page_id`,`ir_id`),
	UNIQUE KEY `ir_id` (`ir_id`),
	KEY `ir_timestamp` (`ir_timestamp`),
	KEY `ir_page_type` (`ir_page_id`,`ir_type`)
);
*/
class ImportFastlyEventLog extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'file', 'input file', true, true, 'f' );
	}

	public function execute() {
		$dbw = wfGetDB(DB_MASTER);
		$table = 'event_log';
		$file = $this->getOption( 'file' );
		decho( "file is", $file, false );
		$handle = fopen( $file, "r" );

		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				$data = preg_split('/\s+/', trim( $line ) );
				$count = $data[0];
				$url = trim( $data[1], ' "' );
				$domain = strstr( $url, '/', true );
				$title = Misc::getTitleFromText( $url );
				if ( !$title ) {
					decho( 'no title found for', $url, false );
					continue;
				}
				$pageId = $title->getArticleID();
				$insertData = array(
					'el_page_id' => $pageId,
					'el_domain' => $domain,
					'el_count' => $count,
				);

				// get action and timestamp
				$queryParams = explode( '&', trim( $data[2], ' "' ) );
				if ( count( $queryParams ) != 2 ) {
					decho("did not find required two query params");
					continue;
				}

				$action = $queryParams[0];
				$date = $queryParams[1];
				//decho("action", $action);
				if ( !substr( $action, 0, 6 ) === "action=" ) {
					decho( "action invalid for row", $insertData, false );
					continue;
				}

				// TODO restrict action to a list of actions
				$insertData['el_action'] = substr( $action, 7 );
				$date = substr( $date, 2 );
				$date = gmdate("Y-m-d H:i:s", $date);
				$insertData['el_date'] = $date;

				$options = array( 'IGNORE' );
				$dbw->insert( $table, $insertData, __METHOD__, $options );
				//decho('inserted row', $insertData, false );
			}

			fclose($handle);
		} else {
			// error opening the file.
		}
	}
}

$maintClass = "ImportFastlyEventLog";
require_once( RUN_MAINTENANCE_IF_MAIN );
