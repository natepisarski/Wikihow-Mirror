<?php
/* This page lets you post jobs to the python project - keywordtool
  * The jobs posted here are run by /data/keywrodtool/runjobs.py
 * The results can be collected from this page
 */

class Keywordtool extends UnlistedSpecialPage {

	private $errors = [];
	const JOBNAME = "Keywordtool";

	public function __construct() {
		$this->action = $GLOBALS[ 'wgTitle' ]->getPartialUrl();
		parent::__construct( $this->action );
		$GLOBALS[ 'wgHooks' ][ 'ShowSideBar' ][] = [ 'Keywordtool::removeSideBarCallback' ];
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
		$questionType = 0;
		$colHeader = [];

		$dbr = wfGetDB( DB_REPLICA );
		// get all data
		if ( isset( $postedValues[ 'alldata' ] ) ) {
			$allData = True;
		} elseif ( isset( $postedValues[ 'lastbatch' ] ) ) {
			// find the last batch id
			$row = $dbr->selectRow( 'keywordtool.kwtjobs', [ 'kwt_id' , 'kwt_other_cols', 'kwt_question' ], [ 'kwt_status=2' ], __METHOD__, [ 'ORDER BY' => 'kwt_id DESC' ] );
			if ( $row ) {
				$batchId = $row->kwt_id;
				$questionType = $row->kwt_question;
				$colHeader = explode( ',', str_replace( [ '(' , ')'  ], '' , $row->kwt_other_cols ) );
			}
		} elseif ( $postedValues[ 'batchid' ] != '' ) {
				$batchId = (int) $postedValues[ 'batchid' ];
				$conds= [ "kwt_id" => $batchId ];
				$row = $dbr->selectRow( 'keywordtool.kwtjobs', [ 'kwt_id' , 'kwt_other_cols', 'kwt_question' ], $conds, __METHOD__, [] );
				if (row) {
					$questionType = $row->kwt_question;
					$colHeader = explode( ',', str_replace( [ '(' , ')'  ], '' , $row->kwt_other_cols ) );
				}
		}
		if ($questionType == 3) {
			$this->getDedupResults($batchId,$colHeader);
		} else {
			$this->getResults( $batchId, $allData, $colHeader );
		}
	}

	//Used to fetch results from the dedup table
	private function getDedupResults( $batchId, $colHeader ) {

		$dbr = wfGetDB( DB_REPLICA );
		$conds= [ "kwt_batchid" => $batchId ];
		$colms = [ 'kwt_query', 'kwt_volume', 'kwt_query_dup',  'kwt_total_volume'];
		$opts = ['ORDER BY' => " kwt_query_dup ='', kwt_volume Desc " ];
		// get data
		$res = $dbr->select( 'keywordtool.kwtdedupresults', $colms, $conds, __METHOD__, $opts );
		$fileName = 'Keywordtoolapi_dedupresults';

		// turn off the html being displayed
		$this->getOutput()->setArticleBodyOnly( true );

		// file header
		header( "Content-Type: text/csv" );
		header( 'Content-Disposition: attachment; filename= "'.$fileName.date("_Y-m-d_H_i_s").'".csv' );

		$header = [ 'Query', 'Avg Volume', 'Duplicates', 'Total Volume'];

		$header = array_merge($header, $colHeader);
		$fileHandle = fopen( 'php://output' ,'w' );
		fputcsv( $fileHandle, $header, ',', '"' );
		if ( $res ) {
			foreach ( $res as $row ) {
				$restData = explode( ',', str_replace( [ '(' ,  ')' ], '', $row->kwt_rest ) );
				$data = array_merge( [ $row->kwt_query, $row->kwt_volume, $row->kwt_query_dup, $row->kwt_total_volume],
										$restData );
				fputcsv( $fileHandle, $data, ',', '"' );
			}
		}
		return;
	}

	//Used to fetch results from the db
	private function getResults( $batchId, $allData, $colHeader ) {

		$dbr = wfGetDB( DB_REPLICA );
		if ( !$allData ) {
			$conds= [ "kwt_batchid" => $batchId ];
		} else {
			$conds = [];
		}
		$colms = [ 'kwt_query', 'kwt_query_asked',  'kwt_mod_query', 'kwt_volume', 'kwt_cpc', 'kwt_cmp', 'kwt_slope',
						'kwt_monthly', 'kwt_rest' ];

		// get data
		$res = $dbr->select( 'keywordtool.kwtresults', $colms, $conds, __METHOD__, [] );
		$fileName = 'Keywordtoolapi_results';

		// turn off the html being displayed
		$this->getOutput()->setArticleBodyOnly( true );

		// file header
		header( "Content-Type: text/csv" );
		header( 'Content-Disposition: attachment; filename= "'.$fileName.date("_Y-m-d_H_i_s").'".csv' );

		$header = [ 'Query', 'Modified Query/Question', 'Modifier', 'Avg Volume', 'CPC', 'CMP', 'Slope',
						'M1', 'M2', 'M3', 'M4','M5', 'M6','M7', 'M8','M9', 'M10', 'M11', 'M12'];

		$heaader = array_merge($header, $colHeader);
		$fileHandle = fopen( 'php://output' ,'w' );
		fputcsv( $fileHandle, $header, ',', '"' );
		if ( $res ) {
			foreach ( $res as $row ) {

				$monthly = explode( ',', str_replace( [ '(' , ')'  ], '' , $row->kwt_monthly ) );
				$restData = explode( ',', str_replace( [ '(' ,  ')' ], '', $row->kwt_rest ) );
				$data = array_merge( [ $row->kwt_query, $row->kwt_query_asked, $row->kwt_mod_query, $row->kwt_volume,
										$row->kwt_cpc, $row->kwt_cmp, $row->kwt_slope],
										$monthly, $restData );
				fputcsv( $fileHandle, $data, ',', '"' );
			}
		}
	}

	// Displays existing job status at page load
	private function getJobStatus() {
		$dbr = wfGetDB( DB_REPLICA );
		$dbw = wfGetDB( DB_MASTER );
		$html = '';
		$conds = [ "kwt_newjobs" => 1 ] ;
        $conds[] = "kwt_jobname='".self::JOBNAME."'";

		$res = $dbr->select( 'keywordtool.kwtjobs', [ '*' ], $conds, __METHOD__, [] );
		foreach ( $res as $row ) {
			if ( $row->kwt_status == 0 ) {
				// not started the job yet
				$html .= '<p><b> Batch id - ' .$row->kwt_id .' ' .$row->kwt_jobname  .' not started </b><br /> '.$row->kwt_jobmessage . $row->kwt_error .'</p>';
			} elseif ( $row->kwt_status == 1 ) {
				// job started
				$html .= '<p><b>Batch id - '.$row->kwt_id .' ' .$row->kwt_jobname .' is in progress </b><br />'.$row->kwt_jobmessage . $row->kwt_error .'</p>';
			} elseif ( $row->kwt_status == 2 ) {
				// job completed or terminated due to error
				// show completed message and update the db that the job is done
				// update the job as been done.
				$res = $dbw->update('keywordtool.kwtjobs', [ 'kwt_newjobs' => '0' ],  [ 'kwt_id' => $row->kwt_id ], __METHOD__, [] );

				if ( empty ( $row->kwt_error ) ) {
					$html .= '<p><b>Batch id.'.$row->kwt_id .' ' .$row->kwt_jobname .' completed </b><br />'.$row->kwt_jobmessage.'</p>';
				} else {
					//there was an error and the job got terminated because of it
					$html .= '<p><b>'.$row->kwt_jobname.$row->kwt_jobmessage .' not completed because of errors: '. $row->kwt_error.'</b><br/></p>';
				}
			}
		}
		return $html;
	}

	// Post the job to the jobdb table
	private function processUpload( $uploadfile, $userName, $question ) {

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
        $fileSaveName = '/data/keywordtool/csv/keywordtool'.date("_Y-m-d_H_i_s").'.csv';

        if ( !move_uploaded_file( $uploadfile, $fileSaveName ) ) {
			$this->$errors[] = 'Could not save a local copy of the file, check write permission or path.';
			return 'bad file' ;
		}
		else{
			// file is uploaded, add params and add the job to the job table
			// add entry to the job table!
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert( 'keywordtool.kwtjobs',
							['kwt_status'=>'0',
							'kwt_filename'=>$fileSaveName,
							'kwt_lasttimestamp'=>date("YmdHis"),
							'kwt_newjobs'=>'1',
							'kwt_jobname'=> self::JOBNAME,
							'kwt_jobmessage'=>$jobMessage,
							'kwt_user'=>$userName,
							'kwt_question' =>$question]
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
			$qs = $req->getVal('questionbox') ;
			$question = $qs;
			$html = $this->processUpload( $req->getFileTempName( 'ctitlesfile' ), $userName, $question );
		} elseif ( $req->getVal( 'oldbatch' ) ) {
			$out->setArticleBodyOnly( true );
			$html = $this->processBatchRequest( $req->getValues() );
			$out->addHTML( $html );
			return;
		}

		$out->setHTMLTitle( 'Post jobs to keyword tool' );
		$out->setPageTitle( 'Keywordtool API Access' );

		$mustVars = [
						'action' => $this->action,
						'jobStatus' => $this->getJobStatus()
						];

		$options = [ 'loader' => new Mustache_Loader_FilesystemLoader( __DIR__ ) , ];
		$m = new Mustache_Engine( $options );
		$tmpl = $m->render( 'keyword_tool.mustache', $mustVars );

		if ( $html ) $tmpl .= $html;
		if ( !empty( $this->$errors ) ) {
			$errors = '<div class="errors">ERRORS:<br />'.implode('<br />', self::$errors).'</div>';
			$tmpl = $errors.  $tmpl;
		}

		$out->addHTML( $tmpl );
		$out->addModules( 'ext.wikihow.Keywordtool' );
	}
}
