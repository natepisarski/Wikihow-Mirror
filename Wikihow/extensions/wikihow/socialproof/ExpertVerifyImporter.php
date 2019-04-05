<?php

class ExpertVerifyImporter {
	const SHEETS_URL = 'https://docs.google.com/spreadsheets/d/';
	const SHEET_ID = '19KNiXjlz9s9U0zjPZ5yKQbcHXEidYPmjfIWT7KiIf-I'; // prod
	const SHEET_ID_DEV = '1UrMX45_NeFw-TTlXXiZcVsv42s_dTt6MYUAWg0tJzKs';
	const FEED_LINK = 'https://spreadsheets.google.com/feeds/list/';
	const FEED_LINK_2 = '/private/values?alt=json&access_token=';

	/**
	 * Imports the 'Master Expert Verified' sheet into the DB.
	 * Called by MasterExpertSheetUpdate via the AdminSocialProof special page.
	 */
	public function doImport() {
		$token = $this->getApiAccessToken();
		$dbVerifiers = self::getVerifiersFromDB();
		$result = ['errors' => [], 'warnings' => [], 'imported' => []];

		$coauthors = $this->fetchSheetCoauthors($token, $dbVerifiers, $result);
		$blurbs = $this->fetchSheetBlurbs($token, $coauthors, $result);
		$articles = $this->fetchSheetArticles($token, $coauthors, $blurbs, $result);

		if (!$result['errors']) {
			VerifyData::replaceAllData($coauthors, $blurbs, $articles);
			// Schedule the maintenance for the Reverification tool. Use the Main-page title because we need a title
			// in order for the job to work properly
			$title = Title::newFromText('Main-Page');
			$job = Job::factory('ReverificationMaintenanceJob', $title);
			JobQueueGroup::singleton()->push($job);
		}

		return $result;
	}

	public static function getSheetId() {
		global $wgIsProduction;
		if ($wgIsProduction) {
			return self::SHEET_ID;
		} else {
			return self::SHEET_ID_DEV;
		}
	}

	/**
	 * Worksheet ("tab") IDs for the old Google Sheets API (v3).
	 *
	 * They can be found in:
	 * https://spreadsheets.google.com/feeds/worksheets/{$spreadsheetId}/private/full?alt=json&access_token={$token}
	 */
	public static function getWorksheetIds() {
		return [
			'od6'     => 'expert',
			'oc6ksye' => 'academic',
			'o3x9v8q' => 'video',
			'ocopjuk' => 'community',
			'onbrokt' => 'videoverified',
			'oy6rv04' => 'chefverified',
		];
	}

	/**
	 * Import article coauthors from "Co-Author Lookup".
	 *
	 * @param  $token        Google Docs API token
	 * @param  $dbCoauthors  Existing coauthors, so we can detect removals
	 * @param  &$result      To be populated with info/errors/warnings
	 *
	 * @return array         Valid coauthors in the sheet
	 */
	private function fetchSheetCoauthors(string $token, array $dbCoauthors, array &$result): array
	{
		$coauthorsSheetId = 'op2q2od';
		$data = $this->getWorksheetData( self::FEED_LINK, self::getSheetId(), $coauthorsSheetId, self::FEED_LINK_2, $token );
		$num = 1;
		$coauthors = [];
		$names = [];

		foreach ($data as $row) {
			$rowInfo = self::makeRowInfoHtml(++$num, 'coauthors');

			$coauthorIdStr = $row->{'gsx$coauthorid'}->{'$t'};
			$coauthorName = trim($row->{'gsx$people'}->{'$t'});
			$whUserName = trim($row->{'gsx$portalusername'}->{'$t'});
			$initials = $row->{'gsx$initials'}->{'$t'};
			$category = $row->{'gsx$category'}->{'$t'};
			$nameUrl = trim($row->{'gsx$namelinkurlelizonly'}->{'$t'});
			$imageUrl = $row->{'gsx$approvedimageurlelizonly'}->{'$t'};

			$coauthorId = self::parseCoauthorId($coauthorIdStr, $result['errors'], $rowInfo);
			if ( $coauthorId && isset($coauthors[$coauthorId]) ) {
				// TODO report 1st occurrence too (nice to have)
				$result['errors'][] = "$rowInfo Duplicate coauthor ID: $coauthorIdStr";
			}

			$whUserId = 0;
			if ($whUserName) {
				$whUser = User::newFromName($whUserName);
				$whUserId = $whUser ? $whUser->getId() : 0;
			}

			if ( !$coauthorName ) {
				$result['errors'][] = "$rowInfo Empty coauthor name";
			} elseif ( strlen($coauthorName) < 2 ) {
				$result['errors'][] = "$rowInfo Coauthor name too short: $coauthorName";
			} elseif ( strlen($coauthorName) > 120 ) {
				$result['errors'][] = "$rowInfo Coauthor name too long: $coauthorName";
			} elseif ( isset($names[$coauthorName]) ) {
				$result['errors'][] = "$rowInfo Duplicate coauthor name: $coauthorName";
			}

			if ( !$initials ) {
				$result['errors'][] = "$rowInfo Empty initials";
			} elseif ( strlen($initials) > 10 ) {
				$result['errors'][] = "$rowInfo Initials too long: $initials";
			}

			if ( !$category ) {
				$result['errors'][] = "$rowInfo Empty category";
			} elseif ( strlen($category) < 3 ) {
				$result['errors'][] = "$rowInfo Category too short: $category";
			} elseif ( strlen($category) > 120 ) {
				$result['errors'][] = "$rowInfo Category too long: $category";
			}

			if ( $nameUrl && !filter_var($nameUrl, FILTER_VALIDATE_URL) ) {
				$result['errors'][] = "$rowInfo Invalid Name Link URL: $nameUrl";
			}

			if ( $imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL) ) {
				$result['errors'][] = "$rowInfo Invalid Approved Image URL: $imageUrl";
			}

			if ($coauthorId) {
				$vd = VerifyData::newVerifierFromAll( $coauthorId, $coauthorName, '', '', $nameUrl,
					$category, $imageUrl, $initials, $whUserId, $whUserName );
				$coauthors[$coauthorId] = $vd;
				$names[$coauthorName] = true;
			}
		}

		$rowInfo = self::makeRowInfoHtml(0, 'coauthors');
		foreach ($dbCoauthors as $vID => $vName) {
			if ( !isset($coauthors[$vID]) ) {
				// TODO - ask user for confirmation, and then allow removals
				$result['errors'][] = "$rowInfo Verifier was removed: id=$vID, name='$vName'";
			}
		}

		return $coauthors;
	}

	/**
	 * Import from the "Blurb Lookup" worksheet
	 *
	 * @param  $token       Google Docs API token
	 * @param  &$coauthors  Verifiers in 'Co-Author Lookup', so we can detect mismatches
	 * @param  &$result     To be populated with info/errors/warnings
	 *
	 * @return array        Valid blurbs in the sheet
	 */
	private function fetchSheetBlurbs(string $token, array &$coauthors, array &$result): array
	{
		$blurbsSheetId = 'o85rbmm';
		$data = $this->getWorksheetData( self::FEED_LINK, self::getSheetId(), $blurbsSheetId, self::FEED_LINK_2, $token );
		$num = 1;
		$blurbs = [];

		foreach ($data as $row) {
			$rowInfo = self::makeRowInfoHtml(++$num, 'blurbs');

			$coauthorIdStr = $row->{'gsx$coauthorid'}->{'$t'};
			$blurbId = trim($row->{'gsx$blurbid'}->{'$t'});
			$byline = trim($row->{'gsx$byline'}->{'$t'});
			$blurb = trim($row->{'gsx$blurb'}->{'$t'});

			$coauthorId = self::parseCoauthorId($coauthorIdStr, $result['errors'], $rowInfo, $coauthors);

			list($coauthorId2, $blurbNum) = self::parseBlurbId(
				$blurbId, $coauthorIdStr, $coauthorId, $result['errors'], $rowInfo);

			if ( $blurbNum && isset($blurbs[$blurbId]) ) {
				// TODO report 1st occurrence too (nice to have)
				$result['errors'][] = "$rowInfo Duplicate blurb ID: $blurbId";
			}

			if ( !$byline ) {
				$result['errors'][] = "$rowInfo Empty byline";
			} elseif ( strlen($byline) < 2 ) {
				$result['errors'][] = "$rowInfo Byline too short: $byline";
			} elseif ( strlen($byline) > 150 ) {
				$result['errors'][] = "$rowInfo Byline too long: $byline";
			}

			if ( !$blurb ) {
				$result['errors'][] = "$rowInfo Empty blurb";
			} elseif ( strlen($blurb) < 2 ) {
				$result['errors'][] = "$rowInfo Blurb too short: $blurb";
			} elseif ( strlen($blurb) > 900 ) {
				$result['errors'][] = "$rowInfo Blurb too long: $blurb";
			}

			$blurbs[$blurbId] = compact('blurbId', 'coauthorId', 'blurbNum', 'byline', 'blurb');

			// Set the default blurb
			if ( $blurbNum === 1 && isset($coauthors[$coauthorId]) ) {
				$coauthors[$coauthorId]->blurb = $byline;
				$coauthors[$coauthorId]->hoverBlurb = $blurb;
			}
		}

		$rowInfo = self::makeRowInfoHtml(0, 'blurbs');
		foreach ($coauthors as $coauthor) {
			if (!$coauthor->blurb || !$coauthor->hoverBlurb) {
				$name = $coauthor->name;
				$id = $coauthor->verifierId;
				$result['errors'][] = "$rowInfo Missing default blurb for coauthor: $name (id=$id)";
			}
		}

		return $blurbs;
	}

	/**
	 * Import verified articles from: "Expert", "Academic", "YouTube", "Community",
	 * "Video Team Verified", and "Chef Verified".
	 *
	 * @param  $token      Google Docs API token
	 * @param  $coauthors  Verifiers in 'Co-Author Lookup', so we can detect mismatches
	 * @param  $blurbs     Blurbs in 'Blurbs', so we can detect mismatches
	 * @param  &$result    To be populated with info/errors/warnings
	 *
	 * @return array       Valid articles in the sheet
	 */
	private function fetchSheetArticles( string $token, array $coauthors, array $blurbs, array &$result ): array {
		$allRows = []; // every row in every worksheet
		$aids = [];    // article IDs every worksheet: [ aid => count ] (to detect duplicates)

		foreach ( self::getWorksheetIds() as $worksheetId => $worksheetName ) {

			$rows = $this->getWorksheetData( self::FEED_LINK, self::getSheetId(), $worksheetId, self::FEED_LINK_2, $token );
			if ( !$rows || !is_array($rows) ) {
				$result['errors'][] = "Unable to access worksheet '$worksheetName' (id=$worksheetId)";
				continue;
			}

			$num = 1;
			foreach( $rows as $row ) {
				$row->num = ++$num;
				$skip = 1 === intval( $row->{'gsx$devleaveblankifnotdev'}->{'$t'} );
				if ($skip) {
					continue;
				}
				$row->worksheetName = $worksheetName;
				$allRows[] = $row;
				$aid = (int) $row->{'gsx$articleid'}->{'$t'};
				$aids[$aid] = 1 + ($aids[$aid] ?? 0);
			}
		}

		$articles = [];
		$titles = self::newFromIDsAssoc( $aids );

		foreach( $allRows as $row ) {
			$worksheetName = $row->worksheetName;
			$rowInfo = self::makeRowInfoHtml($row->num, $worksheetName);

			// Data validation

			$errors = [];

			$pageIdStr = $row->{'gsx$articleid'}->{'$t'};
			$pageId = (int) $pageIdStr;
			$articleName = $row->{'gsx$articlename'}->{'$t'};

			// $pageId
			$title = $titles[$pageId];
			$titleLink = $title ? Html::rawElement( 'a', ['href'=>$title->getCanonicalURL()], $title->getDBKey()) : '';
			if ( !trim($pageIdStr) ) {
				$errors[] = "$rowInfo Empty article ID";
			} elseif ( $pageId <= 0 ) {
				$errors[] = "$rowInfo Invalid article ID: $pageIdStr";
			} elseif ( $aids[$pageId] > 1 ) {
				$errors[] = "$rowInfo Duplicate Article ID: $pageIdStr";
			} elseif ( !$title ) {
				$errors[] = "$rowInfo Article ID not found in DB: $pageIdStr";
			} elseif ( !$title->inNamespace(NS_MAIN) ) {
				$ns = $title->getNamespace();
				$errors[] = "$rowInfo Not an article: $titleLink (id=$pageIdStr, namespace=$ns)";
			} elseif ( $title->isRedirect() ) {
				$result['warnings'][] = "$rowInfo Redirect: $titleLink (id=$pageIdStr)";
			}

			// $articleName
			$t2 = Misc::getTitleFromText( $articleName );
			if ( !trim($articleName) ) {
				$errors[] = "$rowInfo Empty article name";
			} elseif ( !$t2 || !$t2->exists() ) {
				$errors[] = "$rowInfo Article Name not found in DB: $articleName";
			} else if ( $title && $pageId != $t2->getArticleID() ) {
				$key2 = $t2->getDBkey();
				$id2 = $t2->getArticleID();
				$errors[] = "$rowInfo Mismatch: ArticleID is $pageIdStr, but the ID for '$key2' is $id2";
			}

			if ( !in_array($worksheetName, ['chefverified', 'videoverified']) ) {
				$coauthorIdStr = $row->{'gsx$coauthorid'}->{'$t'};
				$coauthorId = self::parseCoauthorId($coauthorIdStr, $result['errors'], $rowInfo, $coauthors);

				$blurbId = trim($row->{'gsx$blurbid'}->{'$t'});
				list($coauthorId2, $blurbNum) = self::parseBlurbId(
					$blurbId, $coauthorIdStr, $coauthorId, $result['errors'], $rowInfo);

				if ( $blurbNum && !isset($blurbs[$blurbId]) ) {
					$errors[] = "$rowInfo Blurb ID not found in 'Blurbs': $blurbId";
				}
			}

			if ( $worksheetName != 'chefverified' ) {
				$date = trim($row->{'gsx$verifieddate'}->{'$t'});
				if ( !$date ) { // TODO validate
					$errors[] = "$rowInfo Empty Verified Date";
				}
				$revisionLink = trim($row->{'gsx$revisionlink'}->{'$t'});
				$revId = (int) $this->getRevId( $revisionLink );
				if ( !trim($revisionLink) ) {
					$errors[] = "$rowInfo Empty Revision Link URL";
				} elseif ( !$revId ) {
					$errors[] = "$rowInfo Invalid Revision Link URL: $revisionLink";
				}
			}

			if ($errors) {
				$result['errors'] = array_merge($result['errors'], $errors);
				continue;
			}

			// Make a VerifyData object

			if ( $worksheetName == 'chefverified' ) { // this sheet has no verifier data
				$verifyData = VerifyData::newChefArticle( $worksheetName, $pageId );
			}
			elseif ( $worksheetName == 'videoverified' ) {
				$verifyData = VerifyData::newVideoTeamArticle( $worksheetName, $pageId, $revId, $date );
			}
			else {
				$primaryBlurb = $blurbs[$blurbId]['byline'];
				$hoverBlurb = $blurbs[$blurbId]['blurb'];

				$coauthorName = $coauthors[$coauthorId]->name;

				$verifyData = VerifyData::newArticle( $pageId, $coauthorId, $date, $coauthorName, $blurbId, $primaryBlurb,
					$hoverBlurb, $revId, $worksheetName );
			}
			$articles[$pageId][] = $verifyData;
			$result['imported'][] = [ $pageId => $verifyData ];
		}

		return $articles;
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
	private static function parseCoauthorId(
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

	/*
	 * @param  string|null  $idStr      Expected format is 'v0123'.
	 *                                  Comes from $row->{'gsx$coauthorid'}->{'$t'}.
	 *                                  Normally a string, but NULL if error.
	 * @param  array        &$errors
	 * @param  string       $rowInfo    An HTML link to the row in the spreadsheet
	 * @param  array|null   $coauthors  All authors in the 'Co-Author Lookup' worksheet
	 *
	 * @return int                      0 if $idStr is malformed
	 *
	private static function parseCoauthorId(
		string $idStr=null, array &$errors, string $rowInfo, array $coauthors=null): int
	{
		$idInt = 0;
		$idTrim = trim($idStr);
		if ( !$idTrim) {
			$errors[] = "$rowInfo Empty coauthor ID";
		}
		elseif ( preg_match('/^v([0-9]+)$/', $idTrim, $matches) ) {
			$idInt = (int) $matches[1];
			if ( $coauthors && !isset($coauthors[$idInt]) ) {
				$idInt = 0;
				$errors[] = "$rowInfo Coauthor ID not found in 'Co-Author Lookup': $idStr";
			}
		}
		else {
			$errors[] = "$rowInfo Invalid coauthor ID: $idStr";
		}

		return $idInt;
	}
	*/

	/**
	 * @param  string|null $blurbId   Expected format is 'v0123_b01'
	 * @param  string      $coaIdStr  Raw 'Coauthor ID' column
	 * @param  int         $coaId     Parsed 'Coauthor ID' column
	 * @param  array       &$errors
	 * @param  string      $rowInfo
	 *
	 * @return array                  [ COAUTHOR_ID, BLURB_NUM ], or [0, 0] on failure
	 */
	private static function parseBlurbId(string $blurbId=null, string $coaIdStr, int $coaId,
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
				$error = "$rowInfo Coauthor ID doesn't match blurb ID: $coaIdStr vs $blurbId";
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

	private static function getVerifiersFromDB(): array {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(VerifyData::VERIFIER_TABLE, ['vi_id', 'vi_name']);
		$dbVerifiers = [];
		foreach ($res as $row) {
			$dbVerifiers[ (int) $row->vi_id ] = $row->vi_name;
		}
		return $dbVerifiers;
	}


	private static function makeRowInfoHtml(int $rowNo, string $sheetName): string {
		$sheetIds = [
			'coauthors' => '1516230615',
			'blurbs' => '493402436',
			'expert' => '0',
			'academic' => '736642124',
			'video' => '237286064',
			'community' => '767097190',
			'videoverified' => '1410489847',
			'chefverified' => '2067227246',
		];

		$sheetId = $sheetIds[$sheetName];
		$linkText = $sheetName;
		$linkHref = self::SHEETS_URL . self::getSheetId() . "/edit#gid={$sheetId}";
		if ($rowNo) {
			$linkText .= ": $rowNo";
			$linkHref .= "&range=A{$rowNo}";
		}
		$rowLink = Html::rawElement('a', [ 'href'=>$linkHref, 'target'=>'_blank' ], $linkText);

		return "<span class='spa_location'>$rowLink</span>";
	}

	private function getRevId( $revisionLink ) {
		$output = array();
		parse_str( $revisionLink, $output );
		return $output['oldid'];
	}

	private function getApiAccessToken() {
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
	private function getWorksheetData( $feedLink, $sheetId, $worksheetId, $feedLinkSecond, $token ) {
		$feedLink = $feedLink . $sheetId . '/' . $worksheetId . $feedLinkSecond;

		$sheetData = file_get_contents( $feedLink . $token );
		$sheetData = json_decode( $sheetData );
		$sheetData = $sheetData->{'feed'}->{'entry'};

		return $sheetData;
	}

	/**
	 * Make an associative array of titles from an array of IDs
	 *
	 * @param array $ids of Int Array of IDs
	 * @return Array of Titles with key of the id
	 */
	private static function newFromIDsAssoc( $ids ) {
		if ( !count( $ids ) ) {
			return array();
		}
		$dbr = wfGetDB( DB_REPLICA );

		$fields = array(
			'page_namespace', 'page_title', 'page_id',
			'page_len', 'page_is_redirect', 'page_latest',
		);

		$res = $dbr->select(
			'page',
			$fields,
			array( 'page_id' => array_keys($ids) ),
			__METHOD__
		);

		$titles = array();
		foreach ( $res as $row ) {
			$titles[$row->page_id] = Title::newFromRow( $row );
		}
		return $titles;
	}

}
