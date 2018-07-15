<?php

namespace SheetInv;

/**
 * A generic class that represents the result of parsing a Google Sheet.
 *
 * It's returned by the parseSheet() methods of
 * ExpertInvoicing/Spreadsheet.php and WikiVisualInvoicing/Spreadsheet.php
 */
class ParsingResult
{
	public $data;		// array
	public $errors;		// array
	public $warnings;	// array

	public function __construct(array $data, array $errors, array $warnings)
	{
		$this->data = $data;
		$this->errors = $errors;
		$this->warnings = $warnings;
	}

	public function isGood(bool $strict = false): bool {
		return empty($this->errors) && (!$strict || empty($this->warnings));
	}

	public function isBad(bool $strict = false): bool {
		return !$this->isGood($strict);
	}
}
