<?php

class Unguard extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'Unguard' );
	}

	private function padVar($name) {
		$req = $this->getRequest();
		$val = $req->getVal($name);
		if ($val && strlen($val) < 2)
			$val = "0" . $val;
		return $val;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		if ( !in_array( 'staff', $this->getUser()->getGroups() ) ) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$userName = $req->getVal('username', '');
		$out->addHTML("
		<form action='/Special:Unguard' method='POST'>
			Username: <input type='text' name='username' value='$userName'/> <br/><br/>
			Start date: Year: " . date("Y") . " Month: <input type='text' name='month_1' size='2' value='" . date("m") . "'/>
					Day: <input type='text' name='day_1' size='2' value='" . date("d") . "'>
					Hour (GMT): <input type='text' name='hour_1' size='2' value='00'> <br/><br/>
			End date (optional): Year: " . date("Y") . " <input type='text' name='month_2' size='2'>
					Day: <input type='text' name='day_2' size='2'>
					Hour (GMT): <input type='text' name='hour_2' size='2'> <br/><br/>
			<input type='submit'/>
		</form>	");

		if ($req->wasPosted()) {

			$start  = date("Y") . $this->padVar('month_1') . $this->padVar('day_1') . $this->padVar('hour_1') . "0000";
			$end =  $req->getVal('month_2') ?
						date("Y") . $this->padVar('month_1') . $this->padVar('day_2') . $this->padVar('hour_2') . "0000" : null;

			$cutoff = wfTimestamp(TS_MW, $start);
			$cutoff2 = null;
			if (!$end) {
				$out->addHTML("reverting changes by $userName since {$cutoff}<br/>");
			} else {
				$cutoff2 = wfTimestamp(TS_MW, $end);
				$out->addHTML("reverting changes by $userName between {$cutoff} and {$cutoff2} <br/>");
			}

			$userObj = User::newFromName($userName);

			if ($userObj->getID() == 0) {
				$out->addHTML("<b>Whoa! There is no user with this name $userName, bailing.</b>");
				return;
			}

			$dbw = wfGetDB(DB_MASTER);
			$options = array('qcv_user' => $userObj->getID(), "qc_timestamp > {$dbw->addQuotes($cutoff)}");
			if ($cutoff2) {
				$options[] = "qc_timestamp < {$dbw->addQuotes($cutoff2)}'";
			}
			$res = $dbw->select('qc_vote', array('qcv_qcid','qcv_vote'), $options, __METHOD__);

			foreach ($res as $row) {
				// yes or no?
				$theVote = ($row->qcv_vote == 1 ? 'yes' : 'no');

				// grab the QC row
				$row_qc = $dbw->selectRow('qc',
					array('qc_id',
						'qc_patrolled',
						'qc_'.$theVote.'_votes AS votes',
						'qc_'.$theVote.'_votes_req AS votes_req'),
					array('qc_id' => $row->qcv_qcid),
					__METHOD__);

				// do we need to mark it as unpatrolled?
				if ($row_qc->qc_patrolled == 1 && $row_qc->votes >= $row_qc->votes_req) {
					$dbw->update('qc',
						array('qc_patrolled = 0'),
						array('qc_id' => $row->qcv_qcid),
						__METHOD__);
				}

				// subtract vote
				if ($row_qc->votes > 0) {
					$dbw->update('qc',
						array('qc_'.$theVote.'_votes ' => $row_qc->votes - 1),
						array('qc_id' => $row->qcv_qcid),
						__METHOD__);
				}

				// remove it from qc_vote
				$dbw->delete('qc_vote',
					array('qcv_qcid' => $row->qcv_qcid,
						'qcv_user' => $userObj->getID()),
					__METHOD__);
			}
		}
	}
}
