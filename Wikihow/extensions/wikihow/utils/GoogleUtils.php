<?php

abstract class GoogleBase
{
	protected static $token = null;   // String
	protected static $client = null;  // Google_Client
	protected static $service = null; // Google_Service

	protected function __construct() { }

	protected static function getToken(): string {
		static::initialize();
		return static::$token;
	}

	public static function getService(): Google_Service {
		static::initialize();
		return static::$service;
	}

	abstract protected static function newService(Google_Client $client): Google_Service;

	protected static function initialize() {
		if ( static::$client && !static::$client->isAccessTokenExpired() ) {
			return;
		}

		$client = new Google_Client();
		$client->setAuthConfig(WH_GOOGLE_API_CREDENTIALS_PATH);
		$client->addScope( Google_Service_Drive::DRIVE );

		if ( $client->isAccessTokenExpired() ) {
			$client->fetchAccessTokenWithAssertion();
		}

		static::$token = $client->getAccessToken()['access_token'];
		static::$client = $client;
		static::$service = static::newService($client);
	}

}


class GoogleDrive extends GoogleBase
{
	protected static function newService(Google_Client $client): Google_Service {
		return new Google_Service_Drive($client);
	}
}

/**
 * Utility class to support common Sheets operations, like basic reads and writes.
 * For more advanced use cases, get the underlying Google_Service_Sheets via getService().
 */
class GoogleSheets extends GoogleBase
{
	/**
	 * For when you need to do more advanced stuff than this utility class provides.
	 * https://developers.google.com/sheets/api/quickstart/php
	 */
	protected static function newService(Google_Client $client): Google_Service {
		return new Google_Service_Sheets($client);
	}

	/**
	 * Get rows from a sheet range as an array. This is a simple wrapper around the API.
	 *
	 * @param  string $sheetId https://developers.google.com/sheets/api/guides/concepts#spreadsheet_id
	 * @param  string $range   Sheet name [and range] using A1 notation: "Sheet1!A2:D", or simply "Sheet1"
	 *
	 * The returned array has this format:
	 * [
	 *   0 => [ 0 => 'title_A', 1 => 'title_B' ], // row #1
	 *   1 => [ 0 => 'val_a2',  1 => 'val_b2'  ], // row #2
	 *   2 => [ 0 => 'val_a3',  1 => 'val_b3'  ], // row #3
	 * ]
	 */
	public static function getRows( string $sheetId, string $range ): array {
		$json = static::apiGetRows($sheetId, $range);
		if ( $json === false ) {
			throw new MWException( "Failed to read from '$range' (sheet ID = $sheetId)" );
		}

		$data = @json_decode($json);
		if ( $data === null ) {
			throw new MWException( "Failed to parse JSON from '$range' (sheet ID = $sheetId)" );
		}
		return $data->values ?? []; // '->values' is missing when the selected range is empty
	}

	/**
	 * Get rows from a sheet range as value pairs: (int) $rowNum => (array) $contents
	 *   - $rowNum starts with 2, because the 1st row is assumed to be the headers
	 *   - $contents is an associative array, where the keys are the headers
	 *
	 * The yielded value pairs have this format:
	 *   2 -> [ 'title_A' => 'val_a2', 'title_B' => 'val_b2', ] // row #2
	 *   3 -> [ 'title_A' => 'val_a3', 'title_B' => 'val_b3', ] // row #3
	 *
	 * Usage example:
	 *   $rows = GoogleSheets::getRowsAssoc(YOUR_SPREADSHEET_ID, 'Sheet Name');
	 *   foreach ($rows as $num => $row) {
	 *       $txt .= "From row $num: " . $row['Date Added'] . "\n";
	 *   }
	 *   $errMsg = $rows->getReturn(); // Check for errors. If OK, '' is returned.
	 */
	public static function getRowsAssoc( string $sheetId, string $range ): Generator {
		$json = static::apiGetRows($sheetId, $range);
		if ( $json === false ) {
			return "Failed to read from '$range' (sheet ID = $sheetId)";
		}

		$data = @json_decode($json);
		if ( $data === null ) {
			return "Failed to parse JSON from '$range' (sheet ID = $sheetId)";
		}

		$rows = $data->values ?? [];
		if ( !$rows ) {
			return ''; // empty sheet: return error-free
		}

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
	 * https://developers.google.com/sheets/api/guides/values#writing_to_a_single_range
	 */
	public static function updateRows(string $sheetId, string $range, array $rows): Google_Service_Sheets_UpdateValuesResponse {
		$body = new Google_Service_Sheets_ValueRange( [ 'values' => $rows ] );
		$params = [ 'valueInputOption' => 'USER_ENTERED'];
		return static::getService()->spreadsheets_values->update($sheetId, $range, $body, $params);
	}

	/**
	 * https://developers.google.com/sheets/api/guides/values#appending_values
	 */
	public static function appendRows(string $sheetId, string $range, array $rows): Google_Service_Sheets_AppendValuesResponse {
		$body = new Google_Service_Sheets_ValueRange( [ 'values' => $rows ] );
		$params = [ 'valueInputOption' => 'USER_ENTERED'];
		return static::getService()->spreadsheets_values->append($sheetId, $range, $body, $params);
	}

	### Internals ###

	private static function apiGetRows( string $sheetId, string $range ) {
		$range = urlencode($range);
		$url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/{$range}";
		$params = [ 'access_token' => static::getToken() ];
		$url = wfAppendQuery( $url, $params );
		return @file_get_contents($url);
	}

}
