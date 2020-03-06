<?php

namespace WVI;

use SheetInv\ParsingResult;

/**
 * The project's read-only data source, supported by Google Sheets.
 *
 */
class Spreadsheet
{
	private $sheetId;	// String

	public function __construct ()
	{
		global $wgIsProduction;

		$this->sheetId = $wgIsProduction
			? '1ImKmb5gyFmpa3RaTW3ZYD_YjRYQF739ub3MqykcT-Qg'
			: '1exPmD6RuqWnS_4C_UkYKBk33xlQ_zIP3Uw2Kl_sWtA4';
	}

	public function parseSheet(): ParsingResult
	{
		$summaryResult = $this->parseSummarySheet();
		return $this->parseDetailsSheet($summaryResult);
	}

	private function parseSummarySheet() : ParsingResult
	{
		$rows = \GoogleSheets::getRows($this->sheetId, 'Summary!A2:G');
		$data = [];
		$errors = [];
		$idx = 1;

		foreach ($rows as $row) {

			$idx++;
			$ftp = trim($row[0]);
			$name = trim($row[1]);
			$email = trim($row[2]);
			$articles = trim($row[3]);
			$images = trim($row[4]);
			$loan = trim($row[5]);
			$paid = trim($row[6]);

			if (!$ftp || !$name || !$email || !$paid || $articles == '' || $images == '') {
				$errors[] = "Empty cells in row $idx of the 'Summary' sheet";
			}
			elseif (!is_numeric($articles) || !is_numeric($images)) {
				$errors[] = "Non-numeric values in row $idx of the 'Summary' sheet";
			}
			elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$errors[] = "Invalid email address in row $idx of the 'Summary' sheet";
			}
			elseif (isset($data[$ftp])) {
				$errors[] = "Duplicate FTP '$ftp' in the 'Summary' sheet (row $idx)";
			}
			else {
				$data[$ftp] = [
					'ftp' => $ftp,
					'fullName' => $name,
					'email' => $email,
					'loan' => $loan,
					'paid' => $paid,
					'url_total' => (int) $articles,
					'img_total' => (int) $images,
				];
			}

		}

		return new ParsingResult($data, $errors, []);

	}

	private function parseDetailsSheet(ParsingResult $res): ParsingResult
	{
		if ($res->isBad()) {
			return $res;
		}

		$rows = \GoogleSheets::getRows($this->sheetId, 'Details!A2:C');
		$parent = null;
		$child = null;
		$skipChild = false;
		$idx = 1;

		foreach ($rows as $row) {

			$idx++;
			$firstCol = trim($row[0]); // It can be an FTP, or an article URL
			$urlCount = trim($row[1]);
			$imgCount = trim($row[2]);

			if (!$firstCol || !$urlCount || !$imgCount) {
				$res->errors[] = "Empty cells in row $idx of the 'Details' sheet";
			}
			elseif (!is_numeric($urlCount) || !is_numeric($imgCount)) {
				$res->errors[] = "Non-numeric values in row $idx of the 'Details' sheet";
			}
			elseif (strpos($firstCol, 'http') !== 0) { // New FTP
				$child = $firstCol;
				$parent = strpos($child, 'artlab') === 0 ? 'artlab' : $child;

				if (!isset($res->data[$parent])) {
					$skipChild = true;
					$res->warnings[] = "FTP '$child' exists in 'Details' (row $idx) but not in 'Summary'";
				}
				elseif (isset($res->data[$parent]['contractors'][$child])) {
					$skipChild = true;
					$res->errors[] = "Duplicate FTP '$child' in the 'Details' sheet (row $idx)";
				}
				else {
					$skipChild = false;
					$res->data[$parent]['contractors'][$child] = [
						'ftp' => $child,
						'url_total' => $urlCount,
						'img_total' => $imgCount,
						'urls' => []
					];
				}
			}
			elseif (!$skipChild) { // Article URL
				$res->data[$parent]['contractors'][$child]['urls'][] = [
					'url' => $firstCol,
					'url_count' => $urlCount,
					'img_count' => $imgCount,
				];
			}
		}

		$data = [];
		foreach ($res->data as $item) {
			if (!isset($item['contractors'])) {
				$res->errors[] = "FTP '{$item['ftp']}' exists in 'Summary' (row $idx) but not in 'Details'";
			} else {
				// Use integer array keys so we can iterate over them in mustache templates
				$item['contractors'] = array_values($item['contractors']);
				$data[] = $item;
			}
		}
		$res->data = $data;

		return $res;
	}

}

/* Here is an example of the data returned by parseSheet()

[
    [...]
    5 => [
        'ftp' => 'artlab',
        'fullName' => 'Amy Tests',
        'email' => 'amy_tests@alot.com',
        'loan' => '',
        'paid' => '315534 PHP',
        'url_total' => 155,
        'img_total' => 2141,
        'contractors' => [
            0 => [
                'ftp' => 'artlab_amy',
                'url_total' => '5',
                'img_total' => '54',
                'urls' => [
                    0 => [
                        'url' => 'http://www.wikihow.com/Choose-a-Pool-Cue',
                        'url_count' => '1',
                        'img_count' => '10',
                    ],
                    1 => [
                        'url' => 'http://www.wikihow.com/Choose-Organic-Fertilizer',
                        'url_count' => '1',
                        'img_count' => '9',
                    ],
                    [...]
                ],
            ],
            1 => [
                'ftp' => 'artlab_allan',
                'url_total' => '16',
                'img_total' => '276',
                'urls' => [
                    0 => [
                        'url' => 'http://www.wikihow.com/Choose-Your-Power-Wheelchair',
                        'url_count' => '1',
                        'img_count' => '14',
                    ],
                    1 => [
                        'url' => 'http://www.wikihow.com/Clean-Your-Pencil-Case',
                        'url_count' => '1',
                        'img_count' => '31',
                    ],
                    [...]
                ],
            ],
            [...]
        ],
    ],
]
 */
