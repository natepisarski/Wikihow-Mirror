<?php
/**
 * Send an e-mail about language links, where one or more of the articles have
 * been moved, deleted or redirected.
 */

define('WH_USE_BACKUP_DB', true);

require_once __DIR__ . '/../Maintenance.php';

class SendLangLinksChangedReport extends Maintenance {
	const FORCE_DRY_RUN = false;

	// Default number of days to look behind
	const DEFAULT_DAYS_BACK = 1;

	// If true, do not send e-mails and print to stdout instead
	private $dry_run = false;

	// If true, only print the SQL queries to stdout and exit
	private $queries_only = false;

	public function __construct() {
		parent::__construct();
		$this->mDescription =
			'Generate and send report about language links on article changes';

		$this->addOption(
			'stdout', // long form
			'Do not send e-mails. Instead print results to stdout as a dry run.',
			false, // is option required?
			false, // does option require argument?
			's' // short form
		);

		$this->addOption(
			'redirected',
			'Report redirects. If neither -r, -m or -d are provided, all reports will be generated.',
			false,
			false,
			'r'
		);

		$this->addOption(
			'moved',
			'Report moved articles. If neither -r, -m or -d are provided, all reports will be generated.',
			false,
			false,
			'm'
		);

		$this->addOption(
			'deleted',
			'Report deleted articles. If neither -r, -m or -d are provided, all reports will be generated.',
			false,
			false,
			'd'
		);

		$this->addOption(
			'days',
			'Number of days to look back. Default: ' . self::DEFAULT_DAYS_BACK,
			false,
			true,
			'D'
		);

		$this->addOption(
			'queries',
			'Print queries and exit.',
			false,
			false,
			'q'
		);
	}

	public function execute() {
		$this->dry_run = self::FORCE_DRY_RUN || $this->getOption('stdout');
		$this->queries_only = $this->getOption('queries');

		$daysBack = $this->getOption('days') ?: self::DEFAULT_DAYS_BACK;
		$today = date('Ymd');
		$lowDate = wfTimestamp(
			TS_MW,
			strtotime("-$daysBack day", strtotime($today))
		);

		if (!$this->dry_run && !$this->queries_only) {
			print "Generating reports from $lowDate to " . wfTimestampNow() . "\n";
		}

		$includeRedirectedReport = $this->getOption('redirected');
		$includeMovedReport = $this->getOption('moved');
		$includeDeletedReport = $this->getOption('deleted');

		// If no reports are selected, generate all
		$includeAllReports =
			!($includeRedirectedReport || $includeMovedReport || $includeDeletedReport);

		$reports = array();

		if ($includeAllReports || $includeRedirectedReport) {
			$reports['redirected'] = $this->getRedirectedReport($lowDate);
		}

		if ($includeAllReports || $includeMovedReport) {
			$reports['moved'] = $this->getMovedReport($lowDate);
		}

		if ($includeAllReports || $includeDeletedReport) {
			$reports['deleted'] = $this->getDeletedReport($lowDate);
		}

		if ($this->queries_only) {
			foreach ($reports as $type=>$typeReport) {
				print "==== $type ====\n";
				foreach ($typeReport as $lang=>$langQuery) {
					print "$lang:\n$langQuery\n\n";
				}
			}

			return;
		}

		$tsvReports = implode("\n\n", $this->formatReportsTSV($reports));

		if ($this->dry_run) {
			print "$tsvReports\n";
		} else {
			$msg = $this->generateEmailMessage($reports, $lowDate, $today);
			$this->sendEmailReports($msg, $tsvReports);
		}
	}

	/**
	 * Generate a report for articles that have been redirected and have
	 * translation links.
	 */
	public function getRedirectedReport($lowDate) {
		$dbr = wfGetDB(DB_REPLICA);
		$srcLang = 'en';
		$langData = array();

		foreach ($this->getActiveLangs() as $lang) {
			$query = $this->getRedirectedQuery($srcLang, $lang, $lowDate);
			if ($this->queries_only) {
				$langData[$lang] = $query;
				continue;
			}

			$fieldAliases = $this->getRedirectedQueryFieldAliases($dbr, $srcLang, $lang);

			$res = $dbr->query($query, __METHOD__);

			$langData[$lang] = $this->processDBResults($res, $fieldAliases, DailyEdits::EDIT_TYPE);

			if (!$this->dry_run && !empty($langData[$lang])) {
				print "redirect: $lang: " . count($langData[$lang]) . " articles\n";
			}
		}

		return $langData;
	}

	/**
	 * Generate a report for articles that have been moved and have
	 * translation links.
	 */
	public function getMovedReport($lowDate) {
		return $this->getMovedDeletedReport($lowDate, DailyEdits::MOVE_TYPE);
	}

	/**
	 * Generate a report for articles that have been deleted and have
	 * translation links.
	 */
	public function getDeletedReport($lowDate) {
		return $this->getMovedDeletedReport($lowDate, DailyEdits::DELETE_TYPE);
	}

	/**
	 * Generate a report for articles that have either been moved or deleted
	 * and have translation links.
	 *
	 * The generation of reports for moved and deleted articles has been
	 * consolidated into one method due to their similarity. Public users
	 * of this class should use the individual helper methods
	 * getMovedReport() and getDeletedReport().
	 */
	private function getMovedDeletedReport($lowDate, $editType) {
		$dbr = wfGetDB(DB_REPLICA);
		$srcLang = 'en';
		$langData = array();
		$typeName = self::getEditTypeName($editType);

		foreach ($this->getActiveLangs() as $lang) {
			$query = $this->getMovedDeletedQuery($srcLang, $lang, $lowDate, $editType);
			if ($this->queries_only) {
				$langData[$lang] = $query;
				continue;
			}

			$fieldAliases =
				$this->getMovedDeletedQueryFieldAliases($dbr, $srcLang, $lang, $editType);

			$res = $dbr->query($query, __METHOD__);

			$langData[$lang] = $this->processDBResults($res, $fieldAliases, $editType);

			if (!$this->dry_run && !empty($langData[$lang])) {
				print "$typeName: $lang: " . count($langData[$lang]) . " articles\n";
			}
		}

		return $langData;
	}

	/**
	 * Convert DatabaseBase results into associative arrays for each language.
	 *
	 * Also converts page titles into URLs where possible.
	 */
	private function processDBResults(&$res, $fieldAliases, $editType) {
		$data = array();

		if ($editType === DailyEdits::EDIT_TYPE) {
			$titleAliases = $this->getRedirectedTitleAliases();
		} else {
			$titleAliases = $this->getMovedDeletedTitleAliases();
		}

		foreach ($res as $row) {
			$dataRow = array();
			foreach ($fieldAliases as $fieldAlias) {
				$dataRow[$fieldAlias] = $row->$fieldAlias;

				foreach ($titleAliases as $langKey=>$aliases) {
					if (in_array($fieldAlias, $aliases)) {
						if ($row->$fieldAlias) {
							$domain = Misc::getLangDomain($row->$langKey);
							$url = Misc::makeUrl($row->$fieldAlias, $domain);
							$dataRow[$fieldAlias] = $url;
						}
						break;
					}
				}
			}

			$this->addTitusDataToRow($dataRow, $editType);

			$data[] = $dataRow;
		}

		return $data;
	}

	/**
	 * Add fields from Titus to the given row.
	 */
	private function addTitusDataToRow(&$row, $editType) {
		if ($editType === DailyEdits::EDIT_TYPE) {
			$titusAliasInfo = $this->getRedirectedTitusAliasInfo();
		} else {
			$titusAliasInfo = $this->getMovedDeletedTitusAliasInfo();
		}

		foreach ($titusAliasInfo as $aliasInfo) {
			$titusResult = $this->getTitusData(
				$row[$aliasInfo['lang_key']],
				$row[$aliasInfo['page_id']]
			);

			if ($titusResult === false) {
				$titusRow = array();
				foreach (array_keys($aliasInfo['fields']) as $titusField) {
					$titusRow[$titusField] = NULL;
				}
			} else {
				$titusRow = get_object_vars($titusResult->titus);
			}

			$newFields = array();

			foreach ($aliasInfo['fields'] as $titusField=>$rowField) {
				$newFields[$rowField] = $titusRow[$titusField];
			}

			self::arrayMergeAfter(
				$row,
				$aliasInfo['after'],
				$newFields
			);
		}
	}

	/**
	 * Curl the Titus API to get data from Titus.
	 */
	protected function getTitusData($lang, $page_id) {
		global $wgIsDevServer;

		if ($wgIsDevServer) {
			global $wgServer;
			$apiUrl = $wgServer; // Why does wgServer not work? :(
			$apiUrl = 'http://g.wikidiy.com'; // Just work around it, whatever
		} else {
			$apiUrl = WH_TITUS_API_HOST;
		}

		$httpQuery = http_build_query(
			array(
				'action' => 'titus',
				'subcmd' => 'article',
				'language_code' => $lang,
				'page_id' => $page_id,
				'format' => 'json'
			)
		);

		$url = $apiUrl . '/api.php?' . $httpQuery;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$ret = curl_exec($ch);
		$curlErr = curl_error($ch);

		if ($curlErr) {
			return false;
		} else {
			return json_decode($ret);
		}
	}

	/**
	 * Convert reports into TSV format
	 */
	public function formatReportsTSV($reports) {
		$formattedReports = array();

		foreach ($reports as $type => $langReports) {
			$formattedReport = '';
			foreach ($langReports as $report) {
				if (!empty($report)) {
					foreach ($report as $row) {
						if (empty($formattedReport)) {
							$reportKeys = array_keys($row);
							$formattedReport .= implode("\t", $reportKeys) . "\n";
						}
						$formattedReport .= implode("\t", $row) . "\n";
					}
				}
			}

			$formattedReports[$type] = $formattedReport;
		}

		return $formattedReports;
	}

	/**
	 * Build an e-mail body based on generated reports
	 */
	public function generateEmailMessage($reports, $lowDate, $today) {
		$lowDateStr = $this->formatReadableDate($lowDate);
		$todayStr = $this->formatReadableDate($today);

		$emailBody =
			"Articles with translation links changed from $lowDateStr to $todayStr:";

		$found = false;

		foreach ($reports as $type=>$typeReport) {
			$langs = 0;
			$total = 0;
			foreach ($typeReport as $lang=>$langReport) {
				if (!empty($langReport)) {
					$langs++;
					$total += count($langReport);
				}
			}

			if ($total > 0) {
				$found = true;
				$emailBody .=
					"\n\n$type: $total entries across $langs languages";
			}
		}

		if (!$found) {
			$emailBody .= "\n\nNo articles changed!";
		}

		$emailBody .=
			"\n\nSee the detailed reports in the attached spreadsheet.";

		return $emailBody;
	}

	private function formatReadableDate($date) {
		return
			substr($date, 0, 4) . "-"
			. substr($date, 4, 2) . "-"
			. substr($date, 6, 2);
	}

	/**
	 * Send the reports as an e-mail attachment.
	 */
	private function sendEmailReports($msg, $reportsTSV) {
		// Generate a unique boundary hash for the multipart e-mail body
		$boundaryHash = md5(date('r', time()));
		$attachment = chunk_split(base64_encode($reportsTSV));
		$today = date('Y-m-d', strtotime('today'));

		$body = <<<BODY
--PHP-mixed-$boundaryHash
Content-Type: multipart/alternative; boundary="PHP-alt-$boundaryHash"

--PHP-alt-$boundaryHash
Content-Type: text/plain; charset="UTF-8"

$msg

--PHP-alt-$boundaryHash--

--PHP-mixed-$boundaryHash
Content-Type: text/tab-separated-values; name="lang_links_changed_report_$today.xls"
Content-Transfer-Encoding: base64
Content-Disposition: attachment

$attachment

--PHP-mixed-$boundaryHash--
BODY;

		$subject = 'Translation Links: Changed articles report (generated ' . date('jS F, Y', strtotime('today')) . ')';
		$from = new MailAddress('reports@wikihow.com');
		$to = new MailAddress(implode(',', $this->getRecipientEmailAddresses()));
		$replyTo = new MailAddress('reuben@wikihow.com');
		$contentType = 'multipart/mixed; boundary="PHP-mixed-' . $boundaryHash . '"';

		UserMailer::send(
			$to,
			$from,
			$subject,
			$body,
			$replyTo,
			$contentType
		);

		return;
	}

	private function getRecipientEmailAddresses() {
		return array(
			'international@wikihow.com,jordan@wikihow.com'
		);
	}

	/**
	 * Return the languages for which to generate reports
	 */
	public function getActiveLangs() {
		global $wgActiveLanguages;
		return $wgActiveLanguages;
	}

	/**
	 * Generate a query to fetch redirected articles with translation links
	 *
	 * The query will attempt to get various pieces of data for:
	 * - the old article in the source language
	 * - the new article in the source language
	 * - the old article in the target language
	 * - the new article in the target language
	 * 
	 * The source language is typically English. The old article is the page
	 * being redirected, while the new article is the target of the redirect.
	 *
	 * Note that the new article in the target language is optional (i.e. left
	 * joined). A row will be returned regardless of its existence. Both the
	 * articles in the source language, and the old article in the target language
	 * are mandatory (i.e. inner joined).
	 */
	private function getRedirectedQuery($srcLang, $lang, $lowDate) {
		$dbr = wfGetDB(DB_REPLICA);

		$editType = DailyEdits::EDIT_TYPE;

		$srcLangDB = Misc::getLangDB($srcLang);
		$destLangDB = Misc::getLangDB($lang);
		$primaryDB = self::getPrimaryDB();

		$srcLangDBSafe = $dbr->addIdentifierQuotes($srcLangDB);
		$destLangDBSafe = $dbr->addIdentifierQuotes($destLangDB);
		$primaryDBSafe = $dbr->addIdentifierQuotes($primaryDB);

		$tables = array(
			'de' => "$srcLangDBSafe.daily_edits",
			'tl_new' => "$primaryDBSafe.translation_link",
			'p_new' => "$srcLangDBSafe.page",
			'r' => "$primaryDBSafe.redirect",
			'p_old' => "$srcLangDBSafe.page",
			'tl_old' => "$primaryDBSafe.translation_link",
			'babel_old' => "$srcLangDBSafe.babelfish_articles",
			'babel_new' => "$srcLangDBSafe.babelfish_articles",
			'retrans_old' => "$srcLangDBSafe.retranslatefish_articles",
			'retrans_new' => "$srcLangDBSafe.retranslatefish_articles"
		);

		$fields = $this->getRedirectedQueryFields($dbr, $srcLang, $lang);

		$conds = $this->getQueryConds($dbr, $lowDate, $editType);

		$method = __METHOD__;

		$opts = array(
			'ORDER BY' => array('p_new.page_id ASC')
		);

		$joinConds = array(
			'p_new' => array(
				'INNER JOIN',
				array(
					'p_new.page_id = de.de_page_id',
					'p_new.page_namespace' => '0',
					'p_new.page_is_redirect' => '0'
				)
			),
			'r' => array(
				'INNER JOIN',
				array(
					'r.rd_title = p_new.page_title',
					'r.rd_namespace' => '0'
				)
			),
			'p_old' => array(
				'INNER JOIN',
				array(
					'p_old.page_id = r.rd_from',
					'p_old.page_namespace' => '0',
					'p_old.page_is_redirect' => '1'
				)
			),
			'tl_new' => array(
				'LEFT JOIN', // Note: new translation link optional
				array(
					'tl_new.tl_from_lang' => $srcLang,
					'tl_new.tl_from_aid = de.de_page_id',
					'tl_new.tl_to_lang' => $lang
				)
			),
			'tl_old' => array(
				'INNER JOIN',
				array(
					'tl_old.tl_from_lang' => $srcLang,
					'tl_old.tl_from_aid = p_old.page_id',
					'tl_old.tl_to_lang' => $lang
				)
			),
			'babel_old' => array(
				'LEFT JOIN',
				array(
					'babel_old.ct_lang_code' => $srcLang,
					'babel_old.ct_page_id = p_old.page_id'
				)
			),
			'babel_new' => array(
				'LEFT JOIN',
				array(
					'babel_new.ct_lang_code' => $srcLang,
					'babel_new.ct_page_id = p_new.page_id'
				)
			),
			'retrans_old' => array(
				'LEFT JOIN',
				array(
					'retrans_old.ct_lang_code' => $lang,
					'retrans_old.ct_page_id = p_old.page_id'
				)
			),
			'retrans_new' => array(
				'LEFT JOIN',
				array(
					'retrans_new.ct_lang_code' => $lang,
					'retrans_new.ct_page_id = p_new.page_id'
				)
			)
		);

		return $dbr->selectSQLText(
			$tables,
			$fields,
			$conds,
			$method,
			$opts,
			$joinConds
		);
	}

	/**
	 * Generate a query to fetch moved articles with translation links
	 */
	public function getMovedQuery($srcLang, $lang, $lowDate) {
		return $this->getMovedDeletedQuery($srcLang, $lang, $lowDate, DailyEdits::MOVE_TYPE);
	}

	/**
	 * Generate a query to fetch deleted articles with translation links
	 */
	public function getDeletedQuery($srcLang, $lang, $lowDate) {
		return $this->getMovedDeletedQuery($srcLang, $lang, $lowDate, DailyEdits::DELETE_TYPE);
	}

	/**
	 * Generate a query to fetch either moved or deleted articles with translation
	 * links.
	 *
	 * The generation of queries for moved and deleted articles has been
	 * consolidated into one method due to their similarity. Public users
	 * of this class should use the individual helper methods
	 * getMovedQuery() and getDeletedQuery().
	 *
	 * The query will attempt to get various pieces of data for:
	 * - the moved/deleted article in the source language
	 * - the associated article in the target language
	 *
	 * The source language is typically English.
	 */
	private function getMovedDeletedQuery($srcLang, $lang, $lowDate, $editType) {
		$dbr = wfGetDB(DB_REPLICA);

		$srcLangDB = Misc::getLangDB($srcLang);
		$destLangDB = Misc::getLangDB($lang);
		$primaryDB = self::getPrimaryDB();

		$srcLangDBSafe = $dbr->addIdentifierQuotes($srcLangDB);
		$destLangDBSafe = $dbr->addIdentifierQuotes($destLangDB);
		$primaryDBSafe = $dbr->addIdentifierQuotes($primaryDB);
		
		$tables = array(
			'de' => "$srcLangDBSafe.daily_edits",
			'tl' => "$primaryDBSafe.translation_link",
			'p' => "$srcLangDBSafe.page",
			'babel' => "$srcLangDBSafe.babelfish_articles",
			'retrans' => "$srcLangDBSafe.retranslatefish_articles"
		);

		$fields = $this->getMovedDeletedQueryFields($dbr, $srcLang, $lang, $editType);

		$conds = $this->getQueryConds($dbr, $lowDate, $editType);

		$method = __METHOD__;

		$opts = array(
			'ORDER BY' => array('p.page_id ASC')
		);

		$joinConds = array(
			'tl' => array(
				'INNER JOIN',
				array(
					'tl.tl_from_lang' => $srcLang,
					'tl.tl_from_aid = de.de_page_id',
					'tl.tl_to_lang' => $lang
				)
			),
			'p' => array(
				'LEFT JOIN',
				array(
					'p.page_id = de.de_page_id',
					'p.page_namespace' => '0'
				)
			),
			'babel' => array(
				'LEFT JOIN',
				array(
					'babel.ct_lang_code' => $lang,
					'babel.ct_page_id = de.de_page_id'
				)
			),
			'retrans' => array(
				'LEFT JOIN',
				array(
					'retrans.ct_lang_code' => $lang,
					'retrans.ct_page_id = de.de_page_id'
				)
			)
		);

		return $dbr->selectSQLText(
			$tables,
			$fields,
			$conds,
			$method,
			$opts,
			$joinConds
		);
	}

	/**
	 * Get the conditions for the WHERE-clause of the query.
	 */
	protected function getQueryConds(&$dbr, $lowDate, $editType) {
		return array(
			'de.de_timestamp >= ' . $dbr->addQuotes($lowDate),
			'de.de_edit_type' => $editType
		);
	}

	/**
	 * Get the fields for the SELECT query for redirected articles.
	 */
	public function getRedirectedQueryFields(&$dbr, $srcLang, $lang) {
		return array(
			'action' => $dbr->addQuotes(self::getEditTypeName(DailyEdits::EDIT_TYPE)),
			'action_timestamp' => 'STR_TO_DATE(de.de_timestamp, "%Y%m%d%H%i%s")',
			'src_lang' => $dbr->addQuotes($srcLang),
			'src_lang_old_aid' => 'p_old.page_id',
			'src_lang_old_title' => 'p_old.page_title',
			'src_lang_old_in_babelfish' => 'babel_old.ct_page_id IS NOT NULL',
			'src_lang_old_in_retranslatefish' => 'retrans_old.ct_page_id IS NOT NULL',
			'src_lang_new_aid' => 'p_new.page_id',
			'src_lang_new_title' => 'p_new.page_title',
			'src_lang_new_in_babelfish' => 'babel_new.ct_page_id IS NOT NULL',
			'src_lang_new_in_retranslatefish' => 'retrans_new.ct_page_id IS NOT NULL',
			'dest_lang' => $dbr->addQuotes($lang),
			'dest_lang_old_aid' => 'tl_old.tl_to_aid',
			'dest_lang_new_aid' => 'tl_new.tl_to_aid'
		);
	}

	/**
	 * Get the fields for the SELECT query for moved/deleted articles.
	 */
	public function getMovedDeletedQueryFields(&$dbr, $srcLang, $lang, $editType) {
		$editTypeName = self::getEditTypeName($editType);

		return array(
			'action' => $dbr->addQuotes($editTypeName),
			'action_timestamp' => 'STR_TO_DATE(de.de_timestamp, "%Y%m%d%H%i%s")',
			'src_lang' => $dbr->addQuotes($srcLang),
			'src_lang_aid' => 'de.de_page_id',
			'src_lang_title' => 'p.page_title',
			'src_lang_in_babelfish' => 'babel.ct_page_id IS NOT NULL',
			'src_lang_in_retranslatefish' => 'retrans.ct_page_id IS NOT NULL',
			'dest_lang' => $dbr->addQuotes($lang),
			'dest_lang_aid' => 'tl.tl_to_aid'
		);
	}

	/**
	 * Get the aliases of the fields in the SELECT query for redirected articles.
	 *
	 * These aliases will be the keys in the objects returned by DatabaseBase on
	 * successful query execution.
	 */
	public function getRedirectedQueryFieldAliases(&$dbr, $srcLang, $lang) {
		return array_keys($this->getRedirectedQueryFields($dbr, $srcLang, $lang));
	}

	/**
	 * Get the aliases of the fields in the SELECT query for moved and deleted
	 * articles.
	 *
	 * These aliases will be the keys in the objects returned by DatabaseBase on
	 * successful query execution.
	 */
	public function getMovedDeletedQueryFieldAliases(&$dbr, $srcLang, $lang, $editType) {
		return array_keys($this->getMovedDeletedQueryFields($dbr, $srcLang, $lang, $editType));
	}

	/**
	 * Get the aliases describing page titles in the SELECT query for redirected
	 * articles.
	 *
	 * These titles will be converted to URLs when possible.
	 */
	public function getRedirectedTitleAliases() {
		return array(
			'src_lang' => array(
				'src_lang_old_title',
				'src_lang_new_title'
			),
			'dest_lang' => array(
				'dest_lang_old_title',
				'dest_lang_new_title'
			)
		);
	}

	/**
	 * Get the aliases describing page titles in the SELECT query for moved and
	 * deleted articles.
	 *
	 * These titles will be converted to URLs when possible.
	 */
	public function getMovedDeletedTitleAliases() {
		return array(
			'src_lang' => array(
				'src_lang_title'
			),
			'dest_lang' => array(
				'dest_lang_title'
			)
		);
	}

	/**
	 * Get info about fields that should be fetched from Titus for redirected
	 * articles.
	 */
	public function getRedirectedTitusAliasInfo() {
		return array(
			array(
				'lang_key' => 'src_lang',
				'page_id' => 'src_lang_old_aid',
				'fields' => array(
					'ti_30day_views' => 'src_lang_old_30day_views'
				),
				'after' => 'src_lang_old_title'
			),
			array(
				'lang_key' => 'src_lang',
				'page_id' => 'src_lang_new_aid',
				'fields' => array(
					'ti_30day_views' => 'src_lang_new_30day_views'
				),
				'after' => 'src_lang_new_title'
			),
			array(
				'lang_key' => 'dest_lang',
				'page_id' => 'dest_lang_old_aid',
				'fields' => array(
					'ti_page_title' => 'dest_lang_old_title',
					'ti_30day_views' => 'dest_lang_old_30day_views'
				),
				'after' => 'dest_lang_old_aid'
			),
			array(
				'lang_key' => 'dest_lang',
				'page_id' => 'dest_lang_new_aid',
				'fields' => array(
					'ti_page_title' => 'dest_lang_new_title',
					'ti_30day_views' => 'dest_lang_new_30day_views'
				),
				'after' => 'dest_lang_new_title'
			)
		);
	}

	/**
	 * Get info about fields that should be fetched from Titus for moved and
	 * deleted articles.
	 */
	public function getMovedDeletedTitusAliasInfo() {
		return array(
			array(
				'lang_key' => 'src_lang',
				'page_id' => 'src_lang_aid',
				'fields' => array(
					'ti_30day_views' => 'src_lang_30day_views'
				),
				'after' => 'src_lang_title'
			),
			array(
				'lang_key' => 'dest_lang',
				'page_id' => 'dest_lang_aid',
				'fields' => array(
					'ti_page_title' => 'dest_lang_title',
					'ti_30day_views' => 'dest_lang_30day_views'
				),
				'after' => 'dest_lang_aid'
			),
		);
	}

	public static function getPrimaryDB() {
		return 'wikidb_112';
	}

	public static function getEditTypeName($editType) {
		if ($editType === DailyEdits::EDIT_TYPE) {
			return 'redirect';
		} elseif ($editType === DailyEdits::MOVE_TYPE) {
			return 'move';
		} elseif ($editType === DailyEdits::DELETE_TYPE) {
			return 'delete';
		} else {
			return false;
		}
	}

	/**
	 * Merge an associative array into the haystack after the given needle (key).
	 */
	public static function arrayMergeAfter(&$haystack, $needle, $new) {
		$i = 0;
		foreach ($haystack as $key=>$value) {
			++$i;
			if ($key == $needle) {
				break;
			}
		}

		$haystack = array_merge(
			array_slice(
				$haystack, 0, $i, true
			),
			$new,
			array_slice(
				$haystack, $i, null, true
			)
		);
	}
}

$maintClass = 'SendLangLinksChangedReport';
require_once RUN_MAINTENANCE_IF_MAIN;

