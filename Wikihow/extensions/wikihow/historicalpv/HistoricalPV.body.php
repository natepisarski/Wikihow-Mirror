<?php

global $IP;
require_once "$IP/extensions/wikihow/titus/TitusQueryTool.php";

class HistoricalPV extends UnlistedSpecialPage {
	var $redshift = null;

	function __construct() {
		global $wgHooks;
		parent::__construct('HistoricalPV');

		// moar ram
		ini_set('memory_limit', '2048M');
		$wgHooks['ShowSideBar'][] = ['HistoricalPV::removeSideBarCallback'];
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	function initialize() {
		set_time_limit(600);

		if ($this->isPageRestricted()) {
			$this->getOutput()->setRobotPolicy('noindex,nofollow');
			$this->getOutput()->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return false;
		}

		$this->redshift = wfGetLBFactory()->getExternalLB('redshift')->getConnection(DB_MASTER);

		$this->getOutput()->addModules('ext.wikihow.historicalpv');
		return true;
	}

	function isPageRestricted() {
		global $wgIsToolsServer, $wgIsTitusServer, $wgIsDevServer;

		$userGroups = $this->getUser()->getGroups();

		return !($wgIsToolsServer || $wgIsTitusServer || $wgIsDevServer)
			|| $this->getUser()->isBlocked()
			|| !in_array('staff', $userGroups);
	}

	function execute($par) {
		$redis = self::getRedis();
		if (!$this->initialize()) {
			return;
		}

		if ($this->getRequest()->wasPosted()) {
			$job_id = $this->handleQuery();
		}

		$this->getOutput()->setPageTitle('Historical Pageview Analysis');
		EasyTemplate::set_path(__DIR__ . '/');

		if ($redis) {
			$job_status = $redis->hGetAll('sidekiq-andromedon:status');
		}

		$vars = [
			"redshift_fields" => $this->getRedshiftFields(),
			"job_id" => ($job_id ? $job_id : ""),
			"job_status" => $job_status,
		];
		$this->getOutput()->addHtml(EasyTemplate::html('resources/templates/historicalpv.tmpl.php', $vars));
	}

	function getRedshiftFields() {
		$result = [];
		$dbResult = $this->redshift->select(
		  "pg_table_def",
		  "pg_table_def.column",
		  ["tablename" => "titus_historical_intl"],
		  __METHOD__,
		  ["ti_page_id ASC", "ti_date_stamp ASC"]
		);
		foreach ($dbResult as $row) {
			$result[] = $row->column;
		}

		return $result;
	}

	private function getRedis() {
		$redis = WHRedis::getConnection();
		return $redis;
	}

	private function utf8ize($d) {
		if (is_array($d)) {
			foreach ($d as $k => $v) {
				$d[$k] = self::utf8ize($v);
			}
		} elseif (is_string ($d)) {
			return utf8_encode($d);
		}

		return $d;
	}

	function handleQuery() {
		global $wgIsTitusServer;
		$col = $this->getRequest()->getVal('col');
		$dates = self::parseDates();
		$lines = $this->getRequest()->getVal('urls');
		if ($lines == "") {
			$lines = file_get_contents($this->getRequest()->getFileTempName('upload_file'));
			$lines = preg_replace('/(\r\n|\r|\n)/s', "\n", $lines); // Normalize windows line endings...
		}
		$pages = TitusQueryTool::getIdsFromUrls($lines);

		if (count($dates) == 0 || count($lines) == 0) {
			return false;
		}

		$job_id = uniqid();
		$data_array = [
			'id' 		=> self::utf8ize($job_id),
			'col'		=> self::utf8ize($col),
			'dates' => self::utf8ize($dates),
			'pages' => self::utf8ize($pages),
			'email' => self::utf8ize($this->getRequest()->getVal('email')),
		];
		$data_json = json_encode($data_array);

		$filename = ($wgIsTitusServer ? '/data/sidekiq/tmp/' : '/tmp/') . $job_id . '.json';
		file_put_contents($filename, json_encode($data_array));

		try {
			$redis = self::getRedis();
			$now = microtime(true);
			$job_json = json_encode([
				'class' => 'CohortWorker',
				'queue' => 'sidekiq-andromedon:queue:default',
				'args' => [ $job_id ],
				'retry' => true,
				'jid' => $job_id,
				'created_at' => $now,
				'enqueued_at' => $now,
			]);

			$redis->lpush('sidekiq-andromedon:queue:default', $job_json);
			$redis->sadd('sidekiq-andromedon:queues', 'default');
			$redis->hsetnx('sidekiq-andromedon:status', $job_id, 'Queued');
			return $job_id;
		} catch (Exception $e) {
			wfDebugLog('Redis', $e);
			return false;
		}
	}

	function parseDates() {
		$dates = [];
		$dateType = $this->getRequest()->getVal('date_type');
		if ($dateType == 'specific') {
			foreach (explode("\n", $this->getRequest()->getVal('dates')) as $d) {
				$date = new DateTime($d);
				$dates[] = $date->format('Y-m-d');
			}
		} else {
			$dateStart = new DateTime($this->getRequest()->getVal('date_start'));
			$dateEnd = new DateTime($this->getRequest()->getVal('date_end'));

			switch ($dateType) {
				case 'weekly': // looking for fridays, technically. sunday is 0
					$dateStartWeekday = $dateStart->format('w');
					// advance to the next friday if you didn't give me one
					if ($dateStartWeekday != 5) {
						$mod = 5 - $dateStartWeekday;
						date_modify($dateStart, "$mod days");
					}
					while ($dateStart <= $dateEnd) {
						$dates[] = $dateStart->format('Y-m-d');
						date_modify($dateStart, '+1 week');
					}
					break;
				case 'monthly':
					// cheat, and let php give us the last friday of whatever month you selected
					$dateStart = strtotime('last friday of ' . $dateStart->format('Y-m-d'));
					$dateStart = new DateTime("@$dateStart");
					$dateEnd = strtotime('last friday of ' . $dateEnd->format('Y-m-d'));
					$dateEnd = new DateTime("@$dateEnd");

					$now = new DateTime('now');
					while ($dateStart <= $dateEnd && $dateStart <= $now) {
						$dates[] = $dateStart->format('Y-m-d');
						date_modify($dateStart, '+7 days');
						// hack so we don't skip too many days.
						// php seems to always jump 30-ish days when ask for +1 month, which messes w/ february
						date_modify($dateStart, 'last friday of ' . $dateStart->format('Y-m-d'));
					}
					break;
				default: // if we get anything other than 'daily' ... treat it as 'daily'.
					while ($dateStart <= $dateEnd) {
						$dates[] = $dateStart->format('Y-m-d');
						date_modify($dateStart, '+1 day');
					}
					break;
			}
		}
		return $dates;
	}
}
