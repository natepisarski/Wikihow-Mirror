<?php

require_once( __DIR__ . '/../Maintenance.php' );

/*
CREATE TABLE `event_log` (
	`el_page_id` int(11) NOT NULL,
	`el_domain` varbinary(50) NOT NULL,
	`el_screen` varbinary(16) NOT NULL,
	`el_date` datetime NOT NULL,
	`el_action` varbinary(50) NOT NULL,
	`el_count` int(11) NOT NULL,
	`el_params` blob,
	KEY `page_id` (`el_page_id`),
	KEY `page_domain_date_action_idx` (`el_page_id`,`el_domain`,`el_date`,`el_action`),
	KEY `action_date_domain_idx` (`el_action`,`el_date`,`el_domain`)
);
CREATE TABLE `clear_event` (
	`ce_page_id` int(11) NOT NULL,
	`ce_domain` varbinary(50) NOT NULL,
	`ce_date` datetime NOT NULL,
	`ce_action` varbinary(50) NOT NULL,
	UNIQUE KEY `unique_idx` (`ce_page_id`,`ce_domain`,`ce_date`,`ce_action`),
	KEY `page_id` (`ce_page_id`)
);
CREATE TABLE `item_rating` (
	`ir_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
	`ir_page_id` int(8) unsigned NOT NULL DEFAULT '0',
	`ir_type` varbinary(50) NOT NULL,
	`ir_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`ir_rating` tinyint(1) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`ir_page_id`,`ir_id`),
	UNIQUE KEY `ir_id` (`ir_id`),
	KEY `ir_timestamp` (`ir_timestamp`),
	KEY `ir_page_type` (`ir_page_id`,`ir_type`)
);

Each line in the log file contains:

RFC1123 date
Encoded URL of the endpoint hit to log action with required action and optional page params
Encoded URL of the page the action occured on (can be used in lieu of a page param)

The format is 3 quoted strings, it looks like:
"Tue, 19 Mar 2019 23:03:37 GMT" "m.wikihow.com%2Fx%2Fevent%3Faction%3Dsvideoplay%26page%3D1098893" "https%3A%2F%2Fm.wikihow.com%2FVideo%2FGet-Rid-of-Skunks"
*/

class ImportFastlyEventLog extends Maintenance {

	protected static $counters = [ 'svideoplay', 'svideoview' ]; // legacy

	protected static $eventConfig = [
		'svideoplay' => [],
		'svideoview' => [],
		'covid_readmore' => [],
		'covid_close' => [],
	];

	public function __construct() {
		parent::__construct();
		$this->addOption( 'file', 'input file', true, true, 'f' );
		$this->addOption( 'dry-run', "Do not update the database" );
	}

	public function execute() {
		$dryRun = $this->hasOption( 'dry-run' );
		$file = $this->getOption( 'file' );
		decho( "file is", $file, false );

		$handle = fopen( $file, "r" );
		if ( ! $handle ) {
			decho( 'error opening file', $file, false );
			return;
		}

		$counters = [];
		$events = [];
		while ( ( $row = fgetcsv( $handle, 0, ' ' ) ) ) {
			$title = null;
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
			$domain = $eventUrl['host']; // todo: sanitize (must contain 'wikihow')
			$pageId = $title->getArticleID();
			$action = $params['action'] ?? '';
			$screenSize = $params['screen'] ?? ''; // todo: sanitize (must be: large|medium|small)
			$dateTime = DateTime::createFromFormat( DateTime::RFC1123, $row[0] );
			$dateString = $dateTime->format( 'Y-m-d H:i:s' );

			// Filter out bad actions
			if ( !isset(self::$eventConfig[$action]) ) {
				decho( "invalid action", $action, false );
				continue;
			}

			$isCounter = in_array($action, self::$counters);
			if ( $isCounter ) {
				// Build list of row insertions, summing counts for duplicate domain/pageID/action
				$key = "{$domain} {$pageId} {$action}";
				if ( !isset( $counters[$key] ) ) {
					$counters[$key] = [
						'el_domain' => $domain,
						'el_screen' => $screenSize,
						'el_page_id' => $pageId,
						'el_action' => $action,
						'el_count' => 1,
						'el_date' => $dateString,
					];
				} else {
					$counters[$key]['el_count']++;
					if ( $dateString > $counters[$key]['el_date'] ) {
						$counters[$key]['el_date'] = $dateString;
					}
				}
			} else {
				$extraParams = self::$eventConfig[$action];
				$cleanParams = [];
				foreach ($extraParams as $paramName) {
					$cleanParams[$paramName] = isset($params[$paramName])
						? substr($params[$paramName], 0, 200)
						: null;
				}
				$events[] = [
					'el_page_id' => $pageId,
					'el_domain' => $domain,
					'el_screen' => $screenSize,
					'el_date' => $dateString,
					'el_action' => $action,
					'el_count' => 1,
					'el_params' => json_encode($cleanParams),
				];
			}
		}

		fclose($handle);

		$dbw = wfGetDB( DB_MASTER );

		decho( 'inserting', count( $counters ) . ' counters', false );
		foreach ( $counters as $key => $event ) {
			if ($dryRun) {
				echo "$key | " . implode(', ', $event) . "\n";
			} else {
				$dbw->insert( 'event_log', $event, __METHOD__ );
			}
		}

		decho( 'inserting', count( $events ) . ' events', false );
		foreach ( $events as $event ) {
			if ($dryRun) {
				echo implode(', ', $event) . "\n";
			} else {
				$dbw->insert( 'event_log', $event, __METHOD__ );
			}
		}

	}
}

$maintClass = "ImportFastlyEventLog";
require_once( RUN_MAINTENANCE_IF_MAIN );
