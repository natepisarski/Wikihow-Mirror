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

	protected static $allowedActions = [ 'svideoplay', 'svideoview' ];

	public function __construct() {
		parent::__construct();
		$this->addOption( 'file', 'input file', true, true, 'f' );
	}

	public function execute() {
		$file = $this->getOption( 'file' );
		decho( "file is", $file, false );

		// Each line in the file contains:
		// 
		// RFC1123 date
		// Encoded URL of the endpoint hit to log action with required action and optional page params
		// Encoded URL of the page the action occured on (can be used in lieu of a page param)
		// 
		// The format is 3 quoted strings, it looks like:
		// 
		// "Tue, 19 Mar 2019 23:03:37 GMT" "m.wikihow.com%2Fx%2Fevent%3Faction%3Dsvideoplay%26page%3D1098893" "https%3A%2F%2Fm.wikihow.com%2FVideo%2FGet-Rid-of-Skunks"

		$handle = fopen( $file, "r" );
		$events = [];
		if ( $handle ) {
			while ( ( $row = fgetcsv( $handle, 0, ' ' ) ) ) {
				$eventUrl = parse_url( 'https://' . urldecode( $row[1] ) );
				if ( $eventUrl ) {
					parse_str( $eventUrl['query'], $params );
					if ( isset( $params['page'] ) ) {
						// Lookup by page ID
						$title = Title::newFromID( $params['page'] );
					} else {
						// Extract title from referrer
						$referrerUrl = parse_url( urldecode( $row[2] ) );
						if ( $referrerUrl && strlen( $referrerUrl['path'] ) ) {
							$title = Title::newFromText( ltrim( $referrerUrl['path'] , '/' ) );
						}
					}
				}

				// Verify we have a valid title
				if ( !$title ) {
					decho( 'invalid event row', implode( ' ', $row ) );
					continue;
				}

				// Extract useful information from log line
				$domain = $eventUrl['host'];
				$pageId = $title->getArticleID();
				$action = $params['action'];
				$dateTime = DateTime::createFromFormat( DateTime::RFC1123, $row[0] );
				$dateString = $dateTime->format( 'Y-m-d H:i:s' );

				// Filter out bad actions
				if ( !in_array( $action, self::$allowedActions ) ) {
					decho( "action invalid", $key, false );
					continue;
				}

				// Build list of row insertions, summing counts for duplicate domain/pageID/action
				$key = "{$domain} {$pageId} {$action}";
				if ( !isset( $events[$key] ) ) {
					$events[$key] = [
						'el_domain' => $domain,
						'el_page_id' => $pageId,
						'el_action' => $action,
						'el_count' => 1,
						'el_date' => $dateString,
					];
				} else {
					$events[$key]['el_count']++;
					if ( $dateString > $events[$key]['el_date'] ) {
						$events[$key]['el_date'] = $dateString;
					}
				}	
			}

			fclose($handle);

			decho( 'inserting events', count( $events ) . ' events', false );

			$dbw = wfGetDB( DB_MASTER );
			foreach ( $events as $key => $event ) {
				$dbw->insert( 'event_log', $event, __METHOD__, [ 'IGNORE' ] );
			}
		} else {
			decho( 'error opening file', $file, false );
		}
	}
}

$maintClass = "ImportFastlyEventLog";
require_once( RUN_MAINTENANCE_IF_MAIN );
