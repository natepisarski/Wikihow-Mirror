<?php

require_once __DIR__ . '/../../Maintenance.php';

class CheckSummaryTemplatesForLastSentence extends Maintenance {

	const LOG = '/var/log/wikihow/summary_template_last_sentence.log';

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
		$ls_count = 0;

		foreach ($rows as $row) {
			if ($this->summaryHasLastSentence($row->page_title)) {
				$this->logIt($row->page_title);
				print 'https://www.wikihow.com/'.$row->page_title."\n";
				$ls_count++;
			}
			$count++;
			if ($count % 1000 == 0) usleep(500000);
		}

		print "Done. $ls_count out of $count summaries had last sentences.\n";
	}

	private function summaryHasLastSentence(string $page_title = ''): bool {
		$summary_data = SummarySection::summaryData($page_title);
		return !empty($summary_data['content']) && !empty($summary_data['last_sentence']);
	}

	private function logIt($page_title) {
		$txt = 'https://www.wikihow.com/'.$page_title;

		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = "CheckSummaryTemplatesForLastSentence";
require_once RUN_MAINTENANCE_IF_MAIN;
