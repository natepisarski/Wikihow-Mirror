<?php

namespace ExpInv;

use SheetInv\ParsingResult;

/**
 * The project's read-only data source, supported by Google Sheets.
 */
class Spreadsheet
{
	private $sheetId; // String

	public function __construct()
	{
		global $wgIsProduction;

		$this->sheetId = $wgIsProduction
			? '12Yocb0MhECB71uQLoJf84r5yNzGa3I4WN_nYP2JuhPI'
			: '1yqi-uEZ-pekfakrVvUYGYeMhXp50_6IOV6SsooiYpq0';
	}

	public function parseSheet(): ParsingResult
	{
		$result = $this->parseSummarySheet('Payment Summary!A2:E');
		$result = $this->parseVerificationSheet($result, 'verified', 'Verification Details!A2:B', 'Verification Details');
		$result = $this->parseVerificationSheet($result, 'reverified', 'Re-Verification Details!A2:B', 'Re-Verification Details');
		$result->data = array_values($result->data); // Use integer keys so we can iterate over the array in mustache
		return $result;
	}

	private function parseSummarySheet(string $range) : ParsingResult
	{
		$data = [];
		$errors = [];
		$idx = 1;
		$rows = \GoogleSheets::getRows($this->sheetId, $range);

		foreach ($rows as $row) {

			$idx++;
			$expertName = trim($row[0]);
			$email = trim($row[1]);
			$verifiedCnt = trim($row[2]);
			$reverifiedCnt = trim($row[3]);
			$paid = trim($row[4]);

			if (!$expertName || !$email || $verifiedCnt === '' || $reverifiedCnt === '' || !$paid) {
				$errors[] = "Empty cells in row $idx of the 'Payment Summary' sheet";
			}
			elseif (!is_numeric($verifiedCnt) || !is_numeric($reverifiedCnt)) {
				$errors[] = "Non-numeric values in row $idx of the 'Payment Summary' sheet";
			}
			elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$errors[] = "Invalid email address in row $idx of the 'Payment Summary' sheet";
			}
			elseif (isset($data[$expertName])) {
				$errors[] = "Duplicate name '$expertName' in the 'Payment Summary' sheet (row $idx)";
			}
			else {
				$data[$expertName] = [
					'fullName' => $expertName,
					'firstName' => explode(' ', $expertName)[0],
					'email' => $email,
					'verifiedCnt' => (int) $verifiedCnt,
					'reverifiedCnt' => (int) $reverifiedCnt,
					'paid' => $paid,
				];
			}
		}

		return new ParsingResult($data, $errors, []);

	}

	private function parseVerificationSheet(ParsingResult $res, string $type,
		string $range, string $sheetName): ParsingResult
	{
		if ($res->isBad()) {
			return $res;
		}

		$expertName = null;
		$skipExpert = false;
		$idx = 1;
		$rows = \GoogleSheets::getRows($this->sheetId, $range);

		foreach ($rows as $row) {
			$idx++;
			$firstCol = trim($row[0]); // It can be an expert name or an article URL
			$articleCnt = trim($row[1]);

			if (!$firstCol || !$articleCnt) {
				$res->errors[] = "Empty cells in row $idx of the '$sheetName' sheet";
			}
			elseif (!is_numeric($articleCnt)) {
				$res->errors[] = "Non-numeric value in row $idx of the '$sheetName' sheet";
			}
			elseif (strpos($firstCol, 'http') !== 0) { // New name
				$expertName = $firstCol;
				if (!isset($res->data[$expertName])) {
					$skipExpert = true;
					$res->errors[] = "'$expertName' is in '$sheetName' (row $idx) but not in 'Payment Summary'";
				}
				elseif (isset($res->data[$expertName][$type])) {
					$skipExpert = true;
					$res->errors[] = "'$expertName' is duplicated in the '$sheetName' sheet (row $idx)";
				}
				else {
					$skipExpert = false;
					$res->data[$expertName][$type] = []; // Article URLs
				}
			}
			elseif (!$skipExpert) { // Article URL
				$res->data[$expertName][$type][] = $firstCol;
			}
		}
		return $res;
	}

}

/* Here is an example of the data returned by parseSheet()

[
   'data' =>
  [
    0 =>
    [
      'fullName' => 'Alicia Hyatte, LCSW',
      'firstName' => 'Alicia',
      'email' => 'alicia@wholefamilyliving.com',
      'verifiedCnt' => 3,
      'reverifiedCnt' => 0,
      'paid' => '$30.00',
      'verified' =>
      [
        0 => 'http://www.wikihow.com/Cope-when-an-Older-Friend-Leaves-for-College-While-You-Stay-in-High-School',
        1 => 'http://www.wikihow.com/Cope-with-Social-Anxiety-at-the-Gym',
        2 => 'http://www.wikihow.com/Live-with-Your-College-Kid-over-Summer-Break',
      ],
    ],
    1 =>
    [
      'fullName' => 'Pippa Elliott, MRCVS',
      'firstName' => 'Pippa',
      'email' => 'pippaelliott02@gmail.com',
      'verifiedCnt' => 24,
      'reverifiedCnt' => 5,
      'paid' => '$188.65',
      'verified' =>
      [
        0 => 'http://www.wikihow.com/Buy-a-Personal-Protection-Dog',
        1 => 'http://www.wikihow.com/Buy-a-Puppy-Online-Safely',
        2 => 'http://www.wikihow.com/Care-for-a-Havana-Brown-Cat',
        [...]
      ],
      'reverified' =>
      [
        0 => 'http://www.wikihow.com/Care-for-Beagles',
        1 => 'http://www.wikihow.com/Diagnose-Adrenal-Gland-Disease-in-Pomeranians',
        2 => 'http://www.wikihow.com/Prevent-Hypoglycemia-in-Dogs',
        [...]
      ],
    ],
    [...]
  ],
   'errors' => [],
   'warnings' => [],
]
*/
