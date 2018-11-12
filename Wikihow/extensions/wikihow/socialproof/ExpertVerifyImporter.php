<?php

class ExpertVerifyImporter {
	const SHEETS_URL = "https://docs.google.com/spreadsheets/d/";
	const SHEET_ID = "19KNiXjlz9s9U0zjPZ5yKQbcHXEidYPmjfIWT7KiIf-I";

	const FEED_LINK = "https://spreadsheets.google.com/feeds/list/";
	const FEED_LINK_2 = "/private/values?alt=json&access_token=";

	const DRIVE_ROOT_FOLDER = "0ANxdFk4C7ABLUk9PVA";
	const EXPERT_FEEDBACK_FOLDER_ID = "0B9xdFk4C7ABLakZJdm8zUGFCa1k";
	const COMMUNITY_VERIFY_SHEET_ID = "1uND-YYtRij_XmY5bSAce2VtXP4Lgsl7X8UICuRvzmVw";

	// folder link is like this:
	// https://drive.google.com/a/wikihow.com/folderview?id=0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc&usp=drive_web&usp=docs_home&ths=true&ddrp=1#
	const CPORTAL_DOH_FOLDER = '0B66Rhz56bzLHflROYm5oYlc2dWtHRHNoRE1RandlaG0tY1l0YUtLVWZLMXVydHlZeUtZbk0';
	const CPORTAL_PROD_FOLDER = '0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc';
	const EXPIRED_ITEMS_FOLDER = '0B0oYgpQLcJkJdnY5Nk95SFFadkk';

	const VLOOKUPDEV_SHEET_ID = 'oc5liec';

	var $updateHistorical = true;
	var $includeImages = false;

	private function getRevId( $revisionLink ) {
		$output = array();
		parse_str( $revisionLink, $output );
		return $output['oldid'];
	}

	// the ids of the 3 worksheets on the expert verify spreadsheet
	public static function getWorksheetIds() {
		global $wgIsDevServer;
		$result = array( "ocopjuk" => "community", "oc6ksye" => "academic", "od6" => "expert", "oy6rv04" => "chefverified", "o3x9v8q" => "video", 'onbrokt' => 'videoverified' );
		//$result = array( 'onbrokt' => 'videoverified' );
		if ( $wgIsDevServer ) {
			$result["o3p076"] = "dev";
		}
		return $result;
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
	 * @param string $worksheetId the id of the specific tab or worksheet. very hard to find
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

	private function getMasterExpertSheetMainTabsData( $token ) {
		$data = array();
		foreach ( self::getWorksheetIds() as $worksheetId => $worksheetName ) {
			$sheetData = $this->getWorksheetData( self::FEED_LINK, self::SHEET_ID, $worksheetId, self::FEED_LINK_2, $token );
			if ( !$sheetData || !is_array( $sheetData ) ) {
				continue;
			}
			// update the sheet data to also include the worksheet name for use later
			foreach( $sheetData as $row ) {
				$row->worksheetName = $worksheetName;
			}
			$data = array_merge( $data, $sheetData );
		}
		return $data;
	}

	public function getSpreadsheet() {
		global $wgIsDevServer;

		$token = $this->getApiAccessToken();

		// get the data from the spreadsheet
		$mainTabsData = $this->getMasterExpertSheetMainTabsData( $token );

		// now parse that data
		$result = $this->parseGoogleResponse( $mainTabsData );

		//now get vlookup sheet
		$vLookupSheetId = 'op2q2od';

		$vLookupData = $this->getWorksheetData( self::FEED_LINK, self::SHEET_ID, $vLookupSheetId, self::FEED_LINK_2, $token );
		$result = array_merge_recursive( $result, $this->parseGoogleVerifierResponse( $vLookupData ) );

		// Schedule the maintenance for the Reverification tool.  Use the Main-page title because we need a title
		// in order for the job to work properly
		$title = Title::newFromText('Main-Page');
		$job = Job::factory('ReverificationMaintenanceJob', $title);
		JobQueueGroup::singleton()->push($job);

		return $result;
	}

	private	function getWhUserName( $profileUrl ) {
		if ( $profileUrl ) {
			$pieces = explode( "User:", $profileUrl );
			if ( count( $pieces ) > 1 ) {
				return $pieces[1];
			}
		}
		return "";
	}

	/**
	 * Get the prefixed DB key associated with an ID
	 *
	 * @param int $id the page_id of the article
	 * @return boolean the value of is_redirect for a given id
	 */
	public static function isPageIdRedirect( $id ) {
		$dbr = wfGetDB( DB_SLAVE );

		$r = $dbr->selectField(
			'page',
			array( 'page_is_redirect' ),
			array( 'page_id' => $id ),
			__METHOD__
		);

		if ( $r === false ) {
			return null;
		}

		return $r;
	}

	/**
	 * Make an associative array of titles from an array of IDs
	 *
	 * @param array $ids of Int Array of IDs
	 * @return Array of Titles with key of the id
	 */
	public static function newFromIDsAssoc( $ids ) {
		if ( !count( $ids ) ) {
			return array();
		}
		$dbr = wfGetDB( DB_SLAVE );

		$fields = array(
			'page_namespace', 'page_title', 'page_id',
			'page_len', 'page_is_redirect', 'page_latest',
		);

		$res = $dbr->select(
			'page',
			$fields,
			array( 'page_id' => $ids ),
			__METHOD__
		);

		$titles = array();
		foreach ( $res as $row ) {
			$titles[$row->page_id] = Title::newFromRow( $row );
		}
		return $titles;
	}

	private function parseGoogleResponse( $data ) {
		global $wgIsDevServer;

		$result = array('errors' => array(), 'warnings'=>array(), 'info'=>array(), 'imported'=>array());

		$dataRows = array();
		$avIds = array();
		$pageIds = array();
		$titles = array();

		$ids = array();
		foreach( $data as $row ) {
			$ids[]= $row->{'gsx$articleid'}->{'$t'};
		}
		$titles = self::newFromIDsAssoc( $ids );

		foreach( $data as $row ) {
			$worksheetName = $row->worksheetName;

			$dev = intval( $row->{'gsx$devleaveblankifnotdev'}->{'$t'} );
			if ( $dev === 1 && !$wgIsDevServer ) {
				continue;
			}

			$articleName = $row->{'gsx$articlename'}->{'$t'};

			// if this is the chefverified sheet we can return early since it has no reviewer data
			if ( $worksheetName == "chefverified" ) {
				// we got the article name we don't need to get any other data
				$pageId = $row->{'gsx$articleid'}->{'$t'};

				// we should hvae the page id if not just figure it out
				// although this takes forever so it is NOT recommended
				if ( !$pageId ) {
					$result['warnings'][] = "Specify article id for <b>$articleName</b> on Chef/Video Verified sheet for faster performance.";
					$title = Misc::getTitleFromText( $articleName );
					$pageId = $title->getArticleID();
					$titles[$pageId] = $title;
				}

				$verifyData = VerifyData::newFromWorksheetName( $worksheetName );
				$pageIds[$pageId][] = $verifyData;
				$result['imported'][] = array( $pageId=>$verifyData );
				continue;
			}

			$date = $row->{'gsx$verifieddate'}->{'$t'};
			$name = $row->{'gsx$verifiername'}->{'$t'};
			$pageId = $row->{'gsx$articleid'}->{'$t'};
			if ( !$name && $worksheetName != 'videoverified' ) {
				continue;
			}
			$primaryBlurb = $row->{'gsx$verifierprimaryblurb'}->{'$t'};
			$hoverBlurb = $row->{'gsx$verifierhoverblurb'}->{'$t'};

			$profileUrl = $row->{'gsx$verifierprofileurlifwewantpicture'}->{'$t'};
			$whUserName = $this->getWhUserName( $profileUrl );

			$revisionLink = $row->{'gsx$revisionlink'}->{'$t'};
			$revId = $this->getRevId( $revisionLink );
			if ( !$revId ) {
				$result['errors'][] = "No revision id found for article: $articleName";
				continue;
			}

			// check that the article name matches the page id
			if ( $dev !== 1 ) {
				$title = $titles[$pageId];
				if ( !$title ) {
					$result['warnings'][] = "No title matches ID <b>$pageId</b>.  Entered name is: <b>$articleName</b>";
				} else {
					$isRedirect = $title->isRedirect();
					if ( $isRedirect ) {
						$titleLink = Html::rawElement( 'a', array( 'href'=>$articleName ), $articleName );
						$result['warnings'][] = "$titleLink : $pageId is a redirect.";
					}

					$titleFromName = Misc::getTitleFromText( $articleName );

					if ( !$titleFromName || !$titleFromName->exists() ) {
						$result['warnings'][] = "ArticleName not found.  ArticleName: <b>$articleName</b> does not exist. ArticleID: <b>$pageId</b>.  ";
					} else if ( $pageId != $titleFromName->getArticleID() ) {
						$articleID = $titleFromName->getArticleID();
						$result['warnings'][] = "article ID mismatch.  ArticleID: <b>$pageId</b> but ArticleName: <b>{$titleFromName->getDBkey()}</b> has ID of <b>{$titleFromName->getArticleID()}</b> .";
					}
				}
			}

			$nameLink = $row->{'gsx$namelinkoptional'}->{'$t'};
			$mainNameLink = $row->{'gsx$mainnamelinkoptional'}->{'$t'};

			$blurbLink = $row->{'gsx$blurblinkoptional'}->{'$t'};

			$verifyData = VerifyData::newFromAll( $date, $name, $primaryBlurb, $hoverBlurb, $whUserName, $nameLink, $mainNameLink, $blurbLink, $revId, $worksheetName );
			$pageIds[$pageId][] = $verifyData;
			$result['imported'][] = array( $pageId=>$verifyData );
		}

		foreach ( $pageIds as $pageId => $verifyDataResults ) {
			if ( count( $verifyDataResults ) > 1 ) {
				$count = count( $verifyDataResults );
				$title = $titles[$pageId];
				if ( $title ) {
					$a = Html::rawElement( "a", array( 'href'=>$title->getLinkURL() ), "http://www.wikihow.com/{$title->getPartialURL()}" );
					$result['info'][] = "$a has $count verifiers.";
				}
			}
		}

		VerifyData::replaceData( $pageIds );

		return $result;
	}

	private function validateHTML( $input, &$errors ) {
		$html = '<!DOCTYPE html><html><head><title>test</title></head><body>' . $input . '</body></html>';
		return MWTidy::checkErrors( $html, $errors );
	}


	private function parseGoogleVerifierResponse( $data ) {
		$result = array('errors' => array(), 'warnings'=>array(), 'info'=>array(), 'imported'=>array());

		$dataRows = array();
		foreach( $data as $row ) {

			$name = $row->{'gsx$people'}->{'$t'};
			if ( !$name ) {
				continue;
			}

			$primaryBlurb = $row->{'gsx$primaryblurb'}->{'$t'};
			$hoverBlurb = $row->{'gsx$hoverblurb'}->{'$t'};
			$nameLink = $row->{'gsx$namelinkurlelizonly'}->{'$t'};
			$userName = $row->{'gsx$portalusername'}->{'$t'};

			if ( trim( $nameLink ) && !filter_var( trim( $nameLink ), FILTER_VALIDATE_URL ) ) {
				$errors = '';
				$valid = self::validateHTML( $nameLink, $errors );
				if ( !$valid ) {
					$result['errors'][] ="vlookup: nameLink HTML error for name " . $name . "<br>" . $errors;
				}
			}
			$category = $row->{'gsx$category'}->{'$t'};
			$image = $row->{'gsx$approvedimageurlelizonly'}->{'$t'};
			$initials = $row->{'gsx$initials'}->{'$t'};

			$verifyData = VerifyData::newVerifierFromAll( $name, $primaryBlurb, $hoverBlurb, $nameLink, $category, $image, $initials, $userName );
			$dataRows[$name] = $verifyData;
		}

		VerifyData::replaceVerifierData( $dataRows );

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

	public static function getSubmittedQuestions( $title, $approved, $limit ) {
		$dbr = wfGetDB(DB_SLAVE);
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

	public function getFiles( $context ) {
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
			if ( $id == self::SHEET_ID ) {
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

}
