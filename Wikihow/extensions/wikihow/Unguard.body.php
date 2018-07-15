<?php

class Unguard extends UnlistedSpecialPage {

    function __construct() {
        parent::__construct( 'Unguard' );
    }
	
	function padVar ($name) {
		global $wgRequest;
		$val = $wgRequest->getVal($name);
		if ($val && strlen($val) < 2)
			$val = "0" . $val;
		return $val;
	}

    function execute ($par) {
		global $wgOut, $wgRequest, $wgUser;

		if ( !in_array( 'staff', $wgUser->getGroups() ) ) {
         	$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
         	return;
		}
	
		$wgOut->addHTML("
		<form action='/Special:Unguard' method='POST'>
			Username: <input type='text' name='username' value='{$wgRequest->getVal('username', '')}'/> <br/><br/>
			Start date: Year: " . date("Y") . " Month: <input type='text' name='month_1' size='2' value='" . date("m") . "'/>
					Day: <input type='text' name='day_1' size='2' value='" . date("d") . "'>
					Hour (GMT): <input type='text' name='hour_1' size='2' value='00'> <br/><br/>
			End date (optional): Year: " . date("Y") . " <input type='text' name='month_2' size='2'>
					Day: <input type='text' name='day_2' size='2'>
					Hour (GMT): <input type='text' name='hour_2' size='2'> <br/><br/>
			<input type='submit'/>
		</form>	");

		if ($wgRequest->wasPosted()) {
			$user   = $wgRequest->getVal('username');
    	
			$start  = date("Y") . $this->padVar('month_1') . $this->padVar('day_1') . $this->padVar('hour_1') . "0000";
			$end =  $wgRequest->getVal('month_2') ? 
						date("Y") . $this->padVar('month_1') . $this->padVar('day_2') . $this->padVar('hour_2') . "0000" : null;
		
		    $cutoff = wfTimestamp(TS_MW, $start);
		    $cutoff2 = null;
		    if (!$end) {
		        $wgOut->addHTML("reverting changes by $user since {$cutoff}<br/>");
		    } else {
		        $cutoff2 = wfTimestamp(TS_MW, $end);
		        $wgOut->addHTML("reverting changes by $user between {$cutoff} and {$cutoff2} <br/>");
		    }

		    $user = User::newFromName($user);

			if ($user->getID() == 0) {
				$wgOut->addHTML("<b>Whoa! There is no user with this name {$wgRequest->getVal('username', '')}, bailing.</b>");
				return;
			}

		    $dbw = wfGetDB(DB_MASTER);
			$options = array('qcv_user'=>$user->getID(),"qc_timestamp > '{$cutoff}'");
			if ($cutoff2)
				$options[] = "qc_timestamp < '{$cutoff2}'";
			$res = $dbw->select('qc_vote', array('qcv_qcid','qcv_vote'), $options);
			
		    while ($row = $dbw->fetchObject($res)) {
				//yes or no?
				($row->qcv_vote == 1) ?	$theVote = 'yes' : $theVote = 'no';
				
				//grab the QC row
				$res_qc = $dbw->select('qc', array('qc_id','qc_patrolled','qc_'.$theVote.'_votes AS votes','qc_'.$theVote.'_votes_req AS votes_req'), array('qc_id'=>$row->qcv_qcid));
				$row_qc = $dbw->fetchObject($res_qc);
				
				//do we need to mark it as unpatrolled?
				if (($row_qc->qc_patrolled == 1) && ($row_qc->votes >= $row_qc->votes_req)) {
					$dbw->update('qc', array('qc_patrolled = 0'), array('qc_id'=>$row->qcv_qcid));
				}
				
				//subtract vote
				if ($row_qc->votes > 0) {
					$dbw->update('qc', array('qc_'.$theVote.'_votes = '.($row_qc->votes - 1)),array('qc_id'=>$row->qcv_qcid));
				}
					
				//remove it from qc_vote
				$dbw->delete('qc_vote', array('qcv_qcid'=>$row->qcv_qcid,'qcv_user'=>$user->getID()));
				
			}
		}
		return;
	}
}
