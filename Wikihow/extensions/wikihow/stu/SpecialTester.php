<?php

/*
 * Ajax end-point for logging some user stats for anon visitors.
 */
class SpecialTester extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('Tester');
	}

	public function execute($par) {
		$start = microtime(true);
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);

		$dbr = wfGetDB(DB_SLAVE);
		for ($i = 0; $i < 100; $i++) {
			usleep(5000);
			$dbr->selectField('page',
				'page_title',
				['page_namespace' => NS_MAIN, 'page_is_redirect' => 0, 'page_random' => wfRandom()]);
		}

		$done = microtime(true) - $start;
		print sprintf("%.1fms\n", 1000.0 * $done);
	}

}
