<?php

require_once __DIR__ . '/../../Maintenance.php';

class GetExistingSummaries extends Maintenance {

	const LOG = '/var/log/wikihow/summaries.csv';

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$this->addToFile('url	summary	position');

		$rows = DatabaseHelper::batchSelect(
			'titus_copy',
			[
				'ti_page_id',
				'ti_page_title'
			],
			[
				'ti_language_code' => 'en',
				'ti_summarized' => 1,
				// 'ti_page_id' => 9909584
			],
			__METHOD__,
			[
				// 'LIMIT' => 4,
				'ORDER BY' => 'ti_page_id'
			]
		);

		$count = 0;

		foreach ($rows as $row) {
			$old_summary = $this->oldSummaryData($row->ti_page_id);

			if (!empty($old_summary) && isset($old_summary['content']) && !empty($old_summary['content'])) {
				$line = $this->formatData($old_summary, $row->ti_page_title);
				$this->addToFile($line);
				// print $line."\n";
				$count++;
			}
		}

		print "Done. $count summaries grabbed.\n";
	}

	private function oldSummaryData($page_id): array {
		$title = Title::newFromId($page_id);
		if (!$title || !$title->exists()) return [];

		$rev = Revision::newFromTitle($title);
		if (!$rev) return [];

		$wikitext = ContentHandler::getContentText( $rev->getContent() );

		if ($this->summaryTemplateExistsInArticle($title, $wikitext)) return [];

		$summary_data = SummaryEditTool::oldSummaryData($wikitext);
		$summary_data['content'] = $this->niceSummary($summary_data['content'], $title);

		return $summary_data;
	}

	private function summaryTemplateExistsInArticle($title, $wikitext): bool {
		$namespace = MWNamespace::getCanonicalName(NS_SUMMARY);
		$title_regex = '('.preg_quote($title->getText()).'|'.preg_quote($title->getDBKey()).')';
		return preg_match('/{{'.$namespace.':'.$title_regex.'}}/i', $wikitext);
	}

	private function niceSummary($summary, $title) {
		global $wgParser;
		$options = new ParserOptions;
		$options->setTidy( true );
		$out = $wgParser->parse($summary, $title, $options);
		$summary = $out->getText();
		$summary = preg_replace('/(\t|\n|\r)/m',' ',$summary);
		return $summary;
	}

	private function formatData($summary_data, $page_title) {
		if (isset($summary_data['at_top']))
			$position_text = $summary_data['at_top'] ? 'top' : 'bottom';
		else
			$position_text = '';

		$line = 'https://www.wikihow.com/'.$page_title.'	'.
						$summary_data['content'].'	'.
						$position_text;

		return $line;
	}

	private function addToFile($txt) {
		$logfile = $this->getOption("logFile", self::LOG);

		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}

}

$maintClass = "GetExistingSummaries";
require_once RUN_MAINTENANCE_IF_MAIN;
