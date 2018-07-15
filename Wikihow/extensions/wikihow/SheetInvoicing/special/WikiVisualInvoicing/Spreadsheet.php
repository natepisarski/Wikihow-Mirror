<?php

namespace WVI;

use GoogleSpreadsheet;

use SheetInv\ParsingResult;

/**
 * The project's read-only data source, supported by Google Sheets.
 *
 */
class Spreadsheet extends GoogleSpreadSheet
{
	private $summarySheetId;	// String
	private $detailsSheetId;	// String

	public function __construct ()
	{
		global $wgIsProduction;

		parent::__construct();

		$sheetId = $wgIsProduction
			? '1ImKmb5gyFmpa3RaTW3ZYD_YjRYQF739ub3MqykcT-Qg'
			: '1exPmD6RuqWnS_4C_UkYKBk33xlQ_zIP3Uw2Kl_sWtA4';

		$this->summarySheetId = "$sheetId/1";
		$this->detailsSheetId = "$sheetId/2";
	}

	public function parseSheet(): ParsingResult
	{
		$summaryResult = $this->parseSummarySheet();
		return $this->parseDetailsSheet($summaryResult);
	}

	private function parseSummarySheet() : ParsingResult
	{
		$minCol = 1; $maxCol = 7; $minRow = 2;
		$xml = $this->fetchSheetData($this->summarySheetId, $minCol, $maxCol, $minRow);
		$data = [];
		$errors = [];
		$idx = 1;

		foreach ($xml->entry as $row) {

			$idx++;
			$ftp = trim($row->xpath('gsx:ftp')[0]);
			$name = trim($row->xpath('gsx:name')[0]);
			$email = trim($row->xpath('gsx:email')[0]);
			$articles = trim($row->xpath('gsx:articles')[0]);
			$images = trim($row->xpath('gsx:images')[0]);
			$loan = trim($row->xpath('gsx:loandeducted')[0]);
			$paid = trim($row->xpath('gsx:amountpaid')[0]);

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

		$minCol = 1; $maxCol = 3; $minRow = 2;
		$xml = $this->fetchSheetData($this->detailsSheetId, $minCol, $maxCol, $minRow);
		$parent = null;
		$child = null;
		$skipChild = false;
		$idx = 1;

		foreach ($xml->entry as $row) {

			$idx++;
			$firstCol = trim($row->xpath('gsx:ftp')[0]); // It can be an FTP, or an article URL
			$urlCount = trim($row->xpath('gsx:ofarticles')[0]);
			$imgCount = trim($row->xpath('gsx:ofimages')[0]);

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
			else if (!$skipChild) { // Article URL
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

	/**
	 * @return SimpleXMLElement|bool
	 */
	private function fetchSheetData(string $worksheet, int $minCol, int $maxCol, int $minRow)
	{
		$url = "https://spreadsheets.google.com/feeds/list/$worksheet/private/full";
		$query = [
			'access_token' => $this->getToken(),
			'min-col' => $minCol,
			'max-col' => $maxCol,
			'min-row' => $minRow,
		];

		$res = $this->doAtomXmlRequest($url, $query);
		return simplexml_load_string($res);
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
