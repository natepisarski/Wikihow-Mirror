<?php

/**
 * Updates the Master Expert Verified spreadsheet with the latest reverifications.  Spreadsheet can be found here:
 * https://docs.google.com/spreadsheets/d/19KNiXjlz9s9U0zjPZ5yKQbcHXEidYPmjfIWT7KiIf-I/edit#gid=1516230615
 */
class ReverificationSpreadsheetUpdater {

	/**
	 * @var ReverificationData[]
	 */
	var $exportData = [];
	var $emailLog = [];
	var $totalUpdateCount = 0;
	var $totalUpdated = 0;
	var $totalSkipped = 0;
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

		if($this->totalUpdateCount > 0) {
			foreach ($this->sheetNames as $sheetName) {
				$sheet = $file->sheet($sheetName);
				$this->log("Processing Sheet: $sheetName");
				$this->processSheet($sheet);
			}
		}

		$this->processRemaining();
		$this->sendReport();
	}

	protected function processSheet(Google_Spreadsheet_Sheet $sheet) {
		$updateCount = 0;
		$skipCount = 0;
		$verifierCount = [];
		foreach ($sheet->items as $row => $data) {
			$rowAid = $data["ArticleID"];
			$shouldUpdateRow = true;
			if (isset($this->exportData[$rowAid]) && $rever = $this->exportData[$rowAid]) {
				$this->log("Processing Reverification:  Article ID - {$rever->getAid()}, " .
					"Reverified Date - {$rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET)}");

				$reverificationDate = $rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET);
				$spreadsheetVerifiedDate = $data["Verified Date"];

				$verifierName = $data["Verifier Name"];
				if(!isset($verifierCount[$verifierName])) {
					$verifierCount[$verifierName] = 0;
				}
				if($verifierCount[$verifierName] >= Self::MAX_PER_DAY) {
					$skipCount++;
					$this->totalSkipped++;
					$shouldUpdateRow = false; //we don't want to update the row in the db, b/c we're saving it for another day
					$this->log("-Skipping. Already processed " . $verifierCount[$verifierName] . " by " . $verifierName);
				} elseif (strtotime($reverificationDate) <= strtotime($spreadsheetVerifiedDate)) {
					$skipCount++;
					$this->totalSkipped++;
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

						if ($rever->getVerifierName() != $data["Verifier Name"]) {
							$this->log("-Updating spreadsheet 'Verifier Name' fields. Replacing " .
							"{$data["Verifier Name"]} with {$rever->getVerifierName()}");
							$sheet->update($row, 'Verifier Name', $rever->getVerifierName());
						}
						$verifierCount[$verifierName]++;
					} else {
						$this->log("-Skipping. Title doesn't exist for Article ID $rowAid");
						$this->emailLog("Article ID: $rowAid, Reverification Date: $reverificationDate - Title " .
							"doesn't exist for article id");
					}
				}

				if($shouldUpdateRow) {
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
		$reportBody = "Total processed reverifications: {$this->totalUpdateCount}\n" .
			"Total updated: {$this->totalUpdated}\n" .
			"Total skipped: {$this->totalSkipped}\n";

		if (!empty($this->emailLog)) {
			$reportBody .= "Notices:\n" . implode("\n", $this->emailLog) . "\n\n";
		}

		$reportBody .=	"Script Start: {$this->convertoLocalTime($startTime)}\n" .
			"Script End: {$this->convertoLocalTime($endTime)}\n" .
			"Duration: $duration";

		UserMailer::send(
			new MailAddress('jordan@wikihow.com, elizabeth@wikihow.com, daniel@wikihow.com, bebeth@wikihow.com'),
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

		if ($wgIsProduction) {
			$fileId = '19KNiXjlz9s9U0zjPZ5yKQbcHXEidYPmjfIWT7KiIf-I'; // prod file
		} else {
			$fileId = '1lSJt2B922mIH7A-rh8LXhJ7qnIDxPqp-wM6Ly-OEPzA'; // dev file
		}
		$file = $client->file($fileId);

		return $file;
	}
}
