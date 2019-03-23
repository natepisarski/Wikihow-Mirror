<?php

class Turker extends UnlistedSpecialPage {

	static $workers_id = array();
	static $status_names = array('Resolved','Issued', 'Reissued', 'Unresolved', 'All Reviewed');
	static $errors = array();
	static $jobName ="Upload HITs";

	function __construct() {
		$this->action = $GLOBALS['wgTitle']->getPartialUrl();
		parent::__construct($this->action);
		$GLOBALS['wgHooks']['ShowSideBar'][] = array('Turker::removeSideBarCallback');
	}

	static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	function processWorkerRequest($postedValues) {
		$workerId = $postedValues['workerid'];

		$fileName ='';
		$dbr = wfGetDB(DB_REPLICA);
		$res = null;
		if (isset($postedValues['allWorkers'])) {
			// get all worker data
			$fileName = "allWorkers";
			$conds = array();
			$res = $dbr->select('aqturkstore.worker', array('workerid','trustscore','wrongc','rightc','rewardtotal','tier','relaxscore','rightr','wrongr','warningmessage','timestamp','deleted'), $conds, __METHOD__, array());

		}else {
			$res = $dbr->select('aqturkstore.worker', array('workerid','trustscore','wrongc','rightc','rewardtotal','tier','relaxscore','rightr','wrongr','warningmessage','timestamp','deleted'), array('workerid'=>$workerId), __METHOD__, array());
			$fileName =$workerId;
		}

		//file header
		$fileHandle =fopen('php://output','w');
		header("Content-Type: text/csv");
		header('Content-Disposition: attachment; filename= WorkerData"'.$fileName .'".csv');
		$header = array('Workerid','Trustscore','Wrong Count','Right Count','Total HITs worked','Tier','Easy Trustscore','Right Count(2)','Wrong Count(2)','Warning Message Sent','Last Active','Deleted');
		fputcsv($fileHandle,$header,',','"');
		//if data found
		if ($res) {
			foreach ($res as $row) {
				fputcsv($fileHandle,array($row->workerid,$row->trustscore,$row->wrongc, $row->rightc,$row->rewardtotal,$row->tier,$row->relaxscore,$row->rightr,$row->wrongr,$row->warningmessage,date('Y-m-d H:i:s', strtotime($row->timestamp)),($row->deleted>0?'Yes':'No')),',','"');
			}
		}
		fclose($fileHandle);
		exit;
	}

	function SaveData($excludeTest,$includeDeleted,$onlyTest,$conds,$fileName) {

		$dbr = wfGetDB(DB_REPLICA);
		$res = null;

		if ($excludeTest) {
			$conds[] = "testhit=0";
			$fileName =$fileName. "_ExcludeTest";
		}
		if ($onlyTest) {
			$conds[] = "testhit=1";
			$fileName =$fileName. "_OnlyTest";
		}
		if (!$includeDeleted) {
			$conds[] = "deleted=0";
		}else {
			$fileName =$fileName. "_IncludeDeleted";
		}
		$colmnstoget = array('pageid','workerid','creationtime','hitgroupid','hittypeid','reward','hitreviewed','algoscore','algorating','turkrating','turksatisfying','turktext','hitid',
								'revision','submittime','accepttime','testhit','listid','query','pagetitle','url','reason','turkstatus','deleted','batchnum');
		$res = $dbr->select('aqturkstore.hits', $colmnstoget, $conds, __METHOD__, array());


		//file header
		$fileHandle =fopen('php://output','w');
		header("Content-Type: text/csv");
		header('Content-Disposition: attachment; filename= "'.$fileName.date("_Y-m-d_H_i_s").'".csv');
		if ($includeDeleted) {
			$header = array('Article Id','Worker Id','Create Time','HIT Group Id','HIT Type Id','Reward Amount','HIT Reviewed','Algo Score','Algo Rating','Turk Rating','Turk Satisfying','Text','HIT Id',
									'Revision','Submit time','Accept time','Test HIT','List Id','Query','Page Title','URL','Reason','Turk Status','Deleted','Batch Number');
		}else {
			$header = array('Article Id','Worker Id','Create Time','HIT Group Id','HIT Type Id','Reward Amount','HIT Reviewed','Algo Score','Algo Rating','Turk Rating','Turk Satisfying','Text','HIT Id',
									'Revision','Submit time','Accept time','Test HIT','List Id','Query','Page Title','URL','Reason','Turk Status','Batch Numer');
		}
		fputcsv($fileHandle,$header,',','"');
		//if data found
		if ($res) {
			foreach ($res as $row) {
				if ($includeDeleted) {
					$data = array($row->pageid,$row->workerid, date('Y-m-d H:i:s', strtotime($row->creationtime)),$row->hitgroupid,$row->hittypeid,$row->reward,($row->hitreviewed>0?'Yes':'No'),$row->algoscore,$row->algorating,
									$row->turkrating,$row->turksatisfying, $row->turktext,$row->hitid,$row->revision,
									strtotime($row->submittime)?date('Y-m-d H:i:s', strtotime($row->submittime)):'',
									strtotime($row->accepttime)?date('Y-m-d H:i:s', strtotime($row->accepttime)):'',
									($row->testhit>0?'Yes':'No'),$row->listid,$row->query,$row->pagetitle,$row->url,$row->reason,$row->turkstatus,($row->deleted>0?'Yes':'No'),$row->batchnum);
					fputcsv($fileHandle,$data,',','"');
				}else {
					$data = array($row->pageid,$row->workerid, date('Y-m-d H:i:s', strtotime($row->creationtime)),$row->hitgroupid,$row->hittypeid,$row->reward,($row->hitreviewed>0?'Yes':'No'),$row->algoscore,$row->algorating,
									$row->turkrating,$row->turksatisfying, $row->turktext,$row->hitid,$row->revision,
									strtotime($row->submittime)?date('Y-m-d H:i:s', strtotime($row->submittime)):'',
									strtotime($row->accepttime)?date('Y-m-d H:i:s', strtotime($row->accepttime)):'',
									($row->testhit>0?'Yes':'No'),$row->listid,$row->query,$row->pagetitle,$row->url,$row->reason,$row->turkstatus,$row->batchnum);
					fputcsv($fileHandle,$data,',','"');
				}
			}
		}
		fclose($fileHandle);
		exit;
	}

	function ProcessKeywordQuery($posted_values) {

		$excludeTest = False;
		$includeDeleted = False;
		$onlyTest =False;
		//all data! check for the other two
		if (isset($posted_values['excludeTest'])) {
			$excludeTest = True;
		}
		if (isset($posted_values['includeDeleted'])) {
			$includeDeleted = True;
		}
		if (isset($posted_values['onlyTest'])) {
			$onlyTest = True;
		}

		if (isset($posted_values['allData'])) {

			// output file with all the data
			$fileName ='AllData';
			$conds =array();
			$this->SaveData($excludeTest,$includeDeleted,$onlyTest,$conds,$fileName);
			return;

		}else {
			//output file with specific data based on input

			$fileName ='TurkQueryData';
			$conds = array();
			$condSet = false;

			if ($posted_values['fromDate'] !='') {
				//end date?
				if ($posted_values['toDate'] !='') {
					$conds[] = "submittime >'".$posted_values['fromDate']."'";
					$conds[] = "submittime <'".$posted_values['ToDate']."'";
					$fileName =$fileName. "_between_dates_";
					$condSet = true;
				}else {
					self::$errors = "Please specify the end date for query";
					return;
				}
			}
			if ($posted_values['listid'] !='') {
				$conds[] = "listid ='".$posted_values['listid']."'";
				$fileName =$fileName. "_list_".$posted_values['listid'];
				$condSet = true;
			}
			if ($posted_values['pageid'] !='') {
				$conds[] = "pageid =".$posted_values['pageid'];
				$fileName =$fileName. "_pageid_".$posted_values['pageid'];
				$condSet = true;
			}
			if ($posted_values['batchnum'] !='') {
				$conds[] = "batchnum ='".$posted_values['batchnum']."'";
				$fileName =$fileName. "_batchnum_".$posted_values['batchnum'];
				$condSet = true;
			}
			if ($posted_values['reason'] !='') {
				$conds[] = "reason ='".$posted_values['reason']."'";
				$fileName =$fileName. "_reason_".$posted_values['reason'];
				$condSet = true;
			}
			if ($posted_values['hitid'] !='') {
				$conds[] = "hitid ='".$posted_values['hitid']."'";
				$fileName =$fileName. "_hitid_".$posted_values['hitid'];
				$condSet = true;
			}
			if ($posted_values['hitStatus'] !='All') {
					if ($posted_values['hitStatus']=='All Reviewed') {
						$conds[] = "hitreviewed =1";
						$fileName =$fileName."_hitstatus_All_reviewed";
					} else {
						$conds[] = "turkstatus ='".strtolower($posted_values['hitStatus'])."'";
						$fileName =$fileName."_hitstatus_".$posted_values['hitStatus'];
					}
				$condSet = true;
			}
			if ($posted_values['workerid'] !='') {
				$conds[] = "workerid ='".$posted_values['workerid']."'";
				$fileName =$fileName. "_worker_".$posted_values['workerid'];
				$condSet = true;
			}
			if ($condSet) {
				//save data
				$this->SaveData($excludeTest,$includeDeleted,$onlyTest,$conds,$fileName);
			}else{
				self:$errors[] = "No Conditions specified to select HITs";
				return ;
			}
		}
	}

	function ProcessUpload($uploadfile,$radio,$userName) {

		//Check file for format and save it to the local dir
		if (!file_exists($uploadfile) || !is_readable($uploadfile)) {
			self::$errors[] = 'Could not find file. File not uploaded.';
			return 'Bad File' ;
		}

		//compatibility with mac generated csv files
		ini_set('auto_detect_line_endings', true);

		// python does this too, but lets check here anyways
		// check for the number of columns
		// and if the rating is in the right format
		//remove from python
		$row =1;
		if ( ($fileHandle = fopen($uploadfile,'r')) !== FALSE) {
			while ($data = fgetcsv($fileHandle)) {
				if ($row==1){
					$row++;
					continue;
				}
				$rating = (int)$data[7];
				if (!((1<=$rating)&&($rating<=5))) {
					self::$errors[] = "Rating " .$rating."not in the 1-5 range at row ".$row."<br/>\n";
					return 'Bad File';
				}
				$row++;
			}
			//empty file?
			if ($row ==1) {
				self::$errors[] = "File had no contents, check file again";
				return 'Bad File';
			}
			fclose($fileHandle);
		}
		ini_set('auto_detect_line_endings',FALSE);

		$jobMessage = "No of HITs in the file " .($row-2);
		// copy file the local location, where python can find it
		$fileSaveName = '/data/autoturk/'.'Turk_Upload_HITs'.date("_Y-m-d_H_i_s").'.csv';
		if (!move_uploaded_file($uploadfile,$fileSaveName)) {
			self::$errors[] = 'Could not save a local copy of the file, check write permission or path.';
			return 'bad file' ;
		}
		else{

			//file is uploaded, add params and add the job to the job table.
			$params =" -i " .$fileSaveName  ;
			if ($radio !='a') {
				$params .= ' -'.$radio;
			}
			//add entry to the job table, and thats it!
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('aqturkstore.turkjobs', array('tj_status'=>'0','tj_filename'=>$params,'tj_lasttimestamp'=>date("YmdHis"),'tj_newjobs'=>'1','tj_jobname'=> self::$jobName,'tj_jobmessage'=>$jobMessage,'tj_user'=>$userName), __METHOD__,array());
		}
		return 'Upload job added';
	}

	private function makeStatusPullDown() {
		$html = '<select name="hitStatus" id="hitStatus">';

		$html .= '<option value="All">All</option>';

		foreach (self::$status_names as $value) {
			$html .= "<option value='$value'>$value</option>";
		}

		$html .='</select>';

		return $html;
	}

	private function getJobStatus() {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);
		$html = '';
		$conds = array();
		$conds[]="tj_newjobs=1";

		$res = $dbr->select('aqturkstore.turkjobs', array('*'), $conds, __METHOD__,array());
		$rowcount=1;
		foreach ($res as $row) {
			//only show the jobs for this page
			if ($row->tj_jobname==self::$jobName) {
				//echo $row->tj_jobname;
				if ($row->tj_status==0) {
					//not started the job yet
					$html .= '<p><b> Job No.'.$rowcount .') ' . $row->tj_jobname  .' not started </b><br /> '.$row->tj_jobmessage . $row->tj_error .'</p>';
				}elseif ($row->tj_status==1) {
					//job started
					$html .= '<p><b>Job No.'.$rowcount .') '.$row->tj_jobname .' is in progress </b><br />'.$row->tj_jobmessage . $row->tj_error .'</p>';
				}
				elseif ($row->tj_status==2) {
					//job completed or terminated due to error
					///show completed message and update the db that the job is done
					//update the job as been done.
					$res = $dbw->update('aqturkstore.turkjobs', array('tj_newjobs'=> '0'),  array('tj_id'=>$row->tj_id), __METHOD__, array());

					if (empty($row->tj_error)) {
						$html .= '<p><b>Job No.'.$rowcount .') ' .$row->tj_jobname .' completed </b><br />'.$row->tj_jobmessage.'</p>';
					}else {
						//there was an error and the job got terminated because of it
						$html .= '<p><b>'.$row->tj_jobname.$row->tj_jobmessage .' not completed because of errors: '. $row->tj_error.'</b><br/></p>';
					}
				}
			$rowcount +=1;
			}
		}
		return $html;
	}


	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;


		$userName = $wgUser->getName();
		// Check permissions
		$userGroups = $wgUser->getGroups();
		if ($userName!='Rjsbhatia') {
			if ($wgUser->isBlocked() || !(in_array('staff', $userGroups))) {
				$wgOut->setRobotPolicy('noindex,nofollow');
				$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				return;
			}
		}
		// do not need this for testing
		//if ($_SERVER['HTTP_HOST'] != WH_TITUS_HOSTNAME) {
		//	$wgOut->redirect('https://'.WH_TITUS_HOSTNAME.'/Special:MMKManager');
		//}

		//some of these take a little while
		set_time_limit(0);

		if ($wgRequest->getVal('keyword')) {
			$this->ProcessKeywordQuery($wgRequest->getValues());
		}
		elseif ($wgRequest->getVal('worker')) {
			$this->processWorkerRequest($wgRequest->getValues());
		}
		elseif ($wgRequest->getVal('upload')) {
			$radio = $wgRequest->getVal('settype');
			if (empty($radio)) {
				self::$errors[] = 'Data set type not selected<br/>';
			}
			else {
				//save file	locally and run python to upload
				$html = $this->ProcessUpload($wgRequest->getFileTempName('hitsFile'),$radio,$userName);
			}

		}


		$wgOut->setHTMLTitle('Mechanical Turk Data Download Page - wikiHow');
		$wgOut->setPageTitle('Mechanical Turk Data Download');

		$tmpl = $this->getGuts();

		if ($html) $tmpl .= $html;

		if (!empty(self::$errors)) {
			$errors = '<div class="errors">ERRORS:<br />'.implode('<br />',self::$errors).'</div>';
			$tmpl = $errors.  $tmpl;
		}

		$wgOut->addHTML($tmpl);
	}

	function getGuts() {
		$action = $this->action;
		$statuses = $this->makeStatusPullDown();
		$jobStatus = $this->getJobStatus();

		return <<<EOHTML
		<script src='/extensions/min/?f=extensions/wikihow/common/download.jQuery.js,extensions/wikihow/mobile/webtoolkit.aim.min.js'></script>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

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
		<form action="/Special:$action?keyword=1" method="post">
		<div class=bx>
			<p>
				<span class=sm>HITs Data</span>
			</p>
			<div class=bx>
				<div class=bx>
					<p>
						<span class=sm>All Data</span>
						<input type='checkbox' value='' name='allData' id='allData' />
					</p>
				</div>
				<span class=sm>OR</span>
				<div class=bx>
					<p>
						<span class=sm>Dates</span><br/>
						<label for ='fromDate'> From</label>
						<input type='text' value='' name='fromDate' id='fromDate' />
						<label for ='toDate'> To</label>
						<input type='text' value='' name='toDate' id='toDate' />
					</p>
					<p>
						<span class=sm>List Id</span>
						<input type='text' value='' name='listid' id='listid' />
					</p>
					<p>
						<span class=sm>Article Id</span>
						<input type='text' value='' name='pageid' id='pageid' />
					</p>
					<p>
						<span class=sm>Batch Number</span>
						<input type='text' value='' name='batchnum' id='batchnum' />
					</p>
					<p>
						<span class=sm>Reason</span>
						<input type='text' value='' name='reason' id='reason' />
					</p>
					<p>
						<span class=sm>HIT Id</span>
						<input type='text' value='' name='hitid' id='hitid' />
					</p>
					<p>
						<span class=sm>HIT Status</span>
						$statuses
					</p>
					<p>
						<span class=sm>Worker</span>
						<input type='text' value='' name='workerid' id='workerid' />
					</p>
				</div>
			</div>
			<span class=sm>AND</span>
			<div class =bx>
				<p>
					<span class=sm>Exclude Test HITs</span>
					<input type='checkbox' name ='excludeTest' id ='excludeTest'/>
				</p>
				<p>
					<span class=sm>Only Test HITs</span>
					<input type='checkbox' name ='onlyTest' id ='onlyTest'/>
				</p>
				<p>
					<span class=sm>Include Deleted</span>
					<input type='checkbox' name ='includeDeleted' id ='includeDeleted'/>
				</p>
			</div>
			<p><button type="submit" id="keyword-submit">HITs Data</button></p>
		</div>
		</form>

		<form action="/Special:$action?worker=1" method="post">
		<div class=bx>
			<p>
				<span class=sm>Worker Data</span>
			</p>
			<p>
				<span class=sm>All Workers</span>
				<input type='checkbox' name ='allWorkers' id ='allWorkers'/>
			</p>

			<p>
				<span class=sm>Worker</span>
				<input type='text' value='' name='workerid' id='workerid' />
			</p>

			<p><button type="submit" id="keyword-submit">Worker Data</button></p>
		</div>
		</form>


		<form id="turk-hits-upload-form" action="/Special:$action?upload=1" method="post" enctype="multipart/form-data">
		<div class='bx'>
			<p>
				<span class='sm'>Upload HITs file</span>
			</p>
			</p>
				<span>Choose data set type</span>
			</p>
			<input type ="radio" Name ='settype' value ='a'/><span>Advanced Set</span>
			<input type ="radio" Name ='settype' value ='t'/><span>Training Set</span>
			<input type ="radio" Name ='settype' value ='q'/><span>Qualifying Set</span><br/><br/>

			<input type="file" id="hitsFile" name="hitsFile" /><br/>
			<div id="hits-result">
				<ul>
					<li>The input file needs to have 8 columns in the following order: <i>article id, list id, query, url, url name, reason, algorithm score and the expected rating</i>.</li>
					<li>The file needs to be in a csv format.</li>
				</ul>
			</div>
		</div>
		</form>

		<div class=bx>
			<p class=sm>Recent Job Status</p>
			<div class="job_status">$jobStatus</div>
		</div>

		 <script>
		 $(function()
		 {
			$( "#fromDate" ).datepicker({
				  defaultDate: "+1w",
				  changeMonth: true,
				  changeYear: true,
				  onClose: function( selectedDate ) {
					$( "#toDate" ).datepicker( "option", "minDate", selectedDate );
				  }
				});
				$( "#toDate" ).datepicker({
				  defaultDate: "+1w",
				  changeMonth: true,
				  changeYear: true,
				  onClose: function( selectedDate ) {
					$( "#fromDate" ).datepicker( "option", "maxDate", selectedDate );
				  }
				});
			});
			$('#hitsFile').change(function () {
				var filename = $('#hitsFile').val();
				if (!filename) {
					alert('No file selected!');
				} else {
					$('#hits-result').html('uploading file...');
					$('#turk-hits-upload-form').submit();
				}
				return false;
			});
		</script>
EOHTML;
	}

}
