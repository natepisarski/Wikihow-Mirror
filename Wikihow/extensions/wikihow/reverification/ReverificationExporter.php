<?php

/**
 * Created by PhpStorm.
 * User: jordan
 * Date: 3/10/17
 * Time: 3:56 PM
 */
class ReverificationExporter {
	const ACTION_EXPORT_RANGE = 'export_new';
	const ACTION_EXPORT_ALL = 'export_range';

	const HEADER_ROW = [
		'Article ID',
		'Article Title',
		'Article Url',
		'Date action taken',
		'Coauthor ID',
		'Verifier Name',
		'Reverified',
		'Date Reverified',
		'Re-verified article version URL',
		'Quick Feedback text',
		'Request Extensive Feedback',
		'Feedback Doc Url',
		'Feedback Editor',
		'Flagged for Outside Review',
		'Script Export Timestamp',
		'Reverification ID',
	];

	var $exportType = null;

	function __construct() {}

	function exportData($exportType, $from = null, $to = null) {
		$response = new FileAttachmentResponse($this->getFilename($exportType));
		$response->start();
		$response->outputCSVRow($this->getHeaderRow());

		$db = ReverificationDB::getInstance();
		$reverifications = $db->getExported($from, $to);

		$ts = wfTimestampNow();
		$reverificationIds = [];
		foreach ($reverifications as $rever) {
			$response->outputCSVRow($this->getReverificationOutputRow($rever, $ts));
			$reverificationIds []= $rever->getId();
		}

		if (empty($reverifications)) {
			$response->outputData(wfMessage('rva_not_found')->text());
		}

		if ($exportType == self::ACTION_EXPORT_RANGE) {
			$db->updateExportTimestamp($reverificationIds, $ts);
		}

		$response->end();
	}

	protected function getFilename($exportType) {
		return 'reverifications_' . $exportType . '_' . wfTimestampNow() . ".xls";
	}

	protected function getHeaderRow() {
		return self::HEADER_ROW;
	}

	protected function getEmptyRow() {
		return array_fill_keys(self::HEADER_ROW, '');
	}

	protected function getReverificationOutputRow(ReverificationData $rever, $ts) {
		$row = $this->getEmptyRow();
		$t = Title::newFromId($rever->getAid());

		$row['Article ID'] = $rever->getAid();
		if ($t && $t->exists()) {
			$row['Article Title'] = $t->getText();
			$row['Article Url'] = Misc::getLangBaseURL('en') . $t->getLocalURL();
			$row['Date action taken'] = $rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET);
			$row['Coauthor ID'] = $rever->getVerifierId();
			$row['Verifier Name'] = $rever->getVerifierName();
			$row['Reverified'] = $rever->getReverified() ? 'Y' : 'N';


			// Export the following rows if the article is reverified
			if ($rever->getReverified()) {
				$row['Re-verified article version URL'] = Misc::getLangBaseURL('en') . $t->getLocalURL("oldid=" . $rever->getNewRevId());
			}

			$row['Date Reverified'] = $rever->getReverified() ?
				$rever->getNewDate(ReverificationData::FORMAT_SPREADSHEET) : '';
			// Strip new line characters b/c Daniel's version of Excel mac auto-formats cell.  Daniel
			// says the resulting spreadsheet output "can get messy"
			$row['Quick Feedback text'] = str_replace("\n", "", $rever->getFeedback());
			$row['Request Extensive Feedback'] = $rever->getExtensiveFeedback() ? 'Y' : 'N';
			$row['Feedback Doc Url'] = $rever->getExtensiveDoc() ?  $rever->getExtensiveDoc() : '';
			$row['Feedback Editor'] = $rever->getFeedbackEditor() ?: '';
			$row['Flagged for Outside Review'] = $rever->getFlag() ? 'Y' : 'N';
			$row['Script Export Timestamp'] = $rever->getScriptExportTimestamp();
			$row['Reverification ID'] = $rever->getId();
		} else {
			$row['Article Title'] = 'Error: Article not found for given Article ID';
		}

		return array_values($row);
	}
}
