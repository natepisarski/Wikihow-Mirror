<?php

class FileAttachmentResponse {
	var $filename, $mimeType, $output, $delimeter;

	const MIME_TSV = 'text/tsv';
	const DELIMETER_TAB = "\t";

	public function __construct($filename, $mimeType = self::MIME_TSV, $delimeter = self::DELIMETER_TAB) {
		$this->filename = $filename;
		$this->mimeType = $mimeType;
		$this->delimeter = "\t";
	}

	public function start() {
		$this->outputHeader();
		$this->output = fopen("php://output", "w");
	}

	protected function outputHeader() {
		header("Content-Type: $this->mimeType");
		header('Content-Disposition: attachment; filename="' . addslashes($this->filename) . '"');
	}

	public function outputData($data) {
		fwrite($this->output, $data);
	}

	public function outputCSVRow(array $row) {
		fputcsv($this->output, $row, $this->delimeter);
	}

	public function end() {
		fclose($this->output);
		exit;
	}
}
