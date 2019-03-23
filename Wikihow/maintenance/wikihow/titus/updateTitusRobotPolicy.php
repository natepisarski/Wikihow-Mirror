<?php

require_once( __DIR__ . '/../../Maintenance.php');
require_once( $IP . '/extensions/wikihow/titus/Titus.class.php');

class CheckRobotPolicy extends Maintenance {
	private $testServer, $titusdb;

	public function __construct() {
		parent::__construct();
		$this->addOption('addpages','Add pages', false, false);
		$this->addOption('spider', 'Spider', false, false);
		$this->addOption('testspider', 'Test spider on dev server', false, true);
		$this->addOption('username', 'Username for host', false, true);
		$this->addOption('password', 'Password for host', false, true);
		$this->testServer = false;
		$this->mDescription = "Spider for the robot policy";
	}

	# Add pages to robot_policy which are missing
	private function addPagesToRobotPolicy($lang) {

		$dbw = wfGetDB(DB_MASTER);	
		$db = Misc::getLangDB($lang);
		$baseURL = Misc::getLangBaseURL($lang);
		$titusDb = TitusDB::getDBName();
		$sql = 'INSERT INTO ' . $titusDb . '.robot_policy(rp_language_code,rp_page_id) SELECT ' . $dbw->addQuotes($lang) . ' AS rp_language_code, page_id AS rp_page_id FROM ' . $db . '.page LEFT JOIN ' . $titusDb . '.robot_policy rp ON rp.rp_language_code=' . $dbw->addQuotes($lang) . ' AND rp.rp_page_id=page_id WHERE page_namespace=0 AND page_is_redirect=0 AND rp.rp_page_id IS NULL';
		$dbw->query($sql, __METHOD__);

	}
	private function getBaseURL($lang) {
		if ($this->testServer) {
			return("http://localhost");
		}
		else {
			return(Misc::getLangBaseURL($lang));
		}
	}

	private function getHostURL($lang) {
		if ($this->testServer) {
			if ($lang == "en") {
				return($this->testServer);
			}
			else {
				return($lang . "." . $this->testServer);
			}
		} else {
			return(Misc::getLangDomain($lang));
		}
	}

	private function getUrl($lang, $pageId) {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT * FROM " . Misc::getLangDB($lang) . ".page WHERE page_id=" . $dbr->addQuotes($pageId) . " AND page_namespace=0 AND page_is_redirect=0";
		$res = $dbr->query($sql, __METHOD__);
		$url = "";
		foreach ( $res as $row ) {
			$url = $this->getBaseURL($lang) . '/' . urlencode($row->page_title);
		}

		$db = $this->titusdb->getTitusDB();
	
		// If we found the page, fetch it. If not, delete it.
		if ( $url ) {
			$found = true;
			$updateArr = array('rp_last_checked' => wfTimestampNow());
			$curlStart = time();
			$ch = curl_init();
			//print "Getting URL: " . $url . "\n";
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $this->getHostURL($lang)));
			curl_setopt($ch, CURLOPT_USERAGENT, 'wikiHow Titus Robot Policy Checker');
			if ($this->hasOption('username') && $this->hasOption('password')) {
				curl_setopt($ch, CURLOPT_USERPWD, $this->getOption('username') . ':' . $this->getOption('password'));
			}
			$requestStartTime = microtime(true);
			$html = curl_exec($ch);
			$responseTime = 10000*(microtime(true) - $requestStartTime);
			$updateArr['rp_response_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			$robotPolicy = "";
			if (preg_match('@<meta name="robots" content="([^"]+)"@', $html, $matches)) {
				print "Found robot policy: $url\n";
				$updateArr['rp_last_updated'] = wfTimestampNow();
				$updateArr['rp_policy'] = $matches[1];
				$updateArr['rp_response_time'] = $responseTime;
			} else {
				print "No robot policy found: $url\n";
			}
			$db->update('robot_policy', $updateArr, array('rp_page_id' => $pageId, 'rp_language_code' => $lang), __METHOD__);
		}
		else {
			$db->delete('robot_policy', array('rp_page_id' => $pageId, 'rp_language_code' => $lang), __METHOD__);
		}
	}
	public function execute() {
		global $wgActiveLanguages;

		$langs = $wgActiveLanguages;
		$langs[] = 'en';

		$this->titusdb = new TitusDB();
		if ( $this->hasOption( 'addpages' ) ) {
			foreach ( $langs as $lang ) {
				$this->addPagesToRobotPolicy($lang);
			}		
		
		}
		if ( $this->hasOption( 'spider' ) ) {
			$date = date('Y/m/d H:i');
			print "$date Beginning spidering\n";
			$dbr = wfGetDB(DB_REPLICA);
			$lastCheckedMin = wfTimestamp(TS_MW, strtotime("-1 hour", strtotime(date('YmdHis', time()))));
			$sql = 'select rp_language_code, rp_page_id from ' . TitusDB::getDBName() . '.robot_policy where (rp_last_checked is NULL or rp_last_checked < ' . $dbr->addQuotes($lastCheckedMin) . ') order by rp_last_updated asc, rand() limit 50000';
			$res = $dbr->query($sql, __METHOD__);
			$rows = array();
			foreach ( $res as $row ) {
				$rows[] = $row;
			}
			$date = date('Y/m/d H:i');
			$count = count($rows);
			print "$date Read {$count} rows\n";
			$res->free();
			foreach ( $rows as $row ) {
				$startTime = microtime(true);
				$this->getUrl($row->rp_language_code, $row->rp_page_id);
				// Introduce a delay, so we don't query more than once a second.
				$curTime = microtime(true);
				if ( ($curTime - $startTime) < 1.0) {
					usleep( 1000000.0 * (1.0 - ($curTime - $startTime)));
				}
			}
		} elseif ($this->hasOption('testspider')) {
			$this->testServer = $this->getOption('testspider');
			$dbr = wfGetDB(DB_REPLICA);
			$lastCheckedMin = wfTimestamp(TS_MW, strtotime("-1 hour", strtotime(date('YmdHis', time()))));
			$sql = 'select rp_language_code, rp_page_id from ' . TitusDB::getDBName() . '.robot_policy where (rp_last_checked is NULL or rp_last_checked < ' . $dbr->addQuotes($lastCheckedMin) . ') order by rp_last_updated asc, rand() limit 50000';
			$res = $dbr->query($sql, __METHOD__);
			$rows = array();
			foreach ( $res as $row ) {
				$rows[] = $row;
			}
			$res->free();
			foreach ( $rows as $row ) {
				$startTime = microtime(true);
				$this->getUrl($row->rp_language_code, $row->rp_page_id);
			}
		}
	}
}
$maintClass = "CheckRobotPolicy";
require_once RUN_MAINTENANCE_IF_MAIN;
