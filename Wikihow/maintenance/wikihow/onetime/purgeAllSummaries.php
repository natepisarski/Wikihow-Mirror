<?php

require_once __DIR__ . '/../../Maintenance.php';

class PurgeAllSummaries extends Maintenance {

	const LOG = '/var/log/wikihow/summary_purge.log';

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$rows = DatabaseHelper::batchSelect(
			'page',
			['page_title'],
			['page_namespace' => NS_SUMMARY],
			__METHOD__,
			[
				// 'LIMIT' => 5,
				'ORDER BY' => 'page_title'
			]
		);

		$count = 0;
		$purge_count = 0;

		foreach ($rows as $row) {
			if ($this->purgeArticleFromSummary($row->page_title)) {
				$this->logIt($row->page_title);
				print 'https://www.wikihow.com/'.$row->page_title."\n";
				$purge_count++;
			}
			$count++;
			if ($count % 1000 == 0) usleep(500000);
		}

		print "Done. $purge_count out of $count articles have been purged.\n";
	}

	private function purgeArticleFromSummary(string $page_title): bool {
		$title = Title::newFromText($page_title);
		if (!$title || !$title->exists()) return false;

		$page = WikiPage::factory($title);
		if (!$page) return false;

		return $page->doPurge();
	}

	private function logIt(string $page_title) {
		$txt = 'https://www.wikihow.com/'.$page_title;

		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = "PurgeAllSummaries";
require_once RUN_MAINTENANCE_IF_MAIN;
