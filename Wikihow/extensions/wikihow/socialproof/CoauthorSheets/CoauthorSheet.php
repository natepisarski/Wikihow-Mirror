<?php

abstract class CoauthorSheet
{
	protected static function getApiAccessToken() {
		global $IP;
		require_once("$IP/extensions/wikihow/docviewer/SampleProcess.class.php");
		$service = SampleProcess::buildService();
		if ( !isset( $service ) ) {
			return null;
		}
		$client = $service->getClient();
		if ( !$client ) {
			return null;
		}
		$token = $client->getAccessToken();
		$token = json_decode($token);
		if ( !$token ) {
			return null;
		}
		$token = $token->access_token;
		return $token;
	}

	/*
	 * this function gets the data from a google sheet
	 * @param string $feedLink the url of google sheets..never really changes
	 * @param string $sheetId the id of the sheet which is easy to find in it's url
	 * @param string $worksheetId the id of the specific tab or worksheet. very hard to find (see getWorksheetIds())
	 * usually I use the google auth playground explorer to find this
	 * but it never changes once oyu have it
	 * @param string $feedLinkSecond the rest of the url after the id, which  also does not change
	 * and specifies that we want json and the name of the access_token param
	 * @param string $token the access token obtained by created the API client
	 * @return Array the data which is read from the sheet line by line and put in an array
	 */
	protected static function getWorksheetData($feedLink, $sheetId, $worksheetId, $feedLinkSecond, $token) {
		$feedLink = $feedLink . $sheetId . '/' . $worksheetId . $feedLinkSecond;

		$sheetData = file_get_contents( $feedLink . $token );
		$sheetData = json_decode( $sheetData );
		$sheetData = $sheetData->{'feed'}->{'entry'};

		return $sheetData;
	}

	/**
	 * @see https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets.values/get
	 */
	protected static function getWorksheetDataV4(string $sheetId, string $worksheetName, string $token): Generator {
		$url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/{$worksheetName}?access_token={$token}";

		$json = @file_get_contents($url);
		if ( !$json ) {
			return "Can't access worksheet '$worksheetName' (sheet ID = $sheetId)";
		}

		$data = @json_decode($json);
		if ( !is_array($data->values ?? null) ) {
			return "Can't parse JSON from worksheet '$worksheetName' (sheet ID = $sheetId)";
		}

		$rows = $data->values;
		$headers = array_shift($rows);
		$size = count($headers);
		$rowNum = 1;
		foreach ($rows as $values) {
			if ( count($values) > $size ) {
				$values = array_slice($values, 0, $size);
			} elseif ( count($values) < $size ) {
				$values = array_pad($values, $size, '');

			}
			yield ++$rowNum => array_combine($headers, $values);
		}

		return ''; // no error message
	}

	/**
	 * @param  string|null  $idStr      Expected format is '123'.
	 *                                  Comes from $row->{'gsx$coauthorid'}->{'$t'}.
	 *                                  Normally a string, but NULL if error.
	 * @param  array        &$errors
	 * @param  string       $rowInfo    An HTML link to the row in the spreadsheet
	 * @param  array|null   $coauthors  All authors in the 'Co-Author Lookup' worksheet
	 *
	 * @return int                      0 if $idStr is malformed
	 */
	protected static function parseCoauthorId(
		string $idStr=null, array &$errors, string $rowInfo, array $coauthors=null): int
	{
		$ret = 0;
		$idTrim = trim($idStr);
		$idInt = (int) $idTrim;
		if ( !$idTrim) {
			$errors[] = "$rowInfo Empty coauthor ID";
		} elseif ( $idInt <= 0 ) {
			$errors[] = "$rowInfo Invalid coauthor ID: $idStr";
		} elseif ( $coauthors && !isset($coauthors[$idInt]) ) {
			$errors[] = "$rowInfo Coauthor ID not found in 'Co-Author Lookup': $idStr";
		} else {
			$ret = $idInt;
		}

		return $ret;
	}

	/**
	 * @param  string|null $blurbId   Expected format is 'v0123_b01'
	 * @param  int         $coaId     Parsed 'Coauthor ID' column
	 * @param  array       &$errors
	 * @param  string      $rowInfo
	 *
	 * @return array                  [ COAUTHOR_ID, BLURB_NUM ], or [0, 0] on failure
	 */
	protected static function parseBlurbId(string $blurbId=null, int $coaId,
		array &$errors, string $rowInfo): array
	{
		$coauthorId = $blurbNum = $error = 0;

		if ( !trim($blurbId) ) {
			$error = "$rowInfo Empty blurb ID";
		}
		elseif ( preg_match('/^v([0-9]+)_b([0-9]+)$/', trim($blurbId), $matches) ) {
			$coauthorId = (int) $matches[1];
			$blurbNum = (int) $matches[2];
			if ( $blurbNum <= 0 ) {
				$error = "$rowInfo Invalid blurb ID (blurb # is $blurbNum): $blurbId";
			} elseif ( $coauthorId && $coaId && ($coauthorId != $coaId) ) {
				$error = "$rowInfo Coauthor ID doesn't match blurb ID: $coaId vs $blurbId";
			}
		}
		else {
			$error = "$rowInfo Invalid blurb ID: $blurbId";
		}

		if ($error) {
			$errors[] = $error;
			return [0, 0];
		} else {
			return [ $coauthorId, $blurbNum ];
		}

	}

	protected static function makeRowInfoHtml(int $rowNo, string $sheetId, string $sheetName): string {
		$rowLink = self::makeRowLink($rowNo, $sheetId, $sheetName);
		return "<span class='spa_location'>$rowLink</span>";
	}

	protected static function makeRowLink(int $rowNo, string $sheetId, string $sheetName): string {
		$worksheets = [
			// Master Expert Verified
			'coauthors' => '1516230615',
			'blurbs' => '493402436',
			'expert' => '0',
			'academic' => '736642124',
			'video' => '237286064',
			'community' => '767097190',
			'videoverified' => '1410489847',
			'chefverified' => '2067227246',
			// Coauthor Localization
			'AR' => '1483416064',
			'CS' => '605737712',
			'DE' => '1748546274',
			'ES' => '1501876960',
			'FR' => '368117995',
			'HI' => '193367141',
			'ID' => '1937719486',
			'IT' => '96087586',
			'JA' => '586685789',
			'KO' => '1959273612',
			'NL' => '161402666',
			'PT' => '152528290',
			'RU' => '1243258189',
			'TH' => '865321898',
			'TR' => '1817984306',
			'VI' => '457551658',
			'ZH' => '290031945',
		];

		$worksheetId = $worksheets[$sheetName];
		$linkText = $sheetName;
		$linkHref = "https://docs.google.com/spreadsheets/d/{$sheetId}/edit#gid={$worksheetId}";
		if ($rowNo) {
			$linkText .= ": $rowNo";
			$linkHref .= "&range=A{$rowNo}";
		}

		return Html::rawElement('a', [ 'href'=>$linkHref, 'target'=>'_blank' ], $linkText);
	}

	/*
	 * @see https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets.values/append
	 *
	protected static function appendRows(string $token, string $sheetId, string $range, array $rows): int
	{
		$url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/{$range}:append?valueInputOption=USER_ENTERED&access_token={$token}";
		$payload = [
			'majorDimension' => 'ROWS',
			'range' => $range,
			'values' => $rows,
		];

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

		$respBody = curl_exec($ch);
		$errorNum = curl_errno($ch);
		$errorStr = curl_error($ch);
		$respCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ( $errorNum || $respCode != 200 ) {
			$extra = $errorNum ? " (errorNum=$errorNum, errorStr=$errorStr)" : '';
			throw new Exception("Sheets API: {$respCode} response code{$extra}. Response body:<pre>$respBody</pre>");
		}

		$resp = @json_decode($respBody);
		if ( !is_object($resp) ) {
			throw new Exception("Sheets API: Can't parse response:<pre>$respBody</pre>");
		}

		if ( !isset($resp->updates->updatedRows) ) {
			throw new Exception("Sheets API: Unexpected response:<pre>$respBody</pre>");
		}

		$appended = (int) $resp->updates->updatedRows;
		$expected = count($rows);
		if ($appended != $expected) {
			throw new Exception("Sheets API: Tried to add $expected rows but only $appended were added.");
		}

		return $appended;
	}
	*/
}
