<?php

global $IP;
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

session_start();

class EditTurk extends UnlistedSpecialPage {

	static $workers_id = array();
	static $status_names = array('Resolved','Issued', 'Reissued', 'Unresolved', 'All Reviewed');
	static $status_turk_delete = array('NA', 'Last Uploaded');
	static $errors = array();
	static $jobName = "Delete HITs";
	static $jobNameBatch="Delete HITs Batch";
	static $jobNameReason ="Delete HITs Reason";
	static $jobNameStatus="Delete HITs Status";

	function __construct() {
		$this->action = $GLOBALS['wgTitle']->getPartialUrl();
		parent::__construct($this->action);
		$GLOBALS['wgHooks']['ShowSideBar'][] = array('EditTurk::removeSideBarCallback');
	}

	static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	private function processWorkerRequest($postedValues) {
		$workerId = $postedValues['workerid'];

		$fileName ='';
		$dbw = wfGetDB(DB_MASTER);
		$res = null;
		if (isset($postedValues['allWorkers'])) {
			// mark all workers as deleted
			$res = $dbw->update('aqturkstore.worker', array('deleted'=> '1'), array(), __METHOD__, array());
			if (isset($postedValues['workerHits'])) {
				$res = $dbw->update('aqturkstore.hits', array('deleted'=> '1'), array(), __METHOD__, array());
			}
		}else {
			// mark worker as deleted
			if (!empty($workerId)) {
				$res = $dbw->update('aqturkstore.worker', array('deleted'=>'1'), array('workerid'=>$workerId), __METHOD__, array());
				if (isset($postedValues['workerHits'])) {
					$res = $dbw->update('aqturkstore.hits', array('deleted'=> '1'),  array('workerid'=>$workerId), __METHOD__, array());
				}
			}

		}
		$affected = $dbw->affectedRows();
		if ($affected==0) {
			self::$errors[] = " Check the delete condition, no rows were affected for worker id $workerId";
		}
		return "$affected rows deleted ";
	}

	private function ProcessHITDBDelBatch($fileName) {

		//compatibility with mac generated csv files
		ini_set('auto_detect_line_endings', true);

		#no header?
		$hitidArray= array();
		if ( ($fileHandle = fopen($fileName,'r')) !== FALSE) {
			while ($data = fgetcsv($fileHandle)) {
				$hitidArray[]= $data[0];
			}
			fclose($fileHandle);
		}

		ini_set('auto_detect_line_endings',FALSE);

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->update('aqturkstore.hits', array('deleted'=>'1'), array('hitid'=>$hitidArray), __METHOD__, array());

		$affected = $dbw->affectedRows();
		if ($affected==0) {
			self::$errors[] = " Check the delete condition, no rows were affected for: $deleteConditions";
		}

		return "$affected rows deleted ";

	}
	private function ProcessKeywordQuery($posted_values) {

		$deleteConditions = "";
		$conds = array();
		if ($posted_values['listid'] !='') {
			$conds[] = "listid ='".$posted_values['listid']."'";
			$deleteConditions .= "List id ".$posted_values['listid'];
		}
		if ($posted_values['pageid'] !='') {
			$conds[] = "pageid =".$posted_values['pageid'];
			$deleteConditions .= " Page Id ".$posted_values['pageid'];
		}
		if ($posted_values['reason'] !='') {
			$conds[] = "reason ='".$posted_values['reason']."'";
			$deleteConditions .= " Reason ".$posted_values['reason'];
		}
		if ($posted_values['hitid'] !='') {
			$conds[] = "hitid ='".$posted_values['hitid']."'";
			$deleteConditions .= " HITid ".$posted_values['hitid'];
		}
		if ($posted_values['hitStatus'] !='NA') {
			$conds[] = "turkstatus ='".$posted_values['hitStatus']."'";
			$deleteConditions .= " HIT status ".$posted_values['hitStatus'];
		}
		if ($posted_values['workerid'] !='') {
			$conds[] = "workerid ='".$posted_values['workerid']."'";
			$deleteConditions .= " Worker Id ".$posted_values['workerid'];
		}

		if ($conds) {
			$dbw = wfGetDB(DB_MASTER);
			$res = $dbw->update('aqturkstore.hits', array('deleted'=> '1'),  $conds, __METHOD__, array());
		}
		$affected = $dbw->affectedRows();
		if ($affected==0) {
			self::$errors[] = " Check the delete condition, no rows were affected for: $deleteConditions";
		}

		return "$affected rows deleted ";

	}
	private function makeStatusPullDown() {
		$html = '<select name="hitStatus" id="hitStatus">';

		$html .= '<option value="NA">NA</option>';

		foreach (self::$status_names as $key => $match) {
			$html .= "<option value='$match'>$match</option>";
		}

		$html .='</select>';

		return $html;
	}

	private function makeTurkStatusPullDown() {
		$html = '<select name="hitTurkDeleteStatus" id="hitTurkDeleteStatus">';

		foreach (self::$status_turk_delete as $key => $match) {
			$html .= "<option value='$match'>$match</option>";
		}

		$html .='</select>';

		return $html;
	}

	private function DeleteBatchHits($fileName,$userName) {

		$fileSaveName = '/data/autoturk/'.'Turk_delete_HITs'.date("_Y-m-d_H_i_s").'.csv';
		if (!move_uploaded_file($fileName,$fileSaveName)) {
			self::$errors[] = 'Could not save a local copy of the file, check write permission or path.';
			return 'bad file' ;
		}
		else{
			//add entry to the job table, and thats it!
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('aqturkstore.turkjobs', array('tj_status'=>'0','tj_filename'=>$fileSaveName,'tj_lasttimestamp'=>date("YmdHis"),'tj_newjobs'=>'1','tj_jobname'=> self::$jobNameBatch,'tj_user'=>$userName ), __METHOD__,array());
		}
	}

	private function DeleteHITs($posted_values,$userName) {

		$deleteConditions = "";
		$conds = array();

		$dbw = wfGetDB(DB_MASTER);

		if ($posted_values['reason'] !='') {
			$dbw->insert('aqturkstore.turkjobs', array('tj_status'=>'0','tj_filename'=>$posted_values['reason'],'tj_lasttimestamp'=>date("YmdHis"),'tj_newjobs'=>'1','tj_jobname'=> self::$jobNameReason,'tj_user'=>$userName), __METHOD__,array());
		}
		if ($posted_values['hitTurkDeleteStatus'] !='NA') {
			$dbw->insert('aqturkstore.turkjobs', array('tj_status'=>'0','tj_filename'=>$posted_values['hitTurkDeleteStatus'],'tj_lasttimestamp'=>date("YmdHis"),'tj_newjobs'=>'1','tj_jobname'=> self::$jobNameStatus,'tj_user'=>$userName), __METHOD__,array());
		}
	}

   private function getJobStatus() {
		$dbr = wfGetDB(DB_REPLICA);
		$html = '';
		$conds = array();
		$conds[]="tj_newjobs=1";

		$res = $dbr->select('aqturkstore.turkjobs', array('*'), $conds, __METHOD__,array());
		$rowcount=1;
		foreach ($res as $row) {
			#only show jobs related to this page.
			if (strpos($row->tj_jobname,self::$jobName)!==False) {
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
					$dbw = wfGetDB(DB_MASTER);
					if (empty($row->tj_error)) {
						$html .= '<p><b>Job No.'.$rowcount .') ' .$row->tj_jobname .' completed </b><br />'.$row->tj_jobmessage.'</p>';
					}else {
						//there was an error and the job got terminated because of it
						$html .= '<p><b>'.$row->tj_jobname.$row->tj_jobmessage .' not completed because of errors: '. $row->tj_error.'</b><br/></p>';
					}
					//mark the job as completed
					$res = $dbw->update('aqturkstore.turkjobs', array('tj_newjobs'=> '0'),  array('tj_id'=>$row->tj_id), __METHOD__, array());
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

		$userName =$wgUser->getName();
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

		if ($wgRequest->getVal('dbdelete')) {
			//check if a file was uploaded
			$fileName = $wgRequest->getFileTempName('hitsdbFile');
			if (!file_exists($fileName) || !is_uploaded_file($fileName)) {
				//not a file task
				$this->ProcessKeywordQuery($wgRequest->getValues());
			}else{
				//a batch hits file was uploaded
				$this->ProcessHITDBDelBatch($fileName);
			}
		}
		elseif ($wgRequest->getVal('worker')) {
			$this->processWorkerRequest($wgRequest->getValues());
		}
		elseif ($wgRequest->getVal('amzturk')) {
			//do this?
			$fileName = $wgRequest->getFileTempName('hitsFile');
			if (!file_exists($fileName) || !is_uploaded_file($fileName)) {
				$this->DeleteHITs($wgRequest->getValues(),$userName);
			}else{
				//a batch file was uploaded
				$this->DeleteBatchHits($fileName,$userName);
			}
		}

		$wgOut->setHTMLTitle('Mechanical Turk Editor - wikiHow');
		$wgOut->setPageTitle('Mechanical Turk Editor');

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
		$turkdelete = $this->makeTurkStatusPullDown();
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
		<form id='hits-delete-db' action="/Special:$action?dbdelete=1" method="post" enctype="multipart/form-data">
		<div class=bx>
			<p>
				<span class=sm>Delete HITs Data</span>
			</p>
			<div class=bx>
				<p>
					<span class=sm>List Id</span>
					<input type='text' value='' name='listid' id='listid' />
				</p>
				<p>
					<span class=sm>Article Id</span>
					<input type='text' value='' name='pageid' id='pageid' />
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
			<div class=bx>
				<p>
					<label for ="hitsdbFile">Batch HIT Ids</label>
					<input type="file" id="hitsdbFile" name="hitsdbFile" /><br/>
					<ul><li>The file should be in csv format, with only one column containing HIT ids and no headers</li></ul>
				</p>
			</div>
			<p><button type="submit" id="hits-db-submit">Delete HITs</button></p>
		</div>
		</form>

		<form action="/Special:$action?worker=1" method="post">
		<div class=bx>
			<p>
				<span class=sm>Delete Worker Data</span>
			</p>
			<p>
				<span class=sm>All Workers</span>
				<input type='checkbox' name ='allWorkers' id ='allWorkers'/>
			</p>
			<p>
				<span class=sm>Worker</span>
				<input type='text' value='' name='workerid' id='workerid' />
				<input type ="checkbox" name ="workerHits" id ="workerHits/>
				<label for ="workerHits">Delete HITs</label>
			</p>

			<p><button type="submit" id="keyword-submit">Delete Worker</button></p>
		</div>
		</form>

		<form id='turk-hits-delete' action="/Special:$action?amzturk=1" method="post" enctype="multipart/form-data">
		<div class=bx>
			<p>
				<span class=sm>Delete HITs from Turk</span>
			</p>
			<div class=bx>
				<p>
					<span class=sm>Reason</span>
					<input type='text' value='' name='reason' id='reason' />
				</p>
				<p>
					<span class=sm>HIT Status</span>
					$turkdelete
				</p>
		</div>
			<div class=bx>
				<p>
					<label for ="hitsFile">Batch HIT Ids</label>
					<input type="file" id="hitsFile" name="hitsFile" /><br/>
					<ul><li>The file should be in csv format, with only one column containing HIT ids and no headers</li></ul>
				</p>
			</div>
			<p>
				<button type="submit" id="turk-hits-submit">Delete HITs</button>
			</p>
		</form>

		<form id='double-delete' action="/Special:$action?doubledelete=1" method="post" enctype="multipart/form-data">
		<div class=bx>
			<p>
				<span class=sm>delete test Delete HITs from Turk</span>
			</p>
			<div class=bx>
				<p>
					<span class=sm>Reason1</span>
					<input type='text' value='' name='reason1' id='reason1' />
					<span class=sm>Reason2</span>
					<input type='text' value='' name='reason2' id='reason2' />
				</p>
		</div>
		<p>
			<button type="submit" id="doubledelete-submit">Delete HITs</button>
		</p>
		</form>


		<div class=bx>
			<p class=sm>Recent Job Status</p>
			<div class="job_status">$jobStatus</div>
		</div>


		 <script>
			$('#hitsdbFile').change(function () {
				var filename = $('#hitsdbFile').val();
				if (!filename) {
					alert('No file selected!');
				} else {
					$('#hits-db-submit').html('uploading file...');
					$('#hits-delete-db').submit();
				}
				return false;
			});
			$('#hitsFile').change(function () {
				var filename = $('#hitsFile').val();
				if (!filename) {
					alert('No file selected!');
				} else {
					$('#turk-hits-submit').html('uploading file...');
					$('#turk-hits-delete').submit();
				}
				return false;
			});

			(function($){
				$(document).ready(function() {
					$('#doubledelete-submit')
						.prop('disabled', false)
						.click(function() {
							$.post('/Special:$action',
								{ 'reason1': $('#reason1').val(),
								   'doubleDelete': True },
								'json');
							return false;
						});
				});
			})(jQuery);


		</script>

EOHTML;
	}
}
