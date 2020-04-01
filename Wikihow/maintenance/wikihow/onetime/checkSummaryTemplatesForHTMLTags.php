<?php

require_once __DIR__ . '/../../Maintenance.php';

class CheckSummaryTemplatesForHTMLTags extends Maintenance {

	const LOG = '/var/log/wikihow/summary_template_html_tags.log';

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$rows = DatabaseHelper::batchSelect(
			'page',
			[
				'page_id',
				'page_title'
			],
			[
				'page_namespace' => NS_SUMMARY
			],
			__METHOD__,
			[
				// 'LIMIT' => 5,
				'ORDER BY' => 'page_id'
			]
		);

		$count = 0;
		$html_count = 0;

		foreach ($rows as $row) {
			if ($this->summaryHasHTML($row->page_id)) {
				$this->logIt($row->page_title);
				print 'https://www.wikihow.com/'.$row->page_title."\n";
				$html_count++;
			}
			$count++;
			if ($count % 1000 == 0) usleep(500000);
		}

		print "Done. $html_count out of $count summaries had HTML tags.\n";
	}

	private function summaryHasHTML($page_id): bool {
		$title = Title::newFromId($page_id);
		if (!$title || !$title->exists()) return false;

		$rev = Revision::newFromTitle($title);
		if (!$rev) return false;

		return $this->tagsExistInSummary(ContentHandler::getContentText( $rev->getContent() ));
	}

	private function tagsExistInSummary(string $summary): bool {
		$stripped_summary = strip_tags($summary,'<br>'); //<br> gets a pass
		return strcmp($summary, $stripped_summary) !== 0;
	}

	private function logIt($page_title) {
		$txt = 'https://www.wikihow.com/'.$page_title;

		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = "CheckSummaryTemplatesForHTMLTags";
require_once RUN_MAINTENANCE_IF_MAIN;
