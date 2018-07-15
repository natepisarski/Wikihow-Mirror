<?php

require_once __DIR__ . '/Maintenance.php';

class TitusMaintenance extends Maintenance {
	var $titus = null;

	// we can override the active stats with this var
	var $activeStats = array();

	// we can override the pageIds to act on with this var
	var $pageIds = null;

	public function execute() {
		global $wgLanguageCode, $IP;
		print "TitusCheckpoint starting to run " . basename(__FILE__) .
			" with language code '$wgLanguageCode': " . wfTimestampNow() . "\n";
		require_once "$IP/extensions/wikihow/titus/Titus.class.php";
		$this->titus = new TitusDB(true);
		$this->activeStats = TitusConfig::getAllStats();
		$this->nightly();
		print "TitusCheckpoint finished running " . basename(__FILE__) .
			" with language code '$wgLanguageCode': " . wfTimestampNow() . "\n";
	}

	private function getWikiDBName() {
		global $wgLanguageCode;
		return Misc::getLangDB($wgLanguageCode);
	}

	/*
	 * Run the nightly maintenance for the titus and titus_historical tables
	 */
	public function nightly() {
		global $IP;

		$ourErrors = "";
		try {
			print shell_exec("$IP/../scripts/update_titus_historical_schema.sh execute");
			$this->updateHistorical();
			$this->trimHistorical();
			$this->incrementTitusDatestamp();
			$this->removeDeletedPages();
			$this->removeRedirects();

			$this->updateTitus();
			$this->fixBlankRecords();
		} catch ( Exception $e ) {
			$ourErrors .= "\n" . $e->getFile() . ':' . $e->getLine() . " " . $e->getMessage();
		}
		print "Reporting errors\n";
		$this->reportErrors($ourErrors);
	}
	/*
	 * Function for sending error email with doing a full re-run on Titus
	 */
	public function sendErrorEmail() {
		$titus = $this->titus;

		$statGroups = $this->titus->getPagesToCalcByStat($this->activeStats, wfTimestampNow());
		$this->reportErrors();

	}

	/**
	 * Calculate miscellaneous stats that aren't edit or all id stats
	 * $stats - array of stat names to calculate
	 * $customIdsStats - an array of arrays of $ids, one for each custom stat
	 * $pageIds - optional override of ids to calculate the stat on
	 */
	private function calcMiscStats( $stats, $customIdStats, $pageIds = null ) {
		$basicStats = TitusConfig::getBasicStats();

		foreach( $customIdStats as $statName => $ids ) {
			print "calculating stat $statName\n";
			$statsToCalc = array_merge( $basicStats, array( $statName => 1 ) );
			$chunkNumber = 0;
			$chunk = array();

			// if we have a list of $pageIds to calculate the stat on, use it instead
			if ( $pageIds && is_array( $pageIds ) ) {
				$ids = $pageIds;
			}

			foreach( $ids as $id ) {
				$chunk[] = $id;
				if (sizeof($chunk) >= 1000) {
					print "calculating chunk #: " . $chunkNumber . " :" . wfTimestampNow() . "\n";
					$chunkNumber++;
					$this->titus->calcStatsForPageIds($statsToCalc, $chunk);
					$chunk = array();
				}
			}
			if (sizeof($chunk) > 0) {
				print "calculating chunk #: " . $chunkNumber . " :" . wfTimestampNow() . "\n";
				$this->titus->calcStatsForPageIds($statsToCalc, $chunk);
			}
		}
	}

	public function updateTitus() {
		$titus = $this->titus;

		print "TitusCheckpoint getPagesToCalcByStat start: " . wfTimestampNow() . "\n";
		$statGroups = $this->titus->getPagesToCalcByStat($this->activeStats, wfTimestampNow());
		print "TitusCheckpoint getPagesToCalcByStat finish: " . wfTimestampNow() . "\n";

		print "TitusCheckpoint calcLatestEdits start: " . wfTimestampNow() . "\n";
		$titus->calcLatestEdits( $statGroups['daily_edit_stats'], $this->pageIds );
		print "TitusCheckpoint calcLatestEdits finish: " . wfTimestampNow() . "\n";

		print "TitusCheckpoint calcMiscStats start: " . wfTimestampNow() . "\n";
		$this->calcMiscStats( $statGroups['id_stats'], $statGroups['custom_id_stats'], $this->pageIds );
		print "TitusCheckpoint calcMiscStats finish: " . wfTimestampNow() . "\n";

		// Run nightly stats
		print "TitusCheckpoint calcStatsForAllPages start: " . wfTimestampNow() . "\n";
		$titus->calcStatsForAllPages($statGroups['all_id_stats'], $this->pageIds );
		print "TitusCheckpoint calcStatsForAllPages finish: " . wfTimestampNow() . "\n";

		// Update last retranslation timestamps for articles
		// completed in Retranslatefish
		print 'TitusCheckpoint calcRetranslations start: ' . wfTimestampNow() . "\n";
		$this->calcRetranslations();
		print 'TitusCheckpoint calcRetranslations finish: ' . wfTimestampNow() . "\n";
	}

	private function calcRetranslations() {
		global $wgLanguageCode;

		if ($wgLanguageCode == 'en') {
			print "Retranslation updates not supported for English. Skipping.\n";
		} else {
			$titus = $this->titus;
			$statsToCalc['PageId'] = 1;
			$statsToCalc['LanguageCode'] = 1;
			$statsToCalc['RetranslationComplete'] = 1;
			$statGroups = $titus->getPagesToCalcByStat($statsToCalc, wfTimestampNow());

			try {
				// Print debugging info
				print "lang: $wgLanguageCode\n";
				print "statGroups keys: ";
				print_r(array_keys($statGroups));

				print "statGroups['custom_id_stats'] keys: ";
				print_r(array_keys($statGroups['custom_id_stats']));

				print "RetranslationComplete ids: ";
				print_r($statGroups['custom_id_stats']['RetranslationComplete']);
				// End debugging

				$titus->calcStatsForPageids($statsToCalc, $statGroups['custom_id_stats']['RetranslationComplete']);
				print 'Updated ' . count($statGroups['custom_id_stats']['RetranslationComplete']) . " pages\n";
			} catch (Exception $e) {
				// Most likely because there are no articles to update:
				print 'Caught exception: "' . $e->getMessage() . "\"\n";
			}
		}
	}

	/*
	 * Dumps the current state of the titus table into titus_historical.  At the time of the dump, this should be a full days
	 * worth of titus page rows. The titus_historical table should maintain 30-60 days worth of titus table dumps
	 */
	private function updateHistorical() {
		global $wgLanguageCode;
		$sql = "INSERT IGNORE INTO titus_historical_intl SELECT * FROM titus_intl WHERE ti_language_code='" . $wgLanguageCode . "'";
		$this->performMaintenanceQuery($sql, __METHOD__);
	}

	private function trimHistorical($lookBack = 30) {
		global $wgLanguageCode;
		$lowDate = substr(wfTimestamp(TS_MW, strtotime("-$lookBack day", strtotime(date('Ymd', time())))), 0, 8);
		$sql = "DELETE FROM titus_historical_intl WHERE ti_datestamp < '$lowDate' AND ti_language_code='" . $wgLanguageCode . "'";
		$this->performMaintenanceQuery($sql, __METHOD__);
	}

	private function performMaintenanceQuery($sql, $method) {
		$titus = $this->titus;
		return $titus->performTitusQuery($sql, 'write', $method);
	}

	private function incrementTitusDatestamp() {
		global $wgLanguageCode;
		$today = wfTimestamp(TS_MW, strtotime(date('Ymd', time())));
		$sql = "UPDATE titus_intl set ti_datestamp = '$today' WHERE ti_language_code='". $wgLanguageCode . "'";
		$this->performMaintenanceQuery($sql, __METHOD__);
	}

	private function removeDeletedPages() {
		global $wgLanguageCode;
		$dbr = wfGetDB(DB_SLAVE);
		$lowDate = wfTimestamp(TS_MW, strtotime("-10 day", strtotime(date('Ymd', time()))));

		$sql = "SELECT de_page_id FROM daily_edits WHERE de_timestamp >= '$lowDate' AND de_edit_type = " . DailyEdits::DELETE_TYPE;
		$res = $dbr->query($sql);
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->de_page_id;
		}
		if (!empty($ids)) {
			$ids = "(" . implode(", ", $ids) . ")";
			$sql = "DELETE FROM titus_intl where ti_page_id IN $ids AND ti_language_code='" . $wgLanguageCode . "'";
			$this->performMaintenanceQuery($sql, __METHOD__);
		}
	}

	private function removeRedirects() {
		global $wgLanguageCode;
		$dbr = wfGetDB(DB_SLAVE);
		$lowDate = wfTimestamp(TS_MW, strtotime("-1 day", strtotime(date('Ymd', time()))));

		$sql = "SELECT de_page_id FROM daily_edits WHERE de_timestamp >= '$lowDate' AND de_edit_type = " . DailyEdits::EDIT_TYPE;
		$res = $dbr->query($sql);
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->de_page_id;
		}
		if (!empty($ids)) {
			$ids = "(" . implode(", ", $ids) . ")";

			$sql = "DELETE  t.* from titus_intl t LEFT JOIN " . $this->getWikiDBName() . ".page p ON t.ti_page_id = p.page_id
				WHERE p.page_is_redirect = 1 AND ti_page_id IN $ids AND ti_language_code='" . $wgLanguageCode . "'";
			$this->performMaintenanceQuery($sql, __METHOD__);
		}

	}

	private function fixBlankRecords() {
		global $wgLanguageCode;
		$sql = "SELECT ti_page_id FROM titus_intl where ti_page_title = '' AND ti_language_code='" . $wgLanguageCode . "'";
		$ids = array();
		$res = $this->performMaintenanceQuery($sql, __METHOD__);
		foreach ($res as $row) {
			$ids[] = $row->ti_page_id;
		}
		if (!empty($ids)) {
			$pageChunks = array_chunk($ids, 1000);
			foreach ($pageChunks as $chunk) {
				$titus = $this->titus;
				$dailyEditStats = TitusConfig::getDailyEditStats();
				$titus->calcStatsForPageIds($dailyEditStats, $chunk);
			}

		}
	}

	public function reportErrors( $ourErrors = "", $sendEmail = true ) {
		global $wgLanguageCode;

		$errors = "";
		try {
			$errors = $this->titus->getErrors($this->activeStats);
		} catch( Exception $e ) {
			$ourErrors .= "\n" . $e->getMessage();
		}
		if ( $errors || $ourErrors ) {
				if ( $ourErrors ) {
					$errors .= "\n" . $ourErrors;
				}
				print "Errors are: " . $errors . "\n";
				$to = new MailAddress("titus-alerts@wikihow.com,international@wikihow.com");
				$from = new MailAddress("alerts@titus.wikiknowhow.com");
				$subject = "Titus Errors - " . $wgLanguageCode;
				$endPart = "\n\nGenerated by " . __FILE__ . " on " . gethostname() . "\n";
				if ( $sendEmail ) {
					UserMailer::send( $to, $from, $subject, $errors . $endPart );
				}
		}
	}
}

$maintClass = "TitusMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;
