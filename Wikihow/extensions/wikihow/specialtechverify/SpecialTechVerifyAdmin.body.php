<?php

class SpecialTechVerifyAdmin extends UnlistedSpecialPage {

	const STV_TABLE = 'special_tech_verify_item';

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'TechTestingAdmin' );

		$this->out = $this->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();

		$wgHooks['ShowSideBar'][] = [$this, 'removeSideBarCallback'];
		$wgHooks['ShowBreadCrumbs'][] = [$this, 'removeBreadCrumbsCallback'];
	}

	public function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
	}

	public function removeBreadCrumbsCallback(&$showBreadCrumbs) {
		$showBreadCrumbs = false;
	}

	private function isUserAllowed(\User $user): bool {
		$permittedGroups = [
			'staff',
			'staff_widget',
			'sysop'
		];

		return $user &&
					!$user->isBlocked() &&
					!$user->isAnon() &&
					count(array_intersect($permittedGroups, $user->getGroups())) > 0;
	}

	public function execute( $subPage ) {
		$this->out->setRobotPolicy( "noindex,follow" );

		if ( $this->user->getId() == 0 ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		if ( \Misc::isMobileMode() || !$this->isUserAllowed( $this->user ) ) {
			$this->out->setRobotPolicy( 'noindex, nofollow' );
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( !( $this->getLanguage()->getCode() == 'en' || $this->getLanguage()->getCode() == 'qqx' ) ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'updatesheet' ) {
			$this->out->setArticleBodyOnly( true );
			$data = self::updateSheet();
			print json_encode( $data );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'save_job' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->saveJob( $this->request );
			print json_encode( $data );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'change_job_state' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->changeJobState( $this->request );
			print json_encode( $data );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'get_job_details' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->getJobDetails( $this->request );
			print json_encode( $data );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'run_report' ) {
			$batchName = $this->request->getVal( 'batch_name' );
			$this->exportCSV( $batchName );
			return;
		}

		$this->out->setPageTitle( wfMessage( 'stv_admin' )->text() );
		$this->out->addModuleStyles( 'ext.wikihow.specialtechverifyadmin.styles' );
		$this->out->addModules( 'ext.wikihow.specialtechverifyadmin' );

		$html = $this->getMainHTML();
		$this->out->addHTML( $html );
	}

    /**
     * adds new pages to the tech verify table if they are not already there
     * @param $pageId Array the page ids to insert
     * @param $platform String the platform these pages are on
     * @param $batchName String the batch name
     */
    private static function updatePlatform( $batchName, $platform ) {
		$dbw = wfGetDB( DB_MASTER );
        $table = self::STV_TABLE;
		$values = array( 'stvi_platform' => $platform );
        $conds = array(
			'stvi_batch_name' => $batchName
		);
		$dbw->update( $table, $values, $conds, __METHOD__);
    }
    /**
     * adds new pages to the tech verify table if they are not already there
     * @param $pageId Array the page ids to insert
     * @param $platform String the platform these pages are on
     * @param $batchName String the batch name
     * @param $new bool is this a new batch
     */
    private static function insertPagesForPlatform( $pageIds, $platformName, $batchName, $new ) {
		$dbw = wfGetDB( DB_MASTER );
        $table = self::STV_TABLE;
        $var = "count(*)";
        $cond = array(
			'stvi_platform' => $platformName,
			'stvi_batch_name' => $batchName
		);

        $insertRows = array();

        // check if it's already in the table
        foreach ( $pageIds as $pageId ) {
            $cond['stvi_page_id'] = $pageId;
            $count = $dbw->selectField( $table, $var, $cond, __METHOD__ );
            if ( !$count ) {
				$insertRow = $cond;
				// add the latest revision
				$tempTitle = Title::newFromID( $pageId );
				$gr = GoodRevision::newFromTitle( $tempTitle );
				$latestGood = $gr->latestGood();
				$insertRow['stvi_revision_id'] = $latestGood;
				if ( $new ) {
					$insertRow['stvi_enabled'] = 0;
				}
                $insertRows[] = $insertRow;
            }
        }
		$dbw = wfGetDB( DB_MASTER );

        if ( $insertRows ) {
            $dbw->insert( $table, $insertRows, __METHOD__);
        }
    }


    /**
     * deletes pages from the tech verify table
     * @param $pageIds Array the page ids to insert
     * @param $platform String the platform these pages are on
     * @param $batchName String the batch name
     */
    private static function removePagesForPlatform( $pageIds, $platformName, $batchName ) {
		$dbw = wfGetDB( DB_MASTER );
        $table = self::STV_TABLE;
        $conds = array(
			'stvi_platform' => $platformName,
			'stvi_batch_name' => $batchName,
			'stvi_page_id' => $pageIds
		);

		$dbw->delete( $table, $conds, __METHOD__);
    }

	// for now only accept page ids
	private function getPageIdsFromArticleList( $articleList ) {
		$result = array();
		foreach ( $articleList as $article ) {
			if ( is_numeric( $article ) ) {
				$result[] = intval( $article );
			}
		}
		return $result;
	}

	private function saveJob(\WebRequest $request): array {
		$batchName = strip_tags(trim($request->getVal('batch_name', '')));
		if ( empty( $batchName ) ) {
			$errorMessage = wfMessage('stva_save_bad')->text();
			$errorMessage .= 'no batch';
		}

		$platformName = strip_tags(trim($request->getVal('platform_name', '')));
		if ( empty( $platformName ) ) {
			$errorMessage = wfMessage('stva_save_bad')->text();
			$errorMessage .= 'no platform';
		}

		$newBatch = !$request->getFuzzyBool( 'update_batch' );
		$existingData = $this->getDataForBatch( $batchName );
		if ( $existingData && $newBatch ) {
			$errorMessage = 'duplicate batch name';
		}

		if ( $existingData['platform'] != $platformName ) {
			$this->updatePlatform( $batchName, $platformName );
		}

		$articleList = explode("\n", $request->getVal('article_list', ''));
		if ( empty( $articleList ) ) {
			$errorMessage = wfMessage('stva_save_bad')->text();
			$errorMessage .= 'no articles';
		}
		$pageIds = $this->getPageIdsFromArticleList( $articleList );
		$existingPageIds = $this->getAllPagesForBatch( $batchName );

		$removeIds = array_unique( array_values( array_diff( $existingPageIds, $pageIds ) ) );
		$insertIds = array_unique( array_values( array_diff( $pageIds, $existingPageIds ) ) );

		// add the pages to the DB
		if ( $insertIds ) {
			self::insertPagesForPlatform( $insertIds, $platformName, $batchName, $newBatch );
		}
		if ( $removeIds ) {
			self::removePagesForPlatform( $removeIds, $platformName, $batchName );
		}

		$successMessage = wfMessage('stva_save_good')->text();
		if ( !$newBatch ) {
			$successMessage = "successfully updated data";
		}

		return [
			'success' => empty( $errorMessage ),
			'message' => $errorMessage ?: $successMessage
		];
	}

	public function isMobileCapable() {
		return false;
	}

	private function isApproved( $pageId, $batchName ) {
		$dbr = wfGetDB( DB_REPLICA );
        $table =  self::STV_TABLE;
		$var = array(
			'SUM(if(stvi_vote > 0, 1, 0)) as yes',
			'SUM(if(stvi_vote < 0, 1, 0)) as no',
			'count(stvi_vote) as total',
		);

		// ignore the blank user which is a placeholder
		$cond = array(
			'stvi_page_id' => $pageId,
			'stvi_batch_name' => $batchName,
			'stvi_user_id <> ""',
		);

		$row = $dbr->selectRow( $table, $var, $cond, __METHOD__ );

		$yes = $row->yes;
		$no = $row->no;

		if ( $yes - $no > 1 ) {
			return true;
		}
		return false;
	}

	private function isRejected( $pageId, $batchName ) {
		$dbr = wfGetDB( DB_REPLICA );
        $table =  self::STV_TABLE;
		$var = array(
			'SUM(if(stvi_vote > 0, 1, 0)) as yes',
			'SUM(if(stvi_vote < 0, 1, 0)) as no',
			'count(stvi_vote) as total',
		);

		// ignore the blank user which is a placeholder
		$cond = array(
			'stvi_page_id' => $pageId,
			'stvi_user_id <> ""',
		);

		$row = $dbr->selectRow( $table, $var, $cond, __METHOD__ );

		$yes = $row->yes;
		$no = $row->no;
		$rejected = false;

		if ( $no - $yes > 1 ) {
			$rejected = true;
		} elseif ( $no >= 6 ) {
			$rejected = true;
		}

		return $rejected;
	}

	private function getYesCount( $pageIds, $batchName ) {
		$count = 0;
		foreach ( $pageIds as $pageId ) {
			if ( $this->isApproved( $pageId, $batchName ) ) {
				$count++;
			}
		}
		return $count;
	}

	private function getUnresolvedCount( $batchName ) {
		$dbr = wfGetDB( DB_REPLICA );
        $table = self::STV_TABLE;
        $var = 'count(*)';
        $cond = array(
			'stvi_batch_name' => $batchName,
			'stvi_user_id' => ''
		);
		$count = $dbr->selectField( $table, $var, $cond, __METHOD__ );
		return $count;
	}

	private function getCurrentJobs() {
		$result = array();
		$dbr = wfGetDB( DB_REPLICA );
        $table = self::STV_TABLE;
        $var = '*';
        $cond = array();
		$res = $dbr->select( $table, $var, $cond, __METHOD__ );
		$batches = array();
		foreach( $res as $row ) {
			$batches[$row->stvi_batch_name][] = $row;
		}
		foreach ( $batches as $batchName => $items ) {
			$uniquePages = array();
			$enabled = false;
			$platform = '';
			foreach ($items as $item ) {
				$uniquePages[$item->stvi_page_id] = 1;
				$platform = $item->stvi_platform;
				if ( $item->stvi_enabled == 1 ) {
					$enabled = true;
				}
			}

			$unresolvedCount = $this->getUnresolvedCount( $batchName );
			$yesCount = $this->getYesCount( array_keys( $uniquePages ), $batchName );
			$articleCount = count( $uniquePages );
			$noCount = $articleCount - $yesCount - $unresolvedCount;
			$result[] = array(
				'batch_name' => $batchName,
				'platform' => $platform,
				'unresolved_count' => $unresolvedCount,
				'yes_count' => $yesCount,
				'no_count' => $noCount,
				'article_count' => $articleCount,
				'enabled' => $enabled,
			);
		}
		return $result;
	}

	private function getMainHTML() {
		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$vars = [
			'titleTop' => wfMessage( 'stv_admin' )->text(),
			//'platformMessageTop' => wfMessage( 'stvtestplatform' )->text(),
			//'platformSelect' => wfMessage( 'stvplatformselect' )->text(),
			'addNew' => wfMessage( 'stv_admin_add_new' )->text(),
			//'platformSelectButton' => wfMessage( 'stvplatformselectbutton' )->text(),
			//'choosePlatformBottom' => wfMessage( 'stvchooseplatformbottom' )->text(),
			'report_button_label' => wfMessage('stva_report_button')->text(),
			'jobs' => $this->getCurrentJobs(),
			'job_column_batch_name' => wfMessage('stva_job_column_batch_name')->text(),
			'job_column_topic' => wfMessage('job_column_topic')->text(),
			'job_column_article_count' => wfMessage('job_column_article_count')->text(),
			'job_column_status' => wfMessage('job_column_status')->text(),
			'job_column_yes_count' => wfMessage('job_column_yes_count')->text(),
			'job_column_no_count' => wfMessage('job_column_no_count')->text(),
			'job_column_unresolved_count' => wfMessage('job_column_unresolved_count')->text(),
			'job_column_enabled' => wfMessage('job_column_enabled')->text(),
			'job_column_date' => wfMessage('job_column_date')->text(),
			'job_edit' => $loader->load('job_edit'),
			'enabled_button_label' => wfMessage('stva_enabled_button')->text(),
			'view_feedback_sheet' => 'Feedback Sheet',
			'updateSheet' => "Update Feedback Sheet",
			'feedback_sheet_url' => 'https://docs.google.com/spreadsheets/d/1uILCHWGw9DNnrETQoW7HOnPC63W3qH7q58Pw035XstI/edit#gid=0',
		];
		global $wgIsDevServer;
		if ( $wgIsDevServer ) {
			$vars['view_feedback_sheet_dev'] = "Dev Feedback Sheet";
			$vars['feedback_sheet_url_dev'] = "https://docs.google.com/spreadsheets/d/1IrK1-AUeR99mwXQnDjQ1UAuUD7WEpAC1GY2zidNYN7o/edit#gid=0";
		}
		$html = $m->render( 'specialtechverifyadmin', $vars );

		return $html;
	}

	public static function updateSheet() {
		$file = self::getSheetsFile();
		$sheet = $file->sheet('default');

		$items = $sheet->select();
		$lastItem = end( $items );
		$lastId = $lastItem['id'];
		$allData = self::getSheetFeedbackData( $lastId );
		foreach ( $allData as $data ) {
			$sheet->insert( $data );
		}
	}

	private static function getSheetFeedbackData( $lastId ) {
		if ( !$lastId ) {
			$lastId = 0;
		}
		$dbr = wfGetDB( DB_REPLICA );
		$table = 'special_tech_verify_item';
		$var = '*';
		$cond = array(
				'stvi_vote <> 0',
				'stvi_id > ' . $lastId,
			     );
		$options = array( 'ORDER BY' => 'stvi_id ASC' );
		$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );

        $allData = array();
        foreach ( $res as $row ) {
            $data = array(
                'user' => self::getUserFromId( $row->stvi_user_id ),
                'page' => self::getPageFromId( $row->stvi_page_id ),
                'revision' => $row->stvi_revision_id,
                'vote' => $row->stvi_vote,
                'batch' => $row->stvi_batch_name,
                'platform' => $row->stvi_platform,
                'model' => $row->stvi_feedback_model,
                'version' => $row->stvi_feedback_version,
                'text' => $row->stvi_feedback_text,
                'reason' => $row->stvi_feedback_reason,
                'timestamp' => $row->stvi_timestamp,
                'id' => $row->stvi_id,
            );
            $allData[] = $data;
        }
        return $allData;
	}


	private static function getUserFromId( $userId ) {
		$user = User::newFromID( $userId );
		if ( $user->isAnon() ) {
			return "anon";
		}
		return $user->getName();
	}

	private static function getPageFromId( $pageId ) {
		$title = Title::newFromID( $pageId );
		if ( $title ) {
			return "https:" . $title->getFullURL();
		}
		return $pageId;
	}

	/**
	 * @return Google_Spreadsheet_File
	 */
	private static function getSheetsFile( $isSummaryVideoFeedback = false ): Google_Spreadsheet_File {
		global $wgIsProduction;

		$keys = (Object)[
			'client_email' => WH_GOOGLE_SERVICE_APP_EMAIL,
			'private_key' => file_get_contents(WH_GOOGLE_DOCS_P12_PATH)
		];
		$client = Google_Spreadsheet::getClient($keys);

		// Set the curl timeout within the raw google client.  Had to do it this way because the google client
		// is a private member within the Google_Spreadsheet_Client
		$rawClient = function(Google_Spreadsheet_Client $client) {
			return $client->client;
		};
		$rawClient = Closure::bind($rawClient, null, $client);
		$timeoutLength = 600;
		$configOptions = [
			CURLOPT_CONNECTTIMEOUT => $timeoutLength,
			CURLOPT_TIMEOUT => $timeoutLength
		];
		$rawClient($client)->setClassConfig('Google_IO_Curl', 'options', $configOptions);

		if ($wgIsProduction) {
			$fileId = '1uILCHWGw9DNnrETQoW7HOnPC63W3qH7q58Pw035XstI';
		} else {
			$fileId = '1IrK1-AUeR99mwXQnDjQ1UAuUD7WEpAC1GY2zidNYN7o';
		}
		$file = $client->file($fileId);

		return $file;
	}

	private function getDataForBatch( $batchName ) {
		$dbr = wfGetDB( DB_REPLICA );
		$table = 'special_tech_verify_item';
		$var = '*';
		$cond = array(
			'stvi_batch_name' => $batchName,
		 );

		$options = array( "LIMIT" => 1 );
		$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );
		$result = array();
		foreach ( $res as $row ) {
			$result['platform'] = $row->stvi_platform;
			$result['enabled'] = $row->stvi_enabled;
		}
		return $result;
	}

	private function getAllPagesForBatch( $batchName ) {
		$dbr = wfGetDB( DB_REPLICA );
		$table = 'special_tech_verify_item';
		$var = 'distinct stvi_page_id';
		$cond = array(
			'stvi_batch_name' => $batchName,
		 );
		$options = array();
		$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );
		$pageIds = array();
		foreach ( $res as $row ) {
			$pageIds[] = $row->stvi_page_id;
		}
		return $pageIds;
	}
	private function getPlatform( $batchName ) {
		$dbr = wfGetDB( DB_REPLICA );
        $table = self::STV_TABLE;
        $var = 'stvi_platform';
        $cond = array(
			'stvi_batch_name' => $batchName,
		);
		$platform = $dbr->selectField( $table, $var, $cond, __METHOD__ );
		return $platform;
	}

	private function getVoteCount( $batchName, $pageId, $voteValue ) {
		$dbr = wfGetDB( DB_REPLICA );
        $table = self::STV_TABLE;
        $var = 'count(*)';
        $cond = array(
			'stvi_batch_name' => $batchName,
			'stvi_page_id' => $pageId,
			'stvi_vote' => $voteValue,
			"stvi_user_id <> ''"
		);
		$count = $dbr->selectField( $table, $var, $cond, __METHOD__ );
		return $count;
	}

	private function isResolved( $pageId, $batchName ) {
		$dbr = wfGetDB( DB_REPLICA );
        $table = self::STV_TABLE;
        $var = 'count(*)';
        $cond = array(
			'stvi_batch_name' => $batchName,
			'stvi_page_id' => $pageId,
			"stvi_user_id" => ''
		);
		$count = $dbr->selectField( $table, $var, $cond, __METHOD__ );
		return $count > 0;
	}

	private function exportCSV( $batchName ) {
		$this->out->disable();
		header( 'Content-type: application/force-download' );
		header( 'Content-disposition: attachment; filename="data.csv"' );

		$pageIds = $this->getAllPagesForBatch( $batchName );

		$headers = [
			'Page',
			'Page ID',
			'Batch Name',
			'Platform',
			'Yes Votes',
			'No Votes',
			'Skips',
			'Status',
			// TODO
			//'Feedback',
		];

		$lines[] = implode(",", $headers);

		foreach ( $pageIds as $pageId ) {
			$title = \Title::newFromId( $pageId );
			if ( !$title ) {
				continue;
			}

			$url = 'https://www.wikihow.com/'.$title->getDBKey();
			$url = str_replace(',','%2C',$url);
			$platform = $this->getPlatform( $batchName );
			$yesVotes = $this->getVoteCount( $batchName, $pageId, 1 );
			$noVotes = $this->getVoteCount( $batchName, $pageId, -1 );
			$skips = $this->getVoteCount( $batchName, $pageId, 0 );
			$status = "in queue";
			if ( !$this->isResolved( $pageId, $batchName ) ) {
				if ( $this->isApproved( $pageId, $batchName ) ) {
					$status = "approved";
				} elseif ( $this->isRejected( $pageId, $batchName ) ) {
					$status = "rejected";
				} else {
					$status = "skipped";
				}
			}

			$line = array(
				$url,
				$pageId,
				$batchName,
				$platform,
				$yesVotes,
				$noVotes,
				$skips,
				$status,
				// TODO
				//$feedback
			);

			$lines[] = implode(",", $line);
		}

		print(implode("\n", $lines));
	}

	private function changeJobState( \WebRequest $request ): array {
		$batchName = $request->getVal('batch_name');
		$enabled = $request->getInt('enabled', 0);

		$dbw = wfGetDB( DB_MASTER );
        $table = self::STV_TABLE;
        $conds = array(
            'stvi_batch_name' => $batchName,
        );
        $values = array(
            'stvi_enabled' => $enabled,
		);
        $res = $dbw->update( $table, $values, $conds, __METHOD__ );
		return ['success' => $res];
	}

	private function getJobDetails( \WebRequest $request ): array {
		$batchName = $request->getVal('batch_name');
		$data = $this->getDataForBatch( $batchName );
		$pageIds = $this->getAllPagesForBatch( $batchName );
		$platform = $data['platform'];
		$enabled = $data['enabled'];

		$result = array(
			'platform' => $data['platform'],
			'batchName' => $batchName,
			'enabled' => $data['enabled'],
			'pageIds' => $pageIds
		);
		return $result;
	}

}
