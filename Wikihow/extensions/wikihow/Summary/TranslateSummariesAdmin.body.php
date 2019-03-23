<?php

class TranslateSummariesAdmin extends UnlistedSpecialPage {
	const TABLE = 'translate_summaries_log';

	private $specialPage;

	private $authorizedGroups = [
		'staff',
		'staff_widget',
		'sysop'
	];

	public function __construct() {
		$this->specialPage = 'TranslateSummariesAdmin';
		parent::__construct($this->specialPage);
	}

	public function execute($par) {
		$out = $this->getOutput();

		if ($this->getLanguage()->getCode() != 'en' || !$this->allowedUser()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$action = $this->getRequest()->getText('action', '');
		if ($action != '') {
			$out->disable();

			if ($action == 'upload_summaries')
				$this->uploadSummaries();
			elseif ($action == 'run_report')
				$this->runReport();
			elseif ($action == 'run_big_report')
				$this->runReport($full = true);
			elseif ($action == 'delete_summaries')
				$this->deleteSummariesFromQueue();

			return;
		}

		$out->setPageTitle(wfMessage('translate_summaries_admin')->text());
		$out->addHTML($this->getToolHtml());
		$out->addModules(['ext.wikihow.translate_summaries_admin']);
	}

	private function allowedUser(): bool {
		$user = $this->getUser();
		return !Misc::isMobileMode() &&
			!$user->isAnon() &&
			Misc::isUserInGroups($user, $this->authorizedGroups);
	}

	private function getToolHtml(): string {
		global $wgActiveLanguages;
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'form_action' => '/Special:TranslateSummariesAdmin',
			'upload_head' => wfMessage('translate_summaries_admin_import_label')->text(),
			'upload_subtext' => wfMessage('translate_summaries_admin_import_sublabel')->text(),
			'import' => wfMessage('translate_summaries_admin_import')->text(),
			'report_head' => wfMessage('translate_summaries_admin_report_label')->text(),
			'report_button' => wfMessage('translate_summaries_admin_report_button')->text(),
			'big_report_head' => wfMessage('translate_summaries_admin_big_report_label')->text(),
			'big_report_text' => wfMessage('translate_summaries_admin_big_report_text')->text(),
			'delete_head' => wfMessage('translate_summaries_admin_delete_label')->text(),
			'delete_text' => wfMessage('translate_summaries_admin_delete_text')->text(),
			'languages' => $wgActiveLanguages,
			'delete_button' => wfMessage('submit')->text(),
			'delete_description' => wfMessage('translate_summaries_admin_delete_description')->text()
		];

		return $m->render('translate_summaries_admin', $vars);
	}

	private function uploadSummaries() {
		$uploadfile = $this->getRequest()->getFileTempName('upload_file');

		// Check file for format and save it to the local dir
		if ( !file_exists( $uploadfile ) || !is_readable( $uploadfile ) ) return;

		$report = [];
		$headers = [
			'Language',
			'INTL article ID',
			'EN article ID',
			'Result'
		];
		$report[] = implode("\t", $headers);

		// Compatibility with mac generated csv files
		ini_set( 'auto_detect_line_endings', TRUE );

		$row = 0;
		if ( ( $fileHandle = fopen( $uploadfile,'r') ) !== FALSE ) {
			while ( $data = fgetcsv( $fileHandle ) ) {
				if (count($data) != 3) continue;

				if ($row > 0) { //skip headers
					$lang = strval($data[0]);
					$intl_id = intval($data[1]);
					$en_id = intval($data[2]);

					$result = $this->addSummaryToTranslate($lang, $intl_id, $en_id);

					$data[] = $result ? 'Imported' : 'Error';
					$report[] = implode("\t", $data);
				}
				$row++;
			}
			fclose( $fileHandle );
		}
		ini_set( 'auto_detect_line_endings', FALSE );

		$this->outputTSV($report, 'summary_import_results');
	}

	private function addSummaryToTranslate(string $language_code, int $page_id_intl, int $page_id_en): bool {
		$title = Title::newFromId($page_id_en);
		if (!$title || !$title->exists()) return false;

		$page_title_en = $title->getDBKey();

		$summary_data = SummarySection::summaryData($page_title_en);
		if (empty($summary_data['content'])) return false;

		$page_title_intl = $this->getIntlTitleFromTitus($language_code, $page_id_intl);

		$ts = new TranslateSummaries($language_code);
		$ts->loadFromEnArticleId($page_id_en);
		if ($ts->exists()) return false;

		$ts->page_id_en = $page_id_en;
		$ts->page_title_en = $page_title_en;
		$ts->page_id_intl = $page_id_intl;
		$ts->page_title_intl = $page_title_intl;
		$result = $ts->save();

		if ($result) $this->logNewSummary($ts, $summary_data);

		return $result;
	}

	private function logNewSummary(TranslateSummaries $ts, array $summary): bool {
		if (empty($ts) || empty($summary)) return false;

		return wfGetDB(DB_MASTER)->upsert(
			self::TABLE,
			[
				'ts_page_id_en' 					=> $ts->page_id_en,
				'ts_page_title_en' 				=> $ts->page_title_en,
				'ts_page_id_intl' 				=> $ts->page_id_intl,
				'ts_page_title_intl' 			=> $ts->page_title_intl,
				'ts_language_code' 				=> $ts->language_code,
				'ts_source_bytes' 				=> self::calculateSummarySourceBytes($summary),
				'ts_created_timestamp' 		=> $ts->created_timestamp
			],
			['ts_page_id_en','ts_language_code'],
			[
				'ts_page_title_en = VALUES(ts_page_title_en)',
				'ts_page_id_intl = VALUES(ts_page_id_intl)',
				'ts_page_title_intl = VALUES(ts_page_title_intl)',
				'ts_language_code = VALUES(ts_language_code)',
				'ts_source_bytes = VALUES(ts_source_bytes)',
				'ts_created_timestamp = VALUES(ts_created_timestamp)'
			],
			__METHOD__
		);
	}

	public static function logSummarySave(TranslateSummaries $ts): bool {
		if (empty($ts)) return false;

		$summary = TranslateSummaries::getENSummaryData($ts->page_id_en);
		$user = RequestContext::getMain()->getUser();

		$dbw = wfGetDB(DB_MASTER);
		$dbName = $dbw->getDBname();
		$dbw->selectDB('wikidb_112');

		$res = $dbw->update(
			self::TABLE,
			[
				'ts_source_bytes' 				=> self::calculateSummarySourceBytes($summary),
				'ts_user_id' 							=> $user->getId(),
				'ts_translated' 					=> $ts->translated,
				'ts_translated_timestamp' => wfTimeStampNow()
			],
			[
				'ts_page_id_en' => $ts->page_id_en,
				'ts_language_code' => $ts->language_code
			],
			__METHOD__
		);

		$dbw->selectDB($dbName);

		return $res;
	}

	public static function deleteSummarySave(TranslateSummaries $ts): bool {
		if (empty($ts)) return false;

		$dbw = wfGetDB(DB_MASTER);
		$dbName = $dbw->getDBname();
		$dbw->selectDB('wikidb_112');

		$res = $dbw->delete(
			self::TABLE,
			[
				'ts_page_id_intl' => $ts->page_id_intl,
				'ts_language_code' => $ts->language_code
			],
			__METHOD__
		);

		$dbw->selectDB($dbName);

		return $res;
	}

	private static function calculateSummarySourceBytes(array $summary): int {
		if (empty($summary)) return 0;
		return strlen($summary['content'].' '.$summary['last_sentence']);
	}

	private function runReport(bool $full = false) {
		$date_from = $this->getRequest()->getText('date_from', '');
		$date_to = $this->getRequest()->getText('date_to', '');

		$report = [];
		$report[] = implode("\t", $this->translateSummariesReportHeaders());

		$where = [];
		$options = [ 'ORDER BY' => 'ts_translated_timestamp DESC' ];

		if (!$full) {
			$where['ts_translated'] = 1;
			if ($date_from != '') $where[] = 'ts_translated_timestamp >= '. wfTimestamp(TS_MW, strtotime($date_from));
			if ($date_to != '') $where[] = 'ts_translated_timestamp <= '. wfTimestamp(TS_MW, strtotime($date_to.' 11:59pm'));
		}
		$res = wfGetDB(DB_REPLICA)->select(self::TABLE, '*', $where, __METHOD__, $options);

		foreach ($res as $row) {
			$report[] = implode("\t", $this->translateSummariesReportRow($row));
		}

		$filename = $full ? 'summary_full' : 'summary_completed';
		$this->outputTSV($report, $filename);
	}

	private function translateSummariesReportHeaders(): array {
		return [
			'en_page_id',
			'en_page_title',
			'intl_lang_code',
			'intl_page_id',
			'intl_page_title',
			'user_text',
			'completed_timestamp',
			'en_bytes',
			'en_summary_url',
			'en_summary_id',
			'intl_summary_url'
		];
	}

	private function translateSummariesReportRow($row): array {
		$summary_en = Title::newFromText($row->ts_page_title_en, NS_SUMMARY);
		$en_summary_url = $summary_en ? 'https://www.wikihow.com/Summary:'.str_replace(' ', '-', $row->ts_page_title_en) : '';
		$en_summary_id = $summary_en ? $summary_en->getArticleId() : '';

		$intl_domain = wfCanonicalDomain($row->ts_language_code);
		$intl_summary_url = 'https://'.$intl_domain.'/Summary:'.$row->ts_page_title_intl;

		$user = $row->ts_user_id ? User::newFromId($row->ts_user_id) : '';
		$user_name = $user ? $user->getName() : '';

		$translated_date = $row->ts_translated_timestamp ? date('Ymd',strtotime($row->ts_translated_timestamp)) : '';

		return [
			$row->ts_page_id_en,
			$row->ts_page_title_en,
			$row->ts_language_code,
			$row->ts_page_id_intl,
			$row->ts_page_title_intl,
			$user_name,
			$translated_date,
			$row->ts_source_bytes,
			$en_summary_url,
			$en_summary_id,
			$intl_summary_url
		];
	}

	private function getIntlTitleFromTitus(string $language_code, int $article_id): string {
		if (empty($language_code) || empty($article_id)) return [];

		return wfGetDB(DB_REPLICA)->selectField(
			'titus_copy',
			[	'ti_page_title' ],
			[
				'ti_language_code' => $language_code,
				'ti_page_id' => $article_id
			],
			__METHOD__
		);
	}

	private function deleteSummariesFromQueue() {
		$language_code = $this->getRequest()->getText('language', '');
		$delete_ids = explode("\n", $this->getRequest()->getText('delete_ids', ''));

		$report = [];
		$headers = [
			'Language',
			'INTL article ID',
			'EN article ID',
			'Result'
		];

		$report[] = implode("\t", $headers);

		if ($language_code != '') {
			$ts = new TranslateSummaries($language_code);

			foreach ($delete_ids as $intl_id) {
				$ts->loadFromIntlArticleId(trim($intl_id));
				$result = $ts->delete() ? 'Deleted' : 'Error';
				$report[] = $language_code."\t".$intl_id."\t".$ts->page_id_en."\t".$result;
			}
		}

		$this->outputTSV($report, 'summary_deleted_results');
	}

	private function outputTSV(array $report, string $filename = '') {
		if (empty($report)) return;
		if ($filename == '') $filename = 'summary_results';

		header('Content-type: application/force-download; charset=UTF-8');
		header('Content-disposition: attachment; filename="'.$filename.'.tsv"');
		print(implode("\n", $report));
	}
}
