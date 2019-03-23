<?php

class AQRater extends UnlistedSpecialPage {

	static $workers_id = array();
	static $errors = array();
	static $jobName = "Rate Articles";

	public function __construct() {
		$this->action = $GLOBALS['wgTitle']->getPartialUrl();
		parent::__construct($this->action);
		$GLOBALS['wgHooks']['ShowSideBar'][] = array('AQRater::removeSideBarCallback');
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	function processBatchRequest($posted_values) {
		$allData = false;
		$lastBatch = false;
		$batchid = 0;

		// get all data
		if (isset($posted_values['alldata'])) {
			$allData = True;
		} elseif (isset($posted_values['lastbatch'])) {
			// find the last batch id
			$dbr = wfGetDB(DB_REPLICA);
			$row = $dbr->selectRow('aqrater.aqjobs',
				array('aq_id'),
				array('aq_status=2'),
				__METHOD__,
				array('ORDER BY' => 'aq_id DESC'));
			if ($row) {
				$batchid=$row->aq_id;
				self::$errors[]=$batchid;
			}
		} elseif ($posted_values['batchid'] !='') {
			$batchid = $posted_values['batchid'];
		}

		$this->getResults($batchid,$allData);
	}

	private function getResults($batchid,$allData) {

		$dbr = wfGetDB(DB_REPLICA);
		if (!$allData) {
			$conds = array("aq_dataset" => $batchid);
		}else{
			$conds = array();
		}
		$colms = array('aq_pageid','aq_revisionid', 'aq_title', 'aq_dataset','aq_score','aq_srating');

		// get data
		$res = $dbr->select('aqrater.pageratings',
			$colms,
			$conds,
			__METHOD__,
			array());
		$fileName = 'Article_ratings';

		// turn off the html being displayed
		$this->getOutput()->setArticleBodyOnly(true);

		// file header
		header("Content-Type: text/csv");
		header('Content-Disposition: attachment; filename= "'.$fileName.date("_Y-m-d_H_i_s").'".csv');
		$header = array( 'Pageid','Revisionid', 'Title','Batch Number', 'Score','Rating');

		$fileHandle = fopen('php://output','w');
		fputcsv($fileHandle,$header,',','"');
		if ($res) {
			foreach ($res as $row) {
				$data = array($row->aq_pageid,$row->aq_revisionid, $row->aq_title, $row->aq_dataset, $row->aq_score,$row->aq_srating);
				fputcsv($fileHandle,$data,',','"');
			}
		}
		return;
	}

	private function getJobStatus() {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);
		$html = '';
		$conds = array();
		$conds[] = "aq_newjobs=1";

		$res = $dbr->select('aqrater.aqjobs', array('*'), $conds, __METHOD__,array());
		foreach ($res as $row) {
			// only show the jobs for this page
			if ($row->aq_jobname==self::$jobName) {
				echo $row->aq_jobname;
				if ($row->aq_status==0) {
					// not started the job yet
					$html .= '<p><b> Batch id - ' .$row->aq_id .' ' .$row->aq_jobname  .' not started </b><br /> '.$row->aq_jobmessage . $row->aq_error .'</p>';
				} elseif ($row->aq_status==1) {
					// job started
					$html .= '<p><b>Batch id - '.$row->aq_id .' ' .$row->aq_jobname .' is in progress </b><br />'.$row->aq_jobmessage . $row->aq_error .'</p>';
				} elseif ($row->aq_status==2) {
					// job completed or terminated due to error
					// show completed message and update the db that the job is done
					// update the job as been done.
					// create the download file
					$res = $dbw->update('aqrater.aqjobs', array('aq_newjobs'=> '0'),  array('aq_id'=>$row->aq_id), __METHOD__, array());

					if (empty($row->aq_error)) {
						$html .= '<p><b>Batch id.'.$row->aq_id .' ' .$row->aq_jobname .' completed </b><br />'.$row->aq_jobmessage.'</p>';
					} else {
						//there was an error and the job got terminated because of it
						$html .= '<p><b>'.$row->aq_jobname.$row->aq_jobmessage .' not completed because of errors: '. $row->aq_error.'</b><br/></p>';
					}
				}
			}
		}
		return $html;
	}

	function processUpload($uploadfile,$userName) {

		// Check file for format and save it to the local dir
		if (!file_exists($uploadfile) || !is_readable($uploadfile)) {
			self::$errors[] = 'Could not find file. File not uploaded.';
			return 'Bad File' ;
		}
		// Compatibility with mac generated csv files
		ini_set('auto_detect_line_endings', true);

		// python does this too, but lets check here anyways
		// check for the number of columns
		// and if the rating is in the right format
		// remove from python
		$row =1;
		if ( ($fileHandle = fopen($uploadfile,'r')) !== false) {
			while ($data = fgetcsv($fileHandle)) {
				if ($row == 1){
					$row++;
					continue;
				}
				$row++;
			}
			// empty file?
			if ($row ==1) {
				self::$errors[] = "File had no contents, check file again";
				return 'Bad File';
			}
			fclose($fileHandle);
		}
		ini_set('auto_detect_line_endings',false);

		$jobMessage = "No of Articles in the file " .($row-2);
		// copy file the local location, where python can find it
		$fileSaveName = '/data/aqrater/rate_articles'.date("_Y-m-d_H_i_s").'.csv';

		if (!move_uploaded_file($uploadfile,$fileSaveName)) {
			self::$errors[] = 'Could not save a local copy of the file, check write permission or path.';
			return 'bad file' ;
		} else {
			// file is uploaded, add params and add the job to the job table
			// add entry to the job table!
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('aqrater.aqjobs',
				array('aq_status'=>'0','aq_filename'=>$fileSaveName,'aq_lasttimestamp'=>date("YmdHis"),'aq_newjobs'=>'1','aq_jobname'=> self::$jobName,'aq_jobmessage'=>$jobMessage,'aq_user'=>$userName),
				__METHOD__,
				array());
		}
		return 'Upload job added';
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userName=$user->getName();

		// Check permissions
		$userGroups = $user->getGroups();
		if ($userName!='Rjsbhatia') {
			if ($user->isBlocked() || !(in_array('staff', $userGroups))) {
				$out->setRobotPolicy('noindex,nofollow');
				$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				return;
			}
		}

		if ($req->getVal('upload')) {
			$html = $this->processUpload($req->getFileTempName('aqratefile'),$userName);
		} elseif ($req->getVal('oldbatch')) {
			$out->setArticleBodyOnly(true);
			$html = $this->processBatchRequest($req->getValues());
			$out->addHTML($html);
			return;
		}
		$out->setHTMLTitle('Rate Articles - wikiHow');
		$out->setPageTitle('Rate Articles');

		$tmpl = $this->getGuts();
		if ($html) $tmpl .= $html;
		if (!empty(self::$errors)) {
			$errors = '<div class="errors">ERRORS:<br />'.implode('<br />',self::$errors).'</div>';
			$tmpl = $errors.  $tmpl;
		}

		$out->addHTML($tmpl);
	}

	function getGuts() {
		$action = $this->action;
		$jobStatus = $this->getJobStatus();

		return <<<EOHTML
		<script src='/extensions/min/?f=extensions/wikihow/common/download.jQuery.js,extensions/wikihow/mobile/webtoolkit.aim.min.js'></script>
		<style>
			.sm { font-variant:small-caps; letter-spacing:2px; margin-right: 25px; }
			.bx { padding: 5px 10px 5px 10px; margin-bottom: 15px; border: 1px solid #dddddd; border-radius: 10px 10px 10px 10px; }
			.bx p { padding: .5em 0; }
			#auto_mark_status { margin-left: 1.5em; }
			#admin-result ul { margin-top: 0; font-size: 10px; }
			#admin-result li { margin: 3px 0; }
			.recent_log { font-size: .8em; }
			.errors { color: #C00; }
		</style>


		<form id="aqrate-upload-form" action="/Special:$action?upload=1" method="post" enctype="multipart/form-data">
		<div class='bx'>
			<p>
				<span class='sm'>Upload CSV file to rate articles</span>
			</p>
			<input type="file" id="aqratefile" name="aqratefile" /><br/>
			<div id="aq-result">
				<ul>
					<li>The input file needs to have 3 columns in the following order: <i>page id, revision id, title</i>.</li>
					<li>The file needs to be in a csv format.</li>
				</ul>
			</div>
		</div>
		</form>

		<form action="/Special:$action?oldbatch=1" method="post">
		<div class=bx>
			<p>
				<span class=sm>Archived Batches (Choose one)</span>
				<p>
					<span class=sm>All data</span>
					<input type='checkbox' value='' name='alldata' id='alldata' />
				</p>
				<p>
					<span class=sm>Last completed batch</span>
					<input type='checkbox' value='' name='lastbatch' id='lastbatch' />
				</p>
			</p>
			<p>
				<span class=sm>Batch Number</span>
				<input type='text' value='' name='batchid' id='batchid' />
			</p>
			<p><button type="submit" id="keyword-submit">Get Articles</button></p>
		</div>
		</form>

		<div class=bx>
			<p class=sm>Recent Job Status</p>
			<div class="job_status">$jobStatus</div>
		</div>

		<script>
			$('#aqratefile').change(function () {
				var filename = $('#aqratefile').val();
				if (!filename) {
					alert('No file selected!');
				} else {
					$('#aq-result').html('uploading file...');
					$('#aqrate-upload-form').submit();
				}
				return false;
			});
		</script>
EOHTML;
	}

}
