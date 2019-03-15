<?php

class GoogleSpreadsheet {
	private $mToken = null;
	private $mService = null;

	const FEED_LINK = "https://spreadsheets.google.com/feeds/cells/";

	public function __construct() {
		$this->loadToken();
	}

	private function loadToken() {
		global $IP;
		$key = file_get_contents( WH_GOOGLE_DOCS_P12_PATH );
		$cred = new Google_Auth_AssertionCredentials(
			WH_GOOGLE_SERVICE_APP_EMAIL,
			array( 'https://www.googleapis.com/auth/drive', 'https://spreadsheets.google.com/feeds'),
			$key
		);

		$client = new Google_Client();
		$client->setAssertionCredentials( $cred );

		if ( $client->getAuth()->isAccessTokenExpired() ) {
			  $client->getAuth()->refreshTokenWithAssertion();
		}

		$this->mService = new Google_Service_Drive($client);

		$token = $client->getAccessToken();
		$token = json_decode( $token );
		$this->mToken = $token->access_token;
	}

	public function getToken() {
		return $this->mToken;
	}

	public function getService() {
		return $this->mService;
	}

	// this does a curl to get data of atom/xml content type
	public function doAtomXmlRequest( $url, $params = '' ) {
		$url = wfAppendQuery( $url, $params );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 60 * 5 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Content-type: application/atom+xml" ) );
		$res = curl_exec( $ch );
		if ( curl_errno( $ch ) > 0 ) {
			throw new MWException( 'Curl error: ' . curl_error( $ch ) );
		}
		$responseCode = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
		if ( $responseCode != 200 ) {
			throw new MWException( 'Curl non-OK http response code: ' . $responseCode );
		}
		return $res;
	}

	// this does a curl to get data of atom/xml content type
	public function doJSONRequest( $url, $params, $v4=false ) {
		$url = wfAppendQuery( $url, $params );
		$sheetData = file_get_contents( $url );
		if ($sheetData === false) {
			throw new MWException( 'file_get_contents error, could not open url: ' . $url );
		}
		$sheetData = json_decode( $sheetData );
		$sheetData = $v4 ? $sheetData->values : $sheetData->{'feed'}->{'entry'};
		return $sheetData;
	}

	/**
	 * Get all data in a sheet. It uses API V4, which can handle empty cells.
	 *
	 * @see GoogleSpreadsheet::getColumnData()
	 */
	public function getSheetData( $worksheet, $sheetName ) {
		$url = "https://sheets.googleapis.com/v4/spreadsheets/{$worksheet}/values/{$sheetName}";
		$query = [ 'access_token' => $this->getToken() ];
		return $this->doJSONRequest( $url, $query, true );
	}

	/**
	 * Gets data from a google worksheet (including it's tab id) with given number of columns and start row
	 *
	 * WARNING: this method returns unexpected data if there are empty cells. E.g.
	 *    A    B    C
	 * 1  a1   b1   c1
	 * 2  a2        c2
	 * 3  a3   b3   c3
	 *
	 * Results in: [
     *   ['a1', 'b1', 'c1'],
     *   ['a2', 'c2', 'a3'],
     *   ['b3', 'c3', 'a3'],
     * ]
     *
	 * @see GoogleSpreadsheet::getSheetData()
	 */
	public function getColumnData( $worksheet, $startCol, $endCol, $startRow = 1 ) {
		$requestUrl = self::FEED_LINK . $worksheet . "/private/full";
		$query = array(
			'access_token' => $this->getToken(),
			'min-row' => $startRow,
			'min-col' => $startCol,
			'max-col' => $endCol,
		);

		$res = $this->doAtomXmlRequest( $requestUrl, $query );

		$xml = simplexml_load_string( $res );

		$row = array();
		$cols = array();
		$n = 0;
		$columnDiff = $endCol - $startCol;
		foreach ( $xml->entry as $e ) {
			if ( $n > $columnDiff ) {
				$cols[] = $row;
				$n = 0;
			}
			$row[$n] = ( string )$e->content;
			$n++;
		}
		$cols[] = $row;

		return $cols;
	}

	// gets data from a google worksheet (including it's tab id) with given number of columns and start row (JSON style)
	public function getColumnDataJSON( $worksheet, $startCol, $endCol, $startRow = 1 ) {
		$url = self::FEED_LINK . $worksheet . "/private/values";
		$query = array(
			'alt' => 'json',
			'access_token' => $this->getToken(),
			'min-row' => $startRow,
			'min-col' => $startCol,
			'max-col' => $endCol,
		);

		$cols = $this->doJSONRequest( $url, $query );
		$res = $this->parseGoogleJSON($cols, $endCol);
		return $res;
	}

	public function sheetExists($sheetId) {
		$url = self::FEED_LINK . $sheetId . "/private/full";
		$params = [
			'alt' => 'json',
			'access_token' => $this->getToken(),
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, wfAppendQuery($url, $params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_exec($ch);

		if (curl_errno($ch)) {
			$exists = false;
		} else {
			$exists = curl_getinfo($ch)['http_code'] == '200';
		}

		return $exists;
	}

	// gets data from a google worksheet (including it's tab id) with given number of columns and start row (JSON style)
	public function getRawColumnDataJSON( $worksheet, $startCol, $endCol, $startRow = 1 ) {
		$url = self::FEED_LINK . $worksheet . "/private/values";
		$query = array(
			'alt' => 'json',
			'access_token' => $this->getToken(),
			'min-row' => $startRow,
			'min-col' => $startCol,
			'max-col' => $endCol,
		);

		return $this->doJSONRequest( $url, $query );
	}

	private function parseGoogleJSON( $data, $numCol ) {

		$result = array();
		$temp = array();
		foreach ($data as $d) {
			$row = $d->{'gs$cell'}->{'row'};
			$col = $d->{'gs$cell'}->{'col'};
			//brand new row?
			if ($last_row && $last_row != $row) {
				//first, we need to fill in the rest of the cols (if need be)
				$this->addEmpty($temp, ($numCol +1) - $last_col);
				$result[] = $temp;
				unset($temp);
				$last_col = false;
			}

			//did we skip a column?
			if ($last_col) {
				$col_diff = $col - ($last_col+1);
				if ($col_diff > 0) $this->addEmpty($temp, $col_diff);
			}

			//THIS IS WHAT WE WANT!
			$temp[] = $d->{'content'}->{'$t'};

			//for next loop reference
			$last_row = $row;
			$last_col = $col;
		}
		//and add the final one
		$result[] = $temp;

		return $result;
	}

	private function addEmpty(&$temp, $num) {
		for ($i=1; $i < $num; $i++) {
			$temp[] = '';
		}
	}

}
