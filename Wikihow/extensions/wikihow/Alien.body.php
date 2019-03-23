<?php

class Alien extends UnlistedSpecialPage {

    public function __construct() {
		$this->startTime = microtime(true);
        parent::__construct('Alien');
    }

    public function execute($par) {
		header('Cache-Control: no-cache');
		if (defined('WIKIHOW_LIMITED')) {
			print 'nocheck';
		} elseif ($this->quickTestBackend()) {
			$time = microtime(true) - $this->startTime;
			print sprintf('%.3f', 1000*$time);
		} else {
			header('HTTP/1.0 404 Not Found');
			print '-1';
		}
		exit;
	}

	private function quickTestBackend() {
		$dbr = wfGetDB(DB_REPLICA);
		try {
			$res = $dbr->selectField('page', 'page_id',
				array('page_is_redirect' => 0,
					'page_namespace' => NS_MAIN,
					'page_random >= ' . wfRandom()),
				__METHOD__,
				array('ORDER BY' => 'page_random',
					'USE INDEX' => 'page_random'));
		} catch (DBError $e) {
			$res = null;
		}
		if (is_string($res) && intval($res) > 0) {
			return true;
		} else {
			return false;
		}
	}

}
