<?php

require_once __DIR__ . '/../../Maintenance.php';

class moveAllSummaries extends Maintenance {

	const LOG = '/var/log/wikihow/move_summaries.log';
	const DESIRED_POSITION = 'bottom';
	const COMMENT = 'Moving summary to the bottom';

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		global $wgUser;
		$wgUser = User::newFromName('MiscBot');

		$rows = DatabaseHelper::batchSelect(
			'titus_copy',
			[
				'ti_page_id',
				'ti_page_title'
			],
			[
				'ti_language_code' => 'en',
				'ti_summarized' => 1,
				// 'ti_page_id' => 2571103
			],
			__METHOD__,
			[
				// 'LIMIT' => 5,
				'ORDER BY' => 'ti_page_id'
			]
		);

		$count = 0;
		$move_count = 0;

		foreach ($rows as $row) {
			$result = $this->processSummary($row->ti_page_id);

			if ($result) {
				$this->logLine('https://www.wikihow.com/'.$row->ti_page_title);
				$move_count++;
			}

			$count++;
			if ($count % 5000 == 0) usleep(500000);
		}

		print "\nDone. $move_count out of $count summaries moved.\n";
	}

	private function processSummary(int $page_id): bool {
		$title = Title::newFromId($page_id);
		if (!$title || !$title->exists()) return false;

		$rev = Revision::newFromTitle($title);
		if (!$rev) return false;

		$result = $this->updateSummaryPosition($title);
		if ($result) $this->purgePage($title);

		return $result;
	}

	private function updateSummaryPosition(Title $title): bool {
		$result = false;
		$page_title = $title->getText();

		$summary = Title::newFromText($page_title, NS_SUMMARY);

		if ($summary) {
			$summary_data = SummarySection::summaryData($page_title);

			$position = $summary_data['at_top'] ? 'top' : 'bottom';
			if ($position == self::DESIRED_POSITION) return false; //already where we want it

			$quicksummary_template = 	'{{'.SummarySection::QUICKSUMMARY_TEMPLATE_PREFIX.
																self::DESIRED_POSITION.'|'.
																$summary_data['last_sentence'].'|'.
																$summary_data['content'].'}}';

			$content = ContentHandler::makeContent($quicksummary_template, $summary);

			$page = WikiPage::factory($summary);
			$status = $page->doEditContent($content, self::COMMENT);

			if ($status->isOK()) {
				Hooks::run('QuickSummaryEditComplete', [ $summary, $title ] );
				$result = !empty( $status->value['revision'] );
			}
		}

		return $result;
	}

	private function purgePage(Title $title) {
		$page = WikiPage::factory($title);

		//purge the page to immediately show the new summary
		if (!empty($page)) $page->doPurge();

		//purge the title to force the api to grab the new summary (lag of 2-3m)
		if (!empty($title)) $title->purgeSquid();
	}

	private function logLine(string $txt) {
		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = "moveAllSummaries";
require_once RUN_MAINTENANCE_IF_MAIN;
