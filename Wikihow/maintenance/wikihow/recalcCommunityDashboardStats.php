<?php
//
// Refresh the stats in the community dashboard page in memcache every
// REFRESH_SECONDS seconds
//

// use the spare DB for community dashboard stats since the process is expensive and
// not mission critical
define('WH_USE_BACKUP_DB', true);
define('USES_TITUS_MEMCACHE_RELAY', true);

require_once __DIR__ . '/../Maintenance.php';

class RefreshDashboardStats extends Maintenance {

	// the directory where we store the log file and PID file
	const BASE_DIR = '/data/community_dashboard/';

	// refresh the data once a minute
	const REFRESH_SECONDS = 60.0;

	// stop after 5 minutes roughly; the service will be restarted a minute
	// after it is stopped
	const STOP_AFTER_ERRORS = 5;

	const TOKEN_FILE = 'refresh-stats-token.txt';
	const LOG_FILE = 'log.txt';

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Refresh dashboard stats constantly in a loop";
		$this->addOption( 'fake-stats', 'Pull stats once then just replay that', false, false, 'f' );
	}

	public function execute() {
		global $IP, $wgDebugLogFile;

		require_once "$IP/extensions/wikihow/dashboard/CommunityDashboard.php";
		require_once "$IP/extensions/wikihow/dashboard/CommunityDashboard.body.php";

		$wgDebugLogFile = '';

		// We need this hostname check because it's happened multiple times that
		// this daemon is started on the wrong server in production. It should
		// only ever run on the titus server.
		$hostname = gethostname();
		if (strpos($hostname, 'tools') !== false) {
			print "error: community dashboard must be run on the titus server in production! Exiting...\n";
			die(1);
		}

		$this->dataCompileLoop();
	}

	private static function getToken() {
		$token = @file_get_contents(self::BASE_DIR . self::TOKEN_FILE);
		$token = (int)trim($token);

		return $token;
	}

	private static function log($str) {
		$date = date('m/d/Y H:i:s');
		file_put_contents(self::BASE_DIR . self::LOG_FILE, $date . " " . $str . "\n", FILE_APPEND);
	}

	private function dataCompileLoop() {
		self::log("using database master: " . WH_DATABASE_MASTER);
		$origToken = self::getToken();

		$numErrors = 0;
		$stopMsg = '';

		$data = new DashboardData();

		// The dashboard is very susceptible to going down when we're doing
		// maintenance on our spare server. Using this flag is a way to hold
		// the stats steady by reading them once from the master DB and not again
		// until the daemon is restarted.
		$fakeStats = $this->getOption('fake-stats', false);
		if ($fakeStats) {
			self::log("running with 'fake-stats' option turned on");
			$data->fetchOnFirstCallOnly();
		}

		$staticData = $data->loadStaticGlobalOpts();
		$baselines = (array)json_decode($staticData['cdo_baselines_json']);
		DashboardWidget::setBaselines($baselines);

		// Run the data compilation repeatedly, until token changes
		while (true) {
			$start = microtime(true);
			$success = $data->compileStatsData();
			$end = microtime(true);
			$delta = $end - $start;

			if ($success) {
				self::log( sprintf('data refresh took %.3fs', $delta) );
				$numErrors = 0;
			} else {
				self::log( sprintf('error was detected in data refresh (%.3fs)', $delta) );
				$numErrors++;
				if ($numErrors >= self::STOP_AFTER_ERRORS) {
					self::log( sprintf('there were %d errors in a row. stopping daemon.', self::STOP_AFTER_ERRORS) );
					break;
				}
			}

			$until_refresh_seconds = self::REFRESH_SECONDS - $delta;
			if ($until_refresh_seconds >= 0.0) {
				$secs = (int)ceil($until_refresh_seconds);
				sleep($secs);
			}

			$token = self::getToken();
			if ($token != $origToken) {
				self::log( 'stop daemon requested through token change.' );
				break;
			}
		}
	}

}

$maintClass = 'RefreshDashboardStats';
require_once RUN_MAINTENANCE_IF_MAIN;
