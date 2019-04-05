<?php

/**
 * Created by PhpStorm.
 * User: jordan
 * Date: 3/10/17
 * Time: 3:27 PM
 */
class ReverificationData {

	var $id, $aid, $oldDate, $oldRevId, $sheetName, $verifierId, $verifierName, $newDate, $reverified, $newRevId, $feedback,
		$extensiveFeedback, $extensiveDoc, $exportTimestamp, $scriptExportTimestamp, $flag, $skipTimestamp, $feedbackEditor;

	/**
	 * @return mixed
	 */
	public function getExtensiveDoc() {
		return $this->extensiveDoc;
	}

	/**
	 * @param mixed $extensiveDoc
	 */
	public function setExtensiveDoc($extensiveDoc) {
		$this->extensiveDoc = $extensiveDoc;
	}

	/*
	 * Reset the Reverification by clearing certain data elements
	 */
	public static function resetReverification(ReverificationData $r, $clearFeedback = false) {
		$r->setFlag(0);
		$r->setFeedbackEditor('');

		if ($clearFeedback) {
			$r->setFeedback('');
			$r->setExtensiveFeedback(0);
		}
		return $r;
	}

	/**
	 * @return mixed
	 */
	public function getFeedbackEditor() {
		return $this->feedbackEditor;
	}

	/**
	 * @param mixed $feedbackEditor
	 */
	public function setFeedbackEditor($feedbackEditor) {
		$this->feedbackEditor = $feedbackEditor;
	}

	/**
	 * @return mixed
	 */
	public function getSkipTimestamp() {
		return $this->skipTimestamp;
	}

	/**
	 * @param mixed $skipTimestamp
	 */
	public function setSkipTimestamp($skipTimestamp) {
		$this->skipTimestamp = $skipTimestamp;
	}

	public function setSkipTimestampNow() {
		$this->skipTimestamp = wfTimestampNow();
	}

	/**
	 * @return mixed
	 */
	public function getFlag() {
		return $this->flag;
	}

	/**
	 * @param mixed $flag
	 */
	public function setFlag($flag) {
		$this->flag = $flag;
	}

	/**
	 * @return mixed
	 */
	public function getScriptExportTimestamp() {
		return $this->scriptExportTimestamp;
	}

	/**
	 * @param mixed $scriptExportTimestamp
	 */
	public function setScriptExportTimestamp($scriptExportTimestamp) {
		$this->scriptExportTimestamp = $scriptExportTimestamp;
	}

	/**
	 * @return mixed
	 */
	public function getSheetName() {
		return $this->sheetName;
	}

	/**
	 * @param mixed $sheetName
	 */
	public function setSheetName($sheetName) {
		$this->sheetName = $sheetName;
	}

	const FORMAT_DB = 'Ymd';
	const FORMAT_SPREADSHEET = 'n/j/Y';

	public static function newFromDBRow($row) {
		$rd = new ReverificationData();
		$rd->loadFromDBRow($row);
		return $rd;
	}

	public static function newFromVerifyData($data) {
		$rd = new ReverificationData();
		$rd->loadFromVerifyData($data);

		return $rd;
	}

	public function loadFromDBRow($row) {
		$this->setId($row->rv_id);
		$this->setAid($row->rv_aid);
		$this->setOldDate($row->rv_old_date);
		$this->setOldRevId($row->rv_old_revision);
		$this->setSheetName($row->rv_sheet);
		$this->setVerifierId($row->rv_verifier_id);
		$this->setVerifierName($row->rv_verifier_name);
		$this->setNewDate($row->rv_new_date);
		$this->setReverified($row->rv_reverified);
		$this->setNewRevId($row->rv_new_revision);
		$this->setFeedback($row->rv_feedback);
		$this->setFlag($row->rv_flag);
		$this->setFeedbackEditor($row->rv_feedback_editor);
		$this->setExtensiveDoc($row->rv_extensive_doc);
		$this->setExtensiveFeedback($row->rv_extensive_feedback);
		$this->setExportTimestamp($row->rv_export_ts);
		$this->setScriptExportTimestamp($row->rv_script_export_ts);
		$this->setSkipTimestamp($row->rv_skip_ts);
	}

	public function getDBRow() {
		return [
			'rv_id' => $this->getId(),
			'rv_aid' => $this->getAid(),
			'rv_old_date' => $this->getOldDate(),
			'rv_old_revision' => $this->getOldRevId(),
			'rv_sheet' => $this->getSheetName(),
			'rv_verifier_id' => $this->getVerifierId(),
			'rv_verifier_name' => $this->getVerifierName(),
			'rv_new_date' => $this->getNewDate(),
			'rv_reverified' => $this->getReverified(),
			'rv_new_revision' => $this->getNewRevId(),
			'rv_feedback' => $this->getFeedback(),
			'rv_flag' => $this->getFlag(),
			'rv_feedback_editor' => $this->getFeedbackEditor(),
			'rv_extensive_feedback' => $this->getExtensiveFeedback(),
			'rv_extensive_doc' => $this->getExtensiveDoc(),
			'rv_export_ts' => $this->getExportTimestamp(),
			'rv_script_export_ts' => $this->getScriptExportTimestamp(),
			'rv_skip_ts' => $this->getSkipTimestamp(),
		];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @param VerifyData $data
	 */
	private function loadFromVerifyData($data) {
		$this->setAid($data->aid);
		$this->setOldDate($this->formatDate($data->date));
		$this->setOldRevId($data->revisionId);
		$this->setSheetName($data->worksheetName);
		$this->setVerifierId($data->verifierId);
		$this->setVerifierName($data->name);
	}

	/**
	 * @param $date
	 * @param int $formatType
	 * @return false|string
	 */
	public static function formatDate($date, $format = self::FORMAT_DB) {
		return date($format, strtotime($date));
	}

	/**
	 * @return mixed
	 */
	public function getAid() {
		return $this->aid;
	}

	/**
	 * @param mixed $aid
	 */
	public function setAid($aid) {
		$this->aid = $aid;
	}

	/**
	 * @return mixed
	 */
	public function getOldDate() {
		return $this->oldDate;
	}

	/**
	 * @param mixed $oldDate
	 */
	public function setOldDate($oldDate) {
		$this->oldDate = $oldDate;
	}

	/**
	 * @return mixed
	 */
	public function getOldRevId() {
		return $this->oldRevId;
	}

	/**
	 * @param mixed $oldRevId
	 */
	public function setOldRevId($oldRevId) {
		$this->oldRevId = $oldRevId;
	}

	/**
	 * @return mixed
	 */
	public function getVerifierId() {
		return $this->verifierId;
	}

	/**
	 * @param mixed $verifierId
	 */
	public function setVerifierId($verifierId) {
		$this->verifierId = $verifierId;
	}

	/**
	 * @return mixed
	 */
	public function getVerifierName() {
		return $this->verifierName;
	}

	/**
	 * @param mixed $verifierName
	 */
	public function setVerifierName($verifierName) {
		$this->verifierName = $verifierName;
	}

	/**
	 * @return mixed
	 */
	public function getNewDate($format = self::FORMAT_DB) {
		return empty($this->newDate) ? '' : $this->formatDate($this->newDate, $format);
	}

	/**
	 * @param mixed $newDate
	 */
	public function setNewDate($newDate) {
		$this->newDate = $newDate;
	}

	public function setNewDateNow() {
		$this->setNewDate(date(self::FORMAT_DB));
	}

	/**
	 * @return mixed
	 */
	public function getReverified() {
		return $this->reverified;
	}

	/**
	 * @param mixed $reverified
	 */
	public function setReverified($reverified) {
		$this->reverified = $reverified;
	}

	/**
	 * @return mixed
	 */
	public function getNewRevId() {
		return $this->newRevId;
	}

	/**
	 * @param mixed $newRevId
	 */
	public function setNewRevId($newRevId) {
		$this->newRevId = $newRevId;
	}

	/**
	 * @return mixed
	 */
	public function getFeedback() {
		return $this->feedback;
	}

	/**
	 * @param mixed $feedback
	 */
	public function setFeedback($feedback) {
		$this->feedback = $feedback;
	}

	/**
	 * @return mixed
	 */
	public function getExtensiveFeedback() {
		return $this->extensiveFeedback;
	}

	/**
	 * @param mixed $extensiveFeedback
	 */
	public function setExtensiveFeedback($extensiveFeedback) {
		$this->extensiveFeedback = $extensiveFeedback;
	}

	/**
	 * @return mixed
	 */
	public function getExportTimestamp() {
		return $this->exportTimestamp;
	}

	/**
	 * @param mixed $exportTimestamp
	 */
	public function setExportTimestamp($exportTimestamp) {
		$this->exportTimestamp = $exportTimestamp;
	}

}
