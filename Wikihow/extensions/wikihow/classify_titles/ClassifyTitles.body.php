<?php
/* This page lets you post jobs to the python project - classify_titles
 * The python code will classify if the short phrases are good or bad as article titles.
 * The jobs posted here are run by /data/classify_titles/runjobs.py
 * The results can be collected from this page
 */

class ClassifyTitles extends UnlistedSpecialPage {

	private $errors = [];
	const JOBNAME = "Classify Titles";

	public function __construct() {
		global $wgHooks;
		$this->action = RequestContext::getMain()->getTitle()->getPartialUrl();
		parent::__construct( $this->action );
		$wgHooks[ 'ShowSideBar' ][] = [ 'Turker::removeSideBarCallback' ];
	}

	public static function removeSideBarCallback( &$showSideBar ) {
		$showSideBar = false;
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	// Function to get results
	private function processBatchRequest( $postedValues ) {
		$allData = False;
		$lastBatch = False;
		$batchId = 0;

		// get all data
		if ( isset( $postedValues[ 'alldata' ] ) ) {
			$allData = True;
		}
		elseif ( isset( $postedValues[ 'lastbatch' ] ) ) {
			// find the last batch id
			$dbr = wfGetDB( DB_REPLICA );
			$row = $dbr->selectRow( 'classify_titles.ctjobs', [ 'ct_id' ], [ 'ct_status=2' ], __METHOD__, [ 'ORDER BY' => 'ct_id DESC' ] );
			if ( $row ) {
				$batchId = $row->ct_id;
			}
		}elseif ( $postedValues[ 'batchid' ] != '' ) {
				$batchId = (int) $postedValues[ 'batchid' ];
		}

		$this->getResults( $batchId, $allData );
	}

	// Used to fectch results from the db
	private function getResults( $batchId, $allData ) {

		$dbr = wfGetDB( DB_REPLICA );
		if ( !$allData ) {
			$conds= [ "ct_batchid" => $batchId ];
		}else{
			$conds = [];
		}
		$colms = [ 'ct_text', 'ct_result', 'ct_confidence', 'ct_rest' ];

		// get data
		$res = $dbr->select( 'classify_titles.ctresults', $colms, $conds, __METHOD__, [] );
		$fileName = 'ClassifiedTitles';

		// turn off the html being displayed
		$this->getOutput()->setArticleBodyOnly( true );

		// file header
		header( "Content-Type: text/csv" );
		header( 'Content-Disposition: attachment; filename= "'.$fileName.date("_Y-m-d_H_i_s").'".csv' );

		$header = [ 'Title', 'Result', 'Confidence', 'OtherData' ];

		$fileHandle = fopen( 'php://output' ,'w' );
		fputcsv( $fileHandle, $header, ',', '"' );
		if ( $res ) {
			foreach ( $res as $row ) {
				$restData = explode( ',', str_replace( [ '(', ')' ], '', $row->ct_rest ) );
				if ( $restData ) {
					$data = array_merge( [ $row->ct_text, $row->ct_result, $row->ct_confidence ], $restData );
				} else {
					$data = [ $row->ct_text,$row->ct_result, $row->ct_confidence ];
				}
				fputcsv( $fileHandle, $data, ',', '"' );
			}
		}
	}

	// Displays existing job status at page load
	private function getJobStatus() {
		$dbr = wfGetDB( DB_REPLICA );
		$dbw = wfGetDB( DB_MASTER );
		$html = '';
		$conds = [ "ct_newjobs" => 1 ] ;
        $conds[] = "ct_jobname='".self::JOBNAME."'";

		$res = $dbr->select( 'classify_titles.ctjobs', [ '*' ], $conds, __METHOD__, [] );
		foreach ( $res as $row ) {
			if ( $row->ct_status == 0 ) {
				// not started the job yet
				$html .= '<p><b> Batch id - ' .$row->ct_id .' ' .$row->ct_jobname  .' not started </b><br /> '.$row->ct_jobmessage . $row->ct_error .'</p>';
			}elseif ( $row->ct_status == 1 ) {
				// job started
				$html .= '<p><b>Batch id - '.$row->ct_id .' ' .$row->ct_jobname .' is in progress </b><br />'.$row->ct_jobmessage . $row->ct_error .'</p>';
			}
			elseif ( $row->ct_status == 2 ) {
				// job completed or terminated due to error
				// show completed message and update the db that the job is done
				// update the job as been done.
				$res = $dbw->update('classify_titles.ctjobs', [ 'ct_newjobs' => '0' ],  [ 'ct_id' => $row->ct_id ], __METHOD__, [] );

				if ( empty ( $row->ct_error ) ) {
					$html .= '<p><b>Batch id.'.$row->ct_id .' ' .$row->ct_jobname .' completed </b><br />'.$row->ct_jobmessage.'</p>';
				}else {
					//there was an error and the job got terminated because of it
					$html .= '<p><b>'.$row->ct_jobname.$row->ct_jobmessage .' not completed because of errors: '. $row->ct_error.'</b><br/></p>';
				}
			}
		}
		return $html;
	}

	// Post the job to the jobdb table
	private function processUpload( $uploadfile, $userName ) {

		// Check file for format and save it to the local dir
		if ( !file_exists( $uploadfile ) || !is_readable( $uploadfile ) ) {
			$this->$errors[] = 'Could not find file. File not uploaded.';
			return 'Bad File' ;
		}
		// Compatibility with mac generated csv files
		ini_set( 'auto_detect_line_endings', true );

		// python does this too, but lets check here anyways
		$row = 1;
		if ( ( $fileHandle = fopen( $uploadfile,'r') ) !== FALSE ) {
			while ( $data = fgetcsv( $fileHandle ) ) {
				if ( $row == 1 ) {
					 $row++;
					continue;
				}
				$row++;
			}
			// empty file?
			if ( $row == 1 ) {
				$this->$errors[] = "File had no contents, check file again";
				return 'Bad File';
			}
			fclose( $fileHandle );
		}
		ini_set( 'auto_detect_line_endings', FALSE );

		$jobMessage = "No. of rows in the file " .( $row - 2 );
		// copy file the local location, where python can find it
		$fileSaveName = '/data/classify_titles/csv/classify_titles'.date("_Y-m-d_H_i_s").'.csv';

		if ( !move_uploaded_file( $uploadfile, $fileSaveName ) ) {
			$this->$errors[] = 'Could not save a local copy of the file, check write permission or path.';
			return 'bad file' ;
		}
		else{
			// file is uploaded, add params and add the job to the job table
			// add entry to the job table!
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert( 'classify_titles.ctjobs',
							['ct_status'=>'0',
							'ct_filename'=>$fileSaveName,
							'ct_lasttimestamp'=>date("YmdHis"),
							'ct_newjobs'=>'1',
							'ct_jobname'=> self::JOBNAME,
							'ct_jobmessage'=>$jobMessage,
							'ct_user'=>$userName]
							, __METHOD__
							, []
						);
		}
		return 'Upload job added';
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute( $par ) {

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userName = $user->getName();

		// Check permissions
		$userGroups = $user->getGroups();
		if ( ( $userName != 'Rjsbhatia' ) && ( $user->isBlocked() || !( in_array( 'staff', $userGroups ) ) ) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $req->getVal( 'upload' ) ) {
			$html = $this->processUpload( $req->getFileTempName( 'ctitlesfile' ), $userName );
		} elseif ( $req->getVal( 'oldbatch' ) ) {
			$out->setArticleBodyOnly( true );
			$html = $this->processBatchRequest( $req->getValues() );
			$out->addHTML( $html );
			return;
		}

		$out->addModules( 'ext.wikihow.ClassifyTitles' );

		$out->setHTMLTitle( 'Classify Titles - wikiHow' );
		$out->setPageTitle( 'Classify Titles' );

		$must_vars = [
						'action' => $this->action,
						'jobStatus' => $this->getJobStatus()
						];

		$options = [ 'loader' => new Mustache_Loader_FilesystemLoader( __DIR__ ) , ];
		$m = new Mustache_Engine( $options );
		$tmpl = $m->render( 'classifytitles.mustache', $must_vars );

		#$tmpl = $this->getGuts();
		if ( $html ) $tmpl .= $html;
		if ( !empty( $this->$errors ) ) {
			$errors = '<div class="errors">ERRORS:<br />'.implode('<br />', self::$errors).'</div>';
			$tmpl = $errors.  $tmpl;
		}

		$out->addHTML( $tmpl );
	}
}
