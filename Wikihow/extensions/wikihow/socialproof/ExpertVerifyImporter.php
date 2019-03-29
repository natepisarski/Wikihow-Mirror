<?php

class ExpertVerifyImporter {
	const SHEETS_URL = 'https://docs.google.com/spreadsheets/d/';
	const SHEET_ID = '19KNiXjlz9s9U0zjPZ5yKQbcHXEidYPmjfIWT7KiIf-I'; // prod
	const SHEET_ID_DEV = '132x2tHn8DrV1lPqILy_zugjMLqDdSTbHtfMwZfXJfy0';

	const FEED_LINK = 'https://spreadsheets.google.com/feeds/list/';
	const FEED_LINK_2 = '/private/values?alt=json&access_token=';

	const DRIVE_ROOT_FOLDER = '0ANxdFk4C7ABLUk9PVA';
	const EXPERT_FEEDBACK_FOLDER_ID = '0B9xdFk4C7ABLakZJdm8zUGFCa1k';
	const COMMUNITY_VERIFY_SHEET_ID = '1uND-YYtRij_XmY5bSAce2VtXP4Lgsl7X8UICuRvzmVw';

	// folder link is like this:
	// https://drive.google.com/a/wikihow.com/folderview?id=0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc&usp=drive_web&usp=docs_home&ths=true&ddrp=1#
	const CPORTAL_DOH_FOLDER = '0B66Rhz56bzLHflROYm5oYlc2dWtHRHNoRE1RandlaG0tY1l0YUtLVWZLMXVydHlZeUtZbk0';
	const CPORTAL_PROD_FOLDER = '0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc';
	const EXPIRED_ITEMS_FOLDER = '0B0oYgpQLcJkJdnY5Nk95SFFadkk';

	var $updateHistorical = true;
	var $includeImages = false;

	##################################
	# Method Group 1
	#
	# These methods are used to import the 'Master Expert Verified' sheet into the DB.
	# They are called by MasterExpertSheetUpdate via the AdminSocialProof special page.
	##################################

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

			// TODO get WH user ID so we can populate vi_wh_id (from name or directly from sheet)
			$coauthorIdStr = $row->{'gsx$coauthorid'}->{'$t'};
			$coauthorName = trim($row->{'gsx$people'}->{'$t'});
			$whUserName = $row->{'gsx$portalusername'}->{'$t'};
			$initials = $row->{'gsx$initials'}->{'$t'};
			$category = $row->{'gsx$category'}->{'$t'};
			$nameUrl = trim($row->{'gsx$namelinkurlelizonly'}->{'$t'});
			$imageUrl = $row->{'gsx$approvedimageurlelizonly'}->{'$t'};

			$coauthorId = self::parseCoauthorId($coauthorIdStr, $result['errors'], $rowInfo);

			if ( $coauthorId && isset($coauthors[$coauthorId]) ) {
				// TODO report 1st occurrence too (nice to have)
				$result['errors'][] = "$rowInfo Duplicate coauthor ID: $coauthorIdStr";
			}

			 // TODO validate WH ID

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
				$vd = VerifyData::newVerifierFromAll( $coauthorId, $coauthorName, '', '', $nameUrl, $category, $imageUrl, $initials, $whUserName );
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
				// TODO report 1st occurrence too (nice to have)
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

	public static function getSheetId() {
		global $wgIsProduction;
		if ($wgIsProduction) {
			return self::SHEET_ID;
		} else {
			return self::SHEET_ID_DEV;
		}
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

	##################################
	# Method Group 2
	#
	# These methods are used to manipulate Google Docs.
	# Some users:
	#     prod/extensions/wikihow/ContentPortal/lib/GoogleDoc.php
	#     prod/extensions/wikihow/reverification/reverification_tool/Reverification.body.php
	#     prod/extensions/wikihow/socialproof/AdminExpertDoc.body.php
	#     prod/extensions/wikihow/socialproof/ExpertVerifyImporter.php
	#     prod/maintenance/wikihow/updateDriveFilePermissions.php
	##################################

	// create an expert google doc for use by experts to review
	// params:
	// $service - the google php api service which can be obtained by the getService function
	//          - used for doing the api calls to google
	// $article - the name of an article (as a url or just a title (or even a page id will work)
	// $name - the name of a user to create the doc for..it will be used in the title of the doc
	// $context - a context object which is used to get the wikitext output
	public function createExpertDoc( $service = null, $article, $name, $context, $folderId=self::EXPERT_FEEDBACK_FOLDER_ID ) {
		$title = Misc::getTitleFromText( $article );
		if ( !$title ) {
			return null;
		}

		if (is_null($service)) {
			$service = $this->getService();
		}

		$titleText = $this->getExpertDocTitle( $article, $name );

		$file = new Google_Service_Drive_DriveFile();
		$file->setTitle($titleText);
		$file->setDescription($name);

		$parent = new Google_Service_Drive_ParentReference();
		$parent->setId( $folderId );
		$file->setParents( array( $parent ) );
		$data = $this->getExpertDocContent( $title, $article, $name, $context->getOutput() );

		$createdFile = $service->files->insert($file, array(
			'data' => $data,
			'mimeType' => 'text/html',
			'uploadType' => 'multipart',
			'convert' => 'true'
		));

		// set permissions on new file
		$newPermission = new Google_Service_Drive_Permission();
		$newPermission->setRole( 'reader' );
		$newPermission->setType( 'anyone' );
		$newPermission->setWithLink( true );
		$newPermission->setAdditionalRoles( array( 'commenter' ) );
		$service->permissions->insert($createdFile->id, $newPermission);

		//$permissions = $service->permissions->listPermissions($createdFile->id);

		return $createdFile;
	}

	/*
	 * this function gets items older than 6 months from a list of folders then:
	 * removes the anyoneWithLink permission
	 * removes the anyone permission
	 * removes the old parents
	 * moves the file to a new folder
	 */
	public function fixPermissions($maxResults = 100) {
		$service = $this->getService();

		$oldParents = array(
			self::CPORTAL_PROD_FOLDER,
			self::EXPERT_FEEDBACK_FOLDER_ID,
		);

		$newParent = new Google_Service_Drive_ParentReference();
		$newParent->setId( self::EXPIRED_ITEMS_FOLDER );

		$datetime = date( "c", strtotime( "-6 months" ) );

		$processedCount = 0;
		foreach ( $oldParents as $oldParent ) {
			if ( $processedCount >= $maxResults ) {
				break;
			}
			$maxResults = $maxResults - $processedCount;
			$parameters = array( 'maxResults' => $maxResults );
			$parameters['q'] =  "('$oldParent' in parents) and modifiedDate < '$datetime'";
			decho("searching with q", $parameters['q']);
			$fileList = $service->files->listFiles( $parameters );
			$files = $fileList->items;
			foreach( $files as $file ) {
				decho("id", $file->id, false);
				//$permissions = $service->permissions->listPermissions($createdFile->id);

				//delete anyone permissions
				try {
					$service->permissions->delete( $file->id, 'anyone' );
				} catch ( Google_Service_Exception $e ) {
				}

				try {
					$service->permissions->delete( $file->id, 'anyoneWithLink' );
				} catch ( Google_Service_Exception $e ) {
				}
				$service->parents->insert( $file->id, $newParent );

				try {
					//delete old parent
					$service->parents->delete( $file->id, $oldParent );
				} catch ( Google_Service_Exception $e ) {
				}

				try {
					$service->parents->insert( $file->id, $newParent );
				} catch ( Google_Service_Exception $e ) {
					decho("could not add parent", $e, false);
				}
				$processedCount++;
			}
		}

		return $processedCount;
	}

	// on old docs, update the permissions so only wikihow can view
	public function updatePermissions( $context ) {
		$request = $context->getRequest();
		$service = $this->getService();

		// round 1..just fix the permissions
		$this->fixPermissions( $service );
		exit;

	}

	public function moveFiles( $context ) {
		$request = $context->getRequest();
		$service = $this->getService();

		$ids = $request->getArray( 'articles' );

		$folderId = self::CPORTAL_PROD_FOLDER;
		$oldFolderId = self::DRIVE_ROOT_FOLDER;

		$newParent = new Google_Service_Drive_ParentReference();
		$newParent->setId( $folderId );

		$processed = 0;
		$max = 1000;
		foreach ( $ids as $fileId ) {
			if ( !$fileId ) {
				continue;
			}

			// add new parent
			$service->parents->insert( $fileId, $newParent );

			//delete old parent
			$service->parents->delete( $fileId, $oldFolderId );

			$processed++;

			// stop after the first one for now
			if ( $processed >= $max ) {
				break;
			}
		}
	}

	// create multiple docs

	// this function uses a $context and expects there to be request variables
	// which provide one or more article names and an expet name
	public function createExpertDocs( $context ) {
		$request = $context->getRequest();
		$service = $this->getService();

		$name = $request->getVal( 'name' );
		$articles = $request->getArray( 'articles' );
		$articles = array_filter( $articles );

		$this->includeImages = $request->getFuzzyBool( 'images' );

		$files = array();
		foreach ( $articles as $article ) {
			$file = $this->createExpertDoc( $service, $article, $name, $context );
			if ( !$file ) {
				$files[] = array(
					"title" => $article,
					"error"=>"Error: cannot make title from ".$article);
			} else {
				$files[] = $file;
			}
		}

		return $files;
	}

	public function listExpertDocParents( $context ) {
		// only acts on first id in list
		$request = $context->getRequest();
		$ids = $request->getArray( 'articles' );
		$service = $this->getService();
		$parents = array();
		foreach ( $ids as $fileId ) {
			if ( !$fileId ) {
				continue;
			}
			$parents = $service->parents->listParents( $fileId );
			break;
		}
		//for now just echo the result since we haven't implemented the javascript handling
		decho('parents', $parents);
		exit();
		return $parents;
	}

	// get list of expert docs
	public function listExpertDocs( $context ) {
		$request = $context->getRequest();
		$ids = $request->getArray( 'articles' );
		if ( count( $ids ) > 0 && $ids[0] ) {
			return $this->getFiles( $context );
		}

		$service = $this->getService();

		$parameters = array();
		//$parentId = self::EXPERT_FEEDBACK_FOLDER_ID;
		$parentId = self::CPORTAL_PROD_FOLDER;
		$parameters['q'] =  "'$parentId' in parents";
		//$parameters['q'] =  "'$parentId' in parents and title = 'Kiss'";
		//$parameters['maxResults'] = 500;

		$fileList = $service->files->listFiles($parameters);

		$files = $fileList->items;

		// Order  by createdDate desc. Do this locally since createdDate is not a valid
		// parameter for listing files
		usort($files, function($a, $b) {
			$a = $a->createdDate;
			$b = $b->createdDate;
			if ($a == $b) {
				return 0;
			}
			return ($a > $b) ? -1 : 1;
		});

		return  $files;
	}

	public function deleteExpertDocs( $context ) {
		$request = $context->getRequest();
		$service = $this->getService();
		$result = array();

		$ids = $request->getArray( 'articles' );

		$count = 0;
		foreach ( $ids as $id ) {
			if ( !$id ) {
				continue;
			}
			// can't delete special sheets
			if ( $id == self::getSheetId() ) {
				continue;
			}
			if ( $id == self::EXPERT_FEEDBACK_FOLDER_ID ) {
				continue;
			}
			if ( $id == self::COMMUNITY_VERIFY_SHEET_ID ) {
				continue;
			}
			$service->files->delete($id);
			$count++;
		}
		$result[] = array( "status" => "$count file(s) deleted" );
		return $result;
	}

	// get the google php api service
	private function getService() {
		global $IP;
		require_once("$IP/extensions/wikihow/docviewer/SampleProcess.class.php");
		$service = SampleProcess::buildService();
		return $service;
	}

	// get the text title of the google doc
	private function getExpertDocTitle( $article, $name ) {
		$result = $article;
		if ( $name ) {
			$result .= " - " . $name;
		}
		return $result;
	}

	// get the beginning lines of the google doc
	private function getContentFirstLines( $title, $article, $name ) {
		$result = "";

		$url = Misc::makeUrl( $title );
		$titleLink = Html::rawElement( 'a', array( 'href'=>$url ), $article );

		$result .= $titleLink;

		if ( $name ) {
			$result .= " - " . $name;
		}
		$result .= "<br><br>";

		if ( $name ) {
			$result .= "Hi, ".$name."! ";
		} else {
			$result .= "Hi! ";
		}

		$instructions = wfMessage('expert_doc_instructions', $name, $article )->text();
		$result .= $instructions;
		$result .= "<h2>Introduction</h2>";

		return $result;
	}

	// get the ending lines of the google doc
	private function getContentLastLines( $article, $name ) {
		$result = wfMessage('expert_doc_instructions_bottom', $name, $article )->text();
		return $result;
	}

	private static function getSubmittedQuestions( $title, $approved, $limit ) {
		$dbr = wfGetDB(DB_REPLICA);
		$table =  QADB::TABLE_SUBMITTED_QUESTIONS;
		$vars = array('qs_question');
		$conds = [
			'qs_article_id' => $title->getArticleID(),
			'qs_ignore' => 0,
			'qs_curated' => 0,
			'qs_proposed' => 0,
			'qs_approved' => $approved ? 1 : 0
		];

		$options = [ 'ORDER BY' => 'qs_submitted_timestamp', 'LIMIT' => $limit ];
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
		return $res;
	}

	private static function getQAHtml( $title ) {
		$doc = phpQuery::newDocument();

		$qadb = QADB::newInstance();
		// get approved first
		$approvedResults = array();
		$approved = true;
		$limit = 15;
		$res = self::getSubmittedQuestions( $title, $approved, $limit );
		foreach ( $res as $row ) {
			$approvedResults[] = $row->qs_question;
		}

		// if we got fewer than 15 results, fill them with unapproved submitted questions
		$n = count( $approvedResults );
		if ( $n < 15 ) {
			$approved = false;
			$limit = 15 - $n;
			$res = self::getSubmittedQuestions( $title, $approved, $limit );
			foreach ( $res as $row ) {
				$approvedResults[] = $row->qs_question;
			}
		}

		pq('')->prepend('<div id="results"></div>');

		// give it a nice h2 header
		pq('#results')->html('<h2><span class="mw-headline">Unanswered Questions</span></h2>');
		pq('#results')->append('<ul id="approved"></ul>');
		foreach ( $approvedResults as $txt ) {
			pq('#approved')->append("<li>".$txt."</li>");
		}

		$html = $doc->htmlOuter();

		return $html;
	}

	private function processHTML( $body, $title = null ) {

		$qa = "";
		if ( $title ) {
			$qa = self::getQAHtml( $title );
		}

		$doc = phpQuery::newDocument( $body );
		pq('.section.steps:last')->after($qa);

		if ( $this->includeImages == false ) {
			pq('.mwimg')->remove();
		} else {
			foreach (pq('.mwimg') as $node) {
				$pqNode = pq($node);
				$src = $pqNode->find('img')->attr('src');
				if ( $pqNode->nextAll('.step')->find('.whvid_gif')->length > 0 ) {
					$src = $pqNode->nextAll('.step')->find('.whvid_gif')->attr('data-src');
				}
				$pqNode->find('img')->attr('src', "http://pad1.whstatic.com".$src );
				$pqNode->find('img')->attr('width', 364);
				$pqNode->find('img')->attr('height', 273);
			}
			pq('.mwimg')->after('<br>');
		}

		pq('.m-video')->remove();
		pq('.relatedwikihows')->remove();
		pq('.altblock')->remove();
		pq('.step_num')->remove();
		pq('.section.video')->remove();
		pq('.section.testyourknowledge')->remove();
		pq('.section.sample')->remove();
		pq('.anchor')->remove();
		pq('.clearall')->remove();
		pq('.showsources')->remove();
		pq('#intro')->contentsUnwrap();
		pq('.section_text')->contentsUnwrap();
		pq('.step')->contentsUnwrap();
		pq('.section.steps')->contentsUnwrap();
		pq('.stepanchor')->remove();

		$html = $doc->htmlOuter();
		return $html;
	}

	// get the content to put in the google doc
	private function getExpertDocContent( $title, $article, $name, $output ) {
		// add the first line of text which is the article name and users name
		$result = $this->getContentFirstLines( $title, $article, $name );

		// now add the html of the article
		$revision = Revision::newFromTitle( $title );
		$popts = $output->parserOptions();
		$popts->setTidy( true );
		$popts->setEditSection( false );
		$parserOutput = $output->parse( $revision->getText(), $title, $popts );

		// process the html

		$magic = WikihowArticleHTML::grabTheMagic($revision->getText());
		$parserOutput = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
		$parserOutput = $this->processHTML( $parserOutput, $title );

		$result .= $parserOutput;

		$result .= $this->getContentLastLines( $article, $name );

		return $result;
	}

	private function getFiles( $context ) {
		$request = $context->getRequest();
		$ids = $request->getArray( 'articles' );
		$service = $this->getService();
		$files = array();
		foreach ( $ids as $fileId ) {
			if ( !$fileId ) {
				continue;
			}
			$files[] = $service->files->get( $fileId );
		}
		return $files;
	}

	/* Not used (Alberto, 2019-01)
	// get list of expert docs
	public function updateFolderPermission( $context ) {
		$request = $context->getRequest();
		$service = $this->getService();

		$parameters = array();
		$parentId = self::CPORTAL_PROD_FOLDER;
		$parameters['q'] =  "'$parentId' in parents";
		$newPermission = new Google_Service_Drive_Permission();
		$newPermission->setRole( 'writer' );
		$newPermission->setType( 'user' );
		$newPermission->setValue( 'carrie@wikihow.com' );
		$service->permissions->insert(self::CPORTAL_PROD_FOLDER, $newPermission);
		return;
	}
	*/

}
