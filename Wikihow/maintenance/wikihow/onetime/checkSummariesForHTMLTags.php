<?php

require_once __DIR__ . '/../../Maintenance.php';

class checkSummariesForHTMLTags extends Maintenance {

	const LOG = '/var/log/wikihow/summary_html_tags.log';
	var $tags = [];

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$rows = DatabaseHelper::batchSelect(
			'titus_copy',
			[
				'ti_page_id',
				'ti_page_title'
			],
			[
				'ti_language_code' => 'en',
				'ti_summarized' => 1,
				// 'ti_page_id' => 1868
			],
			__METHOD__,
			[
				// 'LIMIT' => 500,
				'ORDER BY' => 'ti_page_id'
			]
		);

		$count = 0;

		foreach ($rows as $row) {
			$result = $this->processSummary($row->ti_page_id);
			$count++;
			if ($count % 5000 == 0) usleep(500000);
		}

		$this->logIt();
		print "\nDone. $count articles checked.\n";
	}

	private function processSummary($page_id) {
		$title = Title::newFromId($page_id);
		if (!$title || !$title->exists()) return false;

		$rev = Revision::newFromTitle($title);
		if (!$rev) return false;

		$wikitext = ContentHandler::getContentText( $rev->getContent() );

		if ($this->summaryTemplateExistsInArticle($title, $wikitext)) return false;

		$old_summary_data = $this->oldSummaryData($wikitext);
		$this->checkForTags($old_summary_data);
	}

	private function checkForTags($old_summary_data) {
		preg_match_all('/(<.*?>)/', $old_summary_data['content'], $tags);
		$tags = !empty($tags[0]) ? $tags[0] : [];

		foreach ($tags as $tag) {
			$this->addTagCount($tag);
		}
	}

	private function addTagCount($tag) {
		$tag = preg_replace('/(<|>)/m', '', trim($tag));
		if (empty($tag) || $tag[0] == '/') return;

		$first_space = strpos($tag,' ');
		if ($first_space) $tag = substr($tag, 0, $first_space);

		if (array_key_exists($tag, $this->tags)) {
			$this->tags[$tag] += 1;
		}
		else {
			$this->tags[$tag] = 1;
		}
	}

	private function summaryTemplateExistsInArticle($title, $wikitext): bool {
		$namespace = MWNamespace::getCanonicalName(NS_SUMMARY);
		$title_regex = '('.preg_quote($title->getText()).'|'.preg_quote($title->getDBKey()).')';
		return preg_match('/{{'.$namespace.':'.$title_regex.'}}/i', $wikitext);
	}

	private function oldSummaryData($wikitext): array {
		$old_section = Wikitext::getSummarizedSection($wikitext);
		if (empty($old_section)) {
			return [
			'content' => '',
			'last_sentence' => '',
			'at_top' => true
			];
		}

		//ignore video for now
		$video_regex = '{{whvid.*?}}';
		$old_summary = preg_replace('/'.$video_regex.'/s', '', $old_section);

		//remove [[Category:*]] lines from old section so we don't replace them
		//and make sure they're on their own line for parsing needs
		$category_regex = '\[\[Category.*?\]\]';
		$old_section = preg_replace('/'.$category_regex.'/m', '', $old_section);
		$old_summary = preg_replace('/('.$category_regex.')/m', "\n$1", $old_summary);

		$summary_lines = [];
		$category_lines = [];
		$lines = explode( PHP_EOL, $old_summary );

		foreach ( $lines as $lineNum => $line ) {
			$header = $lineNum == 0;
			$category_line = strstr($line, '[[Category');
			$other_header = strstr($line, '==');

			if ($header)
				$summary_heading = trim(str_replace('==','',$line));
			elseif ($other_header)
				break;
			elseif ($category_line)
				$category_lines[] = $line;
			else
				$summary_lines[] = $line;
		}

		$at_top = $this->oldSummaryAtTop($wikitext, $old_section);

		return [
			'header' => $summary_heading,
			'content' => implode( "\n", $summary_lines ),
			'last_sentence' => '',
			'at_top' => $at_top,
			'category_lines' => implode( "\n", $category_lines),
			'old_section' => $old_section
		];
	}

	private function oldSummaryAtTop($wikitext, $old_summary): bool {
		$steps = Wikitext::getStepsSection($wikitext);
		$steps_position = !empty($steps) && !empty($steps[0]) ? strpos($wikitext, $steps[0]) : false;
		$summary_position = strpos($wikitext, $old_summary);

		$use_default_value = $summary_position === false || $steps_position === false;

		return $use_default_value || $summary_position < $steps_position;
	}

	private function logIt() {
		$this->logLine('tag	count');

		arsort($this->tags);

		foreach ($this->tags as $tag => $count) {
			$this->logLine($tag.'	'.$count);
		}
	}

	private function logLine($txt) {
		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = "checkSummariesForHTMLTags";
require_once RUN_MAINTENANCE_IF_MAIN;
