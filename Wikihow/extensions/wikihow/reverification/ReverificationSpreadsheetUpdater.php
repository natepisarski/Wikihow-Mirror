<?php

/**
 * Updates the Master Expert Verified spreadsheet with the latest reverifications.
 * Spreadsheet ID: CoauthorSheetMaster::getSheetId()
 */
class ReverificationSpreadsheetUpdater {

	/**
	 * @var ReverificationData[]
	 */
	var $exportData = [];
	var $emailLog = [];
	var $totalUpdateCount = 0;
	var $totalUpdated = 0;
	var $verifierCount = [];
	var $totalSkipped = 0;
	var $verifierSkipCount = [];
	var $startTime = null;
	var $sheetNames = ['Expert', 'Academic'];
	//var $sheetNames = ['Academic'];
	var $errors = [];
	var $sheetsFile = null;
	const TIMEOUT_LENGTH = 600;
	const MAX_PER_DAY = 15;

	public function update() {
		$this->startTime = wfTimestampNow();

		$this->getExportData();

		$file = $this->sheetsFile = $this->getSheetsFile();

		$this->totalUpdateCount = count($this->exportData);
		$this->log($this->totalUpdateCount . " reverifications to update");

		if ($this->totalUpdateCount > 0) {
			foreach ($this->sheetNames as $sheetName) {
				$sheet = $file->sheet($sheetName);
				$this->log("Processing Sheet: $sheetName");
				$this->processSheet($sheet);
			}
		}

		$this->processRemaining();
		// not sending anything right now since this project is on hold
		// $this->sendReport();
	}

	protected function processSheet(Google_Spreadsheet_Sheet $sheet) {
		$updateCount = 0;
		$skipCount = 0;
		foreach ($sheet->items as $row => $data) {
			$rowAid = $data["ArticleID"];
			$shouldUpdateRow = true;
			if (isset($this->exportData[$rowAid]) && $rever = $this->exportData[$rowAid]) {
				$this->log("Processing Reverification:  Article ID - {$rever->getAid()}, " .
					"Reverified Date - {$rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET)}");

				$reverificationDate = $rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET);
				$spreadsheetVerifiedDate = $data["Verified Date"];

				$verifierId = (int) $data["Coauthor ID"];
				$verifierName = $data["Verifier Name"];
				if (!isset($this->verifierCount[$verifierId])) {
					$this->verifierCount[$verifierId] = 0;
					$this->verifierSkipCount[$verifierId] = 0;
				}
				if ($this->verifierCount[$verifierId] >= Self::MAX_PER_DAY) {
					$skipCount++;
					$this->verifierSkipCount[$verifierId]++;
					$this->totalSkipped++;
					$shouldUpdateRow = false; //we don't want to update the row in the db, b/c we're saving it for another day
					$this->log("-Skipping. Already processed " . $this->verifierCount[$verifierId] . " by " . $verifierName);
				} elseif (strtotime($reverificationDate) <= strtotime($spreadsheetVerifiedDate)) {
					$skipCount++;
					$this->totalSkipped++;
					$this->verifierSkipCount[$verifierId]++;
					$this->log("-Skipping.  Reverified date less than or equal to current spreadsheet date " .
						"$spreadsheetVerifiedDate.");
					$this->emailLog("Article ID: $rowAid, Reverification Date: $reverificationDate - Skipping " .
						"b/c verified date less than or equal to spreadsheet verified date ($spreadsheetVerifiedDate)");
				} else {
					$updateCount++;
					$this->totalUpdated++;
					$t = Title::newFromId($rowAid);
					if ($t && $t->exists()) {
						$this->log("-Updating spreadsheet 'Revision Link' and 'Verified Date' field");

						$sheet->update($row, "Revision Link",
							Misc::getLangBaseURL('en') . $t->getLocalURL("oldid=" . $rever->getNewRevId()));
						$sheet->update($row, "Verified Date",
							ReverificationData::formatDate(date(ReverificationData::FORMAT_DB), ReverificationData::FORMAT_SPREADSHEET));

						if ($rever->getVerifierId() && ($rever->getVerifierId() != $verifierId) ) {
							$this->log("-Updating spreadsheet 'Coauthor ID' field. Replacing " .
							"{$data["Coauthor ID"]} with {$rever->getVerifierId()}");
							$sheet->update($row, 'Verifier Name', $rever->getVerifierName());
						}
						$this->verifierCount[$verifierId]++;
					} else {
						$this->log("-Skipping. Title doesn't exist for Article ID $rowAid");
						$this->emailLog("Article ID: $rowAid, Reverification Date: $reverificationDate - Title " .
							"doesn't exist for article id");
					}
				}

				if ($shouldUpdateRow) {
					$rever->setScriptExportTimestamp(wfTimestampNow());
					ReverificationDB::getInstance()->update($rever);
				}

				// Clear out the export data once it's been updated
				unset($this->exportData[$rowAid]);
			}
		}
		$this->log($updateCount . " reverifications updated");
		$this->log($skipCount . " reverifications skipped");
	}

	/**
	 * Check for orphaned export items.  Add them as errors to be reported
	 */
	protected function processRemaining() {
		foreach ($this->exportData as $aid => $rever) {
			$this->log("Reverification article id not found in spreadsheet so not updated:" . $aid);
			$this->emailLog("Reverification article id not found in spreadsheet so not updated:" . $aid);
			$rever->setScriptExportTimestamp(wfTimestampNow());
			ReverificationDB::getInstance()->update($rever);
		}
	}

	protected function sendReport() {
		$startTime = $this->startTime;
		$endTime = wfTimestampNow();
		$duration = gmdate("H:i:s", strtotime($endTime) - strtotime($startTime));
		$reportBody = "Total processed reverifications: {$this->totalUpdateCount}\n\n" .
			"Total updated: {$this->totalUpdated}\n\n" .
			"Total skipped: {$this->totalSkipped}\n\n\n\n";

		if ($this->totalUpdated > 0) {
			foreach ($this->verifierCount as $verifier => $count) {
				$reportBody .= "Total updated by {$verifier}: {$count}\n\n";
				if (isset($this->verifierSkipCount[$verifier]) && $this->verifierSkipCount[$verifier] > 0) {
					$reportBody .= "Total skipped by {$verifier}: {$count}\n\n";
				}
			}
		}

		if (!empty($this->emailLog)) {
			$reportBody .= "Notices:\n\n" . implode("\n\n", $this->emailLog) . "\n\n";
		}

		$reportBody .=	"Script Start: {$this->convertoLocalTime($startTime)}\n\n" .
			"Script End: {$this->convertoLocalTime($endTime)}\n\n" .
			"Duration: $duration";

		UserMailer::send(
			new MailAddress('jordan@wikihow.com, elizabeth@wikihow.com, connor@wikihow.com, bebeth@wikihow.com'),
			new MailAddress('ops@wikihow.com'),
			"Reverifications: Master Expert Verified Update Report - " . $this->convertoLocalTime(wfTimestampNow()),
			$reportBody
		);
	}

	protected function convertoLocalTime(String $date) {
		$dateTime = new DateTime ($date);
		$dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
		return $dateTime->format('Y-m-d H:i:s');

	}

	protected function emailLog($str) {
		$this->emailLog []= $str;
	}

	protected function log($str) {
		echo $str . "\n";
	}

	/**
	 * Get the reverifications that need to be updated in the gsheet
	 *
	 * @return ReverificationData[]
	 */
	protected function getExportData() {
		$db = ReverificationDB::getInstance();
		$exportData = $db->getScriptExport();
		foreach ($exportData as $datum) {
			$this->exportData[$datum->getAid()] = $datum;
		}

		return $this->exportData;
	}

	/**
	 * @return Google_Spreadsheet_File
	 */
	protected function getSheetsFile(): Google_Spreadsheet_File {
		global $wgIsProduction;

		$keys = (Object)[
			'client_email' => WH_GOOGLE_SERVICE_APP_EMAIL,
			'private_key' => file_get_contents(WH_GOOGLE_DOCS_P12_PATH)
		];
		$client = Google_Spreadsheet::getClient($keys);

		// Set the curl timeout within the raw google client.  Had to do it this way because the google client
		// is a private member within the Google_Spreadsheet_Client
		$rawClient = function(Google_Spreadsheet_Client $client) {
			return $client->client;
		};
		$rawClient = Closure::bind($rawClient, null, $client);
		$configOptions = [
			CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_LENGTH,
			CURLOPT_TIMEOUT => self::TIMEOUT_LENGTH
		];
		$rawClient($client)->setClassConfig('Google_IO_Curl', 'options', $configOptions);

		$fileId = CoauthorSheetMaster::getSheetId();
		$file = $client->file($fileId);

		return $file;
	}
}
