<?php

/**
 * import already translated summaries from a spreadsheet like this:
 *
 * whrun --user=apache --lang=de -- php importIntlSummaries.php -f summary_import_de.tsv -l 5
 *
 * SPREADSHEET FORMAT (TSV):
 * language code | Intl id | summary
 */

require_once __DIR__ . '/../../Maintenance.php';

class ImportIntlSummaries extends Maintenance {

	const LOG = '/var/log/wikihow/summary_intl_import.log';

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Import already-translated summaries from a spreadsheet';
		$this->addOption('file', 'File name', false, true, 'f');
		$this->addOption('limit', 'Number of rows to do', false, true, 'l');
	}

	public function execute() {
		global $wgUser;
		//use translator account
		$wgUser = User::newFromName(wfMessage('translator_account')->text());

		$filename = $this->getOption('file');
		$limit = intval($this->getOption('limit'));

		$summaries = [];
		$language_code = '';

		$handle = fopen($filename, 'r');
		$count = 0;
		while ($line = fgets($handle)) {
			if ($count > 0) {
				$parts = explode("\t", $line);

				$summaries[] = [
					'page_id_intl' => $parts[1],
					'content' => $parts[2],
					'last_sentence' => ''
				];

				//only have to set this once
				if ($count == 1) $language_code = $parts[0];
			}

			$count++;
			if ($limit && $count > $limit) break;
		}

		foreach ($summaries as $summary_info) {
			$res = $this->makeSummary($summary_info) ? 'true' : 'false';
			$this->logLine($language_code."\t".$summary_info['page_id_intl']."\t".$res);
		}
	}

	private function makeSummary(array $summary_info): bool {
		if (empty($summary_info) || empty($summary_info['content']) || empty($summary_info['page_id_intl']))
			return false;

		$article_title = Title::newFromId($summary_info['page_id_intl']);
		if (!$article_title) return false;

		$summary = Title::newFromText($article_title->getText(), NS_SUMMARY);
		if (!$summary) return false;

		$summary_position = 'bottom';
		$quicksummary_template = 	'{{'.SummarySection::QUICKSUMMARY_TEMPLATE_PREFIX.
															$summary_position.'|'.
															$summary_info['last_sentence'].'|'.
															$summary_info['content'].'}}';

		$content = ContentHandler::makeContent($quicksummary_template, $summary);
		$comment = wfMessage('summary_edit_log')->text();

		$page = WikiPage::factory($summary);
		$status = $page->doEditContent($content, $comment);

		if ($status->isOK()) {
			$res = $this->addSummaryTemplateToWikiTextIfNeeded($summary_info['page_id_intl']);
			if ($res && $status->isGood()) return true;
		}

		return false;
	}

	private function addSummaryTemplateToWikiTextIfNeeded(int $page_id_intl): bool {
		$result = false;

		$title = Title::newFromId($page_id_intl);
		if (!$title || !$title->exists()) return false;

		$rev = Revision::newFromTitle($title);
		if (!$rev) return false;

		$wikitext = ContentHandler::getContentText( $rev->getContent() );

		$namespace = MWNamespace::getCanonicalName(NS_SUMMARY);
		$title_regex = '('.preg_quote($title->getText()).'|'.preg_quote($title->getDBKey()).')';

		$summary_template_exists = preg_match('/{{'.$namespace.':'.$title_regex.'}}/i', $wikitext);
		if ($summary_template_exists) {
			$page = WikiPage::factory($title);
			$this->purgeIt($page, $title);
			return true;
		}

		$template = '{{'.$namespace.':'.$title->getDBKey().'}}';
		$new_summary_section = $this->prepareNewSummarySection($template);

		$inline_comment = wfMessage('summary_section_notice')->text();

		$wikitext .= 	"\n\n".
									$inline_comment."\n".
									$new_summary_section;

		$content = ContentHandler::makeContent($wikitext, $title);
		$comment = wfMessage('summary_add_log')->text();
		$edit_flags = EDIT_UPDATE | EDIT_MINOR;

		$page = WikiPage::factory($title);
		$status = $page->doEditContent($content, $comment, $edit_flags);

		if ($status->isOK()) {
			$this->purgeIt($page, $title);
			$result = $status->isGood();
		}

		return $result;
	}

	private function prepareNewSummarySection($template) {
		$default_header = '== '.wfMessage('summary_section_default_header')->text().' ==';
		return $default_header."\n".$template;
	}

	private function purgeIt($page, $title) {
		//purge the page to immediately show the new summary
		if (!empty($page)) $page->doPurge();

		//purge the title to force the api to grab the new summary (lag of 2-3m)
		if (!empty($title)) $title->purgeSquid();
	}

	private function logLine($txt) {
		print $txt."\n";
		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}

}

$maintClass = 'ImportIntlSummaries';
require_once RUN_MAINTENANCE_IF_MAIN;
