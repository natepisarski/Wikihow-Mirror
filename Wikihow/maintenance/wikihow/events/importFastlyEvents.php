<?php

require_once( __DIR__ . '/../../Maintenance.php' );

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

CREATE TABLE `event_files` (
	`ef_name` varbinary(150) NOT NULL,
);

Each line in the log file contains:

RFC1123 date
Encoded URL of the endpoint hit to log action with required action and optional page params
Encoded URL of the page the action occured on (can be used in lieu of a page param)

The format is 3 quoted strings, it looks like:
"Tue, 19 Mar 2019 23:03:37 GMT" "m.wikihow.com%2Fx%2Fevent%3Faction%3Dsvideoplay%26page%3D1098893" "https%3A%2F%2Fm.wikihow.com%2FVideo%2FGet-Rid-of-Skunks"
*/

class ImportFastlyEvents extends Maintenance {

	private $errors = [];

	public function __construct() {
		parent::__construct();
		$this->addOption( 'dry-run', "Do not update the database" );
	}

	public function execute() {
		// Get today's and yesterday's dates. We'll use these to find recent Fastly logs.
		$tz = new DateTimeZone('UTC'); // Fastly logs are in GMT
		$di = new DateInterval('P1D'); // 1 day
		$lookupDates = [
			( new DateTime() )->setTimezone($tz)->sub($di)->format('Y-m-d'), // yesterday
			( new DateTime() )->setTimezone($tz)->format('Y-m-d'),           // today
		];

		// Get the date 3 days ago. We'll use it to delete old filenames from the DB.
		$di = new DateInterval('P3D');
		$expiryDate = ( new DateTime() )->setTimezone($tz)->sub($di)->format('Y-m-d');

		// Print initial log line
		$nowStr = ( new DateTime() )->setTimezone($tz)->format('Y-m-d H:i:s');
		$lookupStr = "[". implode(', ', $lookupDates) ."]";
		echo "\n---- $nowStr: Starting with lookup dates={$lookupStr} and expiry date={$expiryDate}\n";

		// Do the actual work
		$dbLogs = $this->getAllLogNamesFromDB();
		$newLogs = $this->getNewLogNamesFromFastly($lookupDates, $dbLogs);
		if ( !$newLogs ) {
			echo "--- Already up to date\n";
			return;
		}
		$eventsFile = $this->downloadLogs($newLogs);
		$events = $this->parseEvents($eventsFile);
		if ($events) {
			$this->insertEventsInDB($events);
		}
		$this->updateLogFilesInDB($expiryDate, $newLogs);
	}

	private function getAllLogNamesFromDB(): array {
		$dbLogs = [];
		$rows = wfGetDB(DB_REPLICA)->select('event_files', '*', [], __METHOD__);
		foreach ($rows as $r) {
			$dbLogs[ $r->ef_name ] = true;
		}
		return $dbLogs;
	}

	private function getNewLogNamesFromFastly(array $dates, array $dbLogs): array {
		$newLogs = [];
		foreach ($dates as $date) {
			$cmd = __DIR__ . "/s3cmd_ls.sh $date";
			$output = [];
			exec($cmd, $output, $retVal);

			if ( $retVal != 0 ) {
				$msg = "\n--- FATAL ERROR: Failed to list log files:\n" . implode("\n", $output);
				throw new Exception($msg, 1);
			}

			foreach ($output as $logName) {
				if ( !isset($dbLogs[$logName]) ) {
					$newLogs[] = $logName;
				}
			}
		}
		return $newLogs;
	}

	private function downloadLogs(array $newLogs): string {
		$count = count($newLogs);
		echo "--- Downloading new logs: $count\n" . implode("\n", $newLogs) . "\n";

		$logpath = "/tmp/event_logs";
		$infile = "$logpath/s3get.files";
		$outfile = "$logpath/s3get.lines";

		exec("mkdir -p $logpath");

		// Write log filenames to a file (30 log names per line)
		$fp = fopen( $infile, 'w' );
		$lineSize = 30;
		for ($offset = 0; $offset < $count; $offset += $lineSize) {
			$slice = array_slice($newLogs, $offset, $lineSize);
			$line = implode(' ', $slice) . "\n";
			fwrite($fp, $line);
		}
		fclose($fp);

		$cmd = __DIR__ . "/s3cmd_get.sh $logpath $infile $outfile";
		exec($cmd, $output, $retVal);

		if ( $retVal != 0 ) {
			$msg = "\n--- FATAL ERROR: Failed to download log files:\n" . implode("\n", $output);
			throw new Exception($msg, 1);
		}

		return $outfile;
	}

	private function parseEvents(string $logFile): array {
		echo "--- Parsing events from: $logFile\n";

		$handle = fopen( $logFile, "r" );
		if ( ! $handle ) {
			echo "--- Error opening file $logFile\n";
			return [];
		}

		$tz = new DateTimeZone('America/Los_Angeles');
		$events = [];
		while ( ( $row = fgetcsv( $handle, 0, ' ' ) ) ) {

			// Validate the event origin and referrer URLs
			$eventUrl = 'https://' . $row[1];
			$referUrl = $row[2];
			$urlParts = EventHelper::validateEventUrls($eventUrl, $referUrl);
			if ( is_string($urlParts) ) {
				$this->reportError($row, $urlParts);
				continue;
			}

			// Validate the event parameters
			$params = [];
			parse_str( $urlParts['query'], $params );
			if ( in_array($params['action'] ?? '', ['svideoview', 'svideoplay'] ) ) { continue; } // TODO: remove this temporary safeguard (at some point)
			$eventConf = EventHelper::validateEventConfig($params);
			if ( is_string($eventConf) ) {
				$this->reportError($row, $eventConf);
				continue;
			}

			// Parse the event date
			$dateTime = DateTime::createFromFormat( DateTime::RFC1123, $row[0] )->setTimezone($tz);
			$dateString = $dateTime->format( 'Y-m-d H:i:s' );

			// Build the event row
			$events[] = EventHelper::makeDBRow($dateString, $urlParts, $eventConf);
		}

		fclose($handle);

		// Print out error summary
		foreach ($this->errors as $type => $count) {
			echo "--- parsing errors - $type: $count\n";
		}

		return $events;
	}
	private function reportError(array $row, string $type) {
		if ( !isset( $this->errors[$type] ) ) {
			$this->errors[$type] = 0;
		}
		$this->errors[$type]++;
		$vals = array_map( function($v) { return urldecode($v); }, $row );
		echo "-- EVENT_ERROR - $type: " . implode(' ', $vals) . "\n";
	}

	private function insertEventsInDB(array $events) {
		$dryRun = $this->hasOption( 'dry-run' );
		$total = count($events);
		echo "--- Inserting new events: $total\n";

		$dbw = wfGetDB( DB_MASTER );
		$batchSize = 1000;
		for ($offset = 0; $offset < $total; $offset += $batchSize) {
			$batch = array_slice($events, $offset, $batchSize);
			if ($dryRun) {
				foreach ($batch as $event) { echo "-- (dry) INSERT_EVENT: " . implode(', ', $event) . "\n"; }
			} else {
				$dbw->insert( 'event_log', $batch, __METHOD__ );
			}
		}
	}

	private function updateLogFilesInDB(string $expiryDate, array $newLogs) {
		$dryRun = $this->hasOption( 'dry-run' );
		$prefix = $dryRun ? ' (dry)': ' ';

		echo "--- Deleting old log files from DB\n";

		$where = "ef_name < 's3://fastlyeventlog/en/{$expiryDate}'";
		$rowsToDel = wfGetDB(DB_REPLICA)->select('event_files', '*', $where, __METHOD__);
		foreach ($rowsToDel as $r) {
			echo "--{$prefix} DELETE_LOG: {$r->ef_name}\n";
		}
		if ( !$dryRun ) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete('event_files', $where, __METHOD__);
		}

		echo "--- Inserting new log files into DB\n";

		foreach ($newLogs as $logName) {
			echo "--{$prefix} INSERT_LOG: $logName\n";
		}
		if ( !$dryRun ) {
			$rows = array_map( function($name) { return ['ef_name' => $name]; }, $newLogs );
			$dbw->insert('event_files', $rows);
		}
	}
}

$maintClass = "ImportFastlyEvents";
require_once( RUN_MAINTENANCE_IF_MAIN );
