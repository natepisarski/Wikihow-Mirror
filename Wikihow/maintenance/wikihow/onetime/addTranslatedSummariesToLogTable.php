<?php

require_once __DIR__ . '/../../Maintenance.php';

class AddTranslatedSummariesToLogTable extends Maintenance {

	const LOG = '/var/log/wikihow/summary_log_update.log';

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Import already-translated summaries from a spreadsheet';
		$this->addOption('file', 'File name', false, true, 'f');
		$this->addOption('limit', 'Number of rows to do', false, true, 'l');
	}

	public function execute() {
		$filename = $this->getOption('file');
		$limit = intval($this->getOption('limit'));

		$summaries = [];
		$language_code = '';

		$handle = fopen($filename, 'r');
		$count = 0;
		while ($line = fgets($handle)) {
			if ($limit && $count > $limit) break;

			if ($count > 0) {
				$parts = explode("\t", $line);

				$page_id_en = intval(trim($parts[0]));
				$page_title_en = trim($parts[1]);
				$page_id_intl = intval(trim($parts[2]));
				$page_title_intl = trim($parts[3]);
				$language_code = trim($parts[4]);

				if (empty($page_id_en) || empty($page_title_en) || empty($page_id_intl) ||
					empty($page_title_intl) || empty($language_code))
				{
					print_r($parts);
					continue;
				}

				$summaries[] = [
					'page_id_en' => $page_id_en,
					'page_title_en' => $page_title_en,
					'page_id_intl' => $page_id_intl,
					'page_title_intl' => $page_title_intl,
					'language_code' => $language_code
				];
			}

			$count++;
		}

		foreach ($summaries as $summary_data) {
			$res = $this->logNewSummary($summary_data) ? 'true' : 'false';
			$this->logLine($summary_data['language_code']."\t".$summary_data['page_id_intl']."\t".$res);
		}
	}

	private function logNewSummary(array $data): bool {
		if (empty($data)) return false;

		return wfGetDB(DB_MASTER)->upsert(
			'translate_summaries_log',
			[
				'ts_page_id_en' 					=> $data['page_id_en'],
				'ts_page_title_en' 				=> $data['page_title_en'],
				'ts_page_id_intl' 				=> $data['page_id_intl'],
				'ts_page_title_intl' 			=> $data['page_title_intl'],
				'ts_language_code' 				=> $data['language_code'],
				'ts_translated'						=> 1,
				'ts_created_timestamp' 		=> wfTimeStampNow(),
				'ts_translated_timestamp' => wfTimeStampNow()
			],
			['ts_page_id_en','ts_language_code'],
			[
				'ts_page_title_en = VALUES(ts_page_title_en)',
				'ts_page_id_intl = VALUES(ts_page_id_intl)',
				'ts_page_title_intl = VALUES(ts_page_title_intl)',
				'ts_language_code = VALUES(ts_language_code)',
				'ts_translated = VALUES(ts_translated)',
				'ts_created_timestamp = VALUES(ts_created_timestamp)',
				'ts_translated_timestamp = VALUES(ts_translated_timestamp)'
			],
			__METHOD__
		);
	}

	private function logLine($txt) {
		print $txt."\n";
		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}

}

$maintClass = 'AddTranslatedSummariesToLogTable';
require_once RUN_MAINTENANCE_IF_MAIN;
