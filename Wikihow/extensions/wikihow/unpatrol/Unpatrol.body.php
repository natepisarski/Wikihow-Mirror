<?php

class UnpatrolTips extends UnlistedSpecialPage {
	function __construct() {
		parent::__construct( 'UnpatrolTips' );
	}

	function padVar($name) {
		$val = $this->getRequest()->getVal($name);
		if ($val && strlen($val) < 2)
			$val = "0" . $val;
		return $val;
	}

	function execute($par) {
		if ( !in_array( 'staff', $this->getUser()->getGroups() ) ) {
			$this->getOutput()->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$target = $this->getRequest()->getVal("target");
		if ($target) {
			if (is_numeric($target)) {
				$target = User::whoIs(intval($target));
			} else {
				$target = User::newFromName($target);
			}
		}

		$month_1 = $this->padVar("month_1") ?: date("m");
		$day_1 = $this->padVar("day_1") ?: date("d");
		$hour_1 = $this->padVar("hour_1") ?: "00";

		$month_2 = $this->padVar("month_2") ?: "";
		$day_2 = $this->padVar("day_2") ?: "";
		$hour_2 = $this->padVar("hour_2") ?: "00";

		$this->getOutput()->addHTML("
			<form action='/Special:UnpatrolTips' method='POST'>
				Username: <input type='text' name='target' value='$target'/> <br/><br/>
				Start date: Year: " . date("Y") . " Month: <input type='text' name='month_1' size='2' value='" . $month_1 . "'/>
						Day: <input type='text' name='day_1' size='2' value='" . $day_1 . "'>
						Hour (GMT): <input type='text' name='hour_1' size='2' value='" . $hour_1 . "'> <br/><br/>
				End date (optional): Year: " . date("Y") . " <input type='text' name='month_2' size='2' value='" . $month_2 . "'>
						Day: <input type='text' name='day_2' size='2' value='".$day_2."'>
						Hour (GMT): <input type='text' name='hour_2' size='2' value='".$hour_2."'> <br/><br/>
				<input type='submit' name='count' value='Count'/>
				<input type='submit' name='view' value='View'/>
				<input type='submit' name='update' value='Submit'/>
			</form>	");
		if ($this->getRequest()->wasPosted()) {
			$user = $target;
			if (!$user || $user->getID() == 0) {
				$this->getOutput()->addHTML("Invalid user.  {$this->getRequest()->getVal('username', '')}");
				return;
			}

			$start  = date("Y") . $month_1 . $day_1 . $hour_1 . "0000";
			$cutoff = wfTimestamp(TS_MW, $start);
			$cutoffDisplay = new MWTimestamp($start);
			$humanCutoff = $cutoffDisplay->getHumanTimestamp();

			$end = null;
			$cutoff2 = null;
			if ($month_2) {
				$end  = date("Y") . $month_2 . $day_2 . $hour_2 . "0000";
				$cutoffDisplay2 = new MWTimestamp($end);
				$humanCutoff2 = $cutoff2->getHumanTimestamp();
				$cutoff2 = wfTimestamp(TS_MW, $end);
			}


			if ($this->getRequest()->getVal("count")) {
				if (!$end) {
					$this->getOutput()->addHTML("changes by $user since {$humanCutoff}<br/>");
				} else {
					$this->getOutput()->addHTML("changes by $user between {$humanCutoff} and {$humanCutoff} <br/>");
				}
				$results = $this->revertTips($user,$cutoff,$cutoff2,false, false);

				$this->getOutput()->addHTML("<br/>");
				if ($results > 0) {
					$this->getOutput()->addHTML("there are " . $results . " tips sent to QG by {$user->getName()} for this date range<br/>");
				}
				else {
					$this->getOutput()->addHTML("There were no tips patrolled to show for this time frame.<br/>");
				}

			} elseif ($this->getRequest()->getVal("view")) {
				if (!$end) {
					$this->getOutput()->addHTML("showing changes by $user since {$humanCutoff}<br/>");
				} else {
					$this->getOutput()->addHTML("showing changes by $user between {$humanCutoff} and {$humanCutoff} <br/>");
				}
				$results = $this->revertTips($user, $cutoff, $cutoff2, false, true);

				$this->getOutput()->addHTML("<br/>");
				if ($results > 0) {
					$this->getOutput()->addHTML("showing " . $results . " tips sent to QG by {$user->getName()} for this date range</br>");
				}
				else {
					$this->getOutput()->addHTML("There were no tips patrolled to show for this time frame.<br/>");
				}
			} else {
				$this->getOutput()->addHTML("<br/>");
				if (!$end) {
					$this->getOutput()->addHTML("reverting changes by $user since {$humanCutoff}<br/>");
				} else {
					$this->getOutput()->addHTML("reverting changes by $user between {$humanCutoff} and {$humanCutoff} <br/>");
				}

				$unpatrolled = $this->revertTips($user, $cutoff, $cutoff2, true);

				if ($unpatrolled > 0) {
					$this->getOutput()->addHTML("Undid " . $unpatrolled . " tips patrolled by {$user->getName()}\n");
				}
				else {
					$this->getOutput()->addHTML("There were no tips unpatrolled.<br/>");
				}
			}
		}

		return;
	}

	function revertTips($user, $cutoff, $cutoff2, $revert, $print = true) {
		$dbw = wfGetDB(DB_MASTER);
		$options = array('tw_user' => $user->getID(), "tw_timestamp > " . $dbw->addQuotes($cutoff));
		if ($cutoff2) {
			$options[] = "tw_timestamp < '{$cutoff2}'";
		}

		$res = $dbw->select('tipsandwarnings_log', array('tw_id', 'tw_qc_id'), $options, __METHOD__);

		$tipIds = array();
		foreach ($res as $row) {
			if ($row->tw_qc_id) {
				$tipIds[$row->tw_id] = $row->tw_qc_id;
			}
		}

		Hooks::run('UnpatrolTips', array(&$tipIds));

		$count = sizeof($tipIds);
		if ($print) {
			$this->getOutput()->addHTML("<br/>");
			foreach ($tipIds as $tipId => $qcId) {
				$this->getOutput()->addHTML("<p>tip with tip_id $tipId and qc_id $qcId <br/></p>");
			}
		}

		if ($revert) {
			$count = 0;
			foreach ($tipIds as $tipId => $qcId) {
				$success = TipsPatrol::undoTip($tipId, $qcId, $user->getId());
				if ($success) {
					$this->getOutput()->addHTML("undid tip $tipId <br/>");
					$count++;
				} else {
					$this->getOutput()->addHTML("could not undo tip $tipId <br/>");
				}
			}

			//log the change
			if ($count > 0) {
				$logTitle = Title::newFromText('Special:UnpatrolTips');
				$logPage = new LogPage('undotips', false);
				$logData = array();
				$logMessage = "[[User:" . $user->getName() . "]]: $count tip patrols undone";
				$logPage->addEntry('unpatrol', $logTitle, $logMessage, $logData);
			}
		}
		return $count;
	}

}

class Unpatrol extends UnlistedSpecialPage {
	function __construct() {
		parent::__construct( 'Unpatrol' );
	}

	function padVar($name) {
		$val = $this->getRequest()->getVal($name);
		if ($val && strlen($val) < 2)
			$val = "0" . $val;
		return $val;
	}

	function execute($par) {
		if ( !in_array( 'staff', $this->getUser()->getGroups() ) ) {
			$this->getOutput()->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->getOutput()->addHTML("
				<form action='/Special:Unpatrol' method='POST'>
					Username: <input type='text' name='username' value='{$this->getRequest()->getVal('username', '')}'/> <br/><br/>
					Start date: Year: " . date("Y") . " Month: <input type='text' name='month_1' size='2' value='" . date("m") . "'/>
							Day: <input type='text' name='day_1' size='2' value='" . date("d") . "'>
							Hour (GMT): <input type='text' name='hour_1' size='2' value='00'> <br/><br/>
					End date (optional): Year: " . date("Y") . " <input type='text' name='month_2' size='2'>
							Day: <input type='text' name='day_2' size='2'>
							Hour (GMT): <input type='text' name='hour_2' size='2' value='00'> <br/><br/>
					<input type='submit'/>
				</form>	");
		if ($this->getRequest()->wasPosted()) {
			$user = $this->getRequest()->getVal('username');

			$start = date("Y") . $this->padVar('month_1') . $this->padVar('day_1') . $this->padVar('hour_1') . "0000";
			$end = $this->getRequest()->getVal('month_2') ?
				date("Y") . $this->padVar('month_2') . $this->padVar('day_2') . $this->padVar('hour_2') . "0000" : null;

			$cutoff = wfTimestamp(TS_MW, $start);
			$cutoff2 = null;
			if (!$end) {
				$this->getOutput()->addHTML("reverting changes by $user since {$cutoff}<br/>");
			} else {
				$cutoff2 = wfTimestamp(TS_MW, $end);
				$this->getOutput()->addHTML("reverting changes by $user between {$cutoff} and {$cutoff2} <br/>");
			}

			$user = User::newFromName($user);

			if ($user->getID() == 0) {
				$this->getOutput()->addHTML("<b>WHoa! There is no user with this name {$this->getRequest()->getVal('username', '')}, bailing.</b>");
				return;
			}

			$unpatrolled = $this->doTheUnpatrol($user,$cutoff,$cutoff2,false);

			if ($unpatrolled > 0) {
				$this->getOutput()->addHTML("Unpatrolled " . $unpatrolled . " patrols by {$user->getName()}\n");
			}
			else {
				$this->getOutput()->addHTML("There were no patrolled edits to undo for this time frame.<br/>");
			}
		}
		return;
	}

	//does the unpatrolling
	// - returns the count of unpatrolled articles
	// *** MAKE SURE TO ADD AN UNPATROL LIMIT ***
	public static function doTheUnpatrol($user, $cutoff, $cutoff2, $unpatrol_limit) {
		global $wgLang;

		// max number of possible unpatrols
		if (!empty($unpatrol_limit)) {
			$limit = array('LIMIT' => $unpatrol_limit);
		}
		else {
			$limit = array();
		}

		$dbw = wfGetDB(DB_MASTER);
		$options = array('log_user'=>$user->getID(), 'log_type'=>'patrol', "log_timestamp > " . $dbw->addQuotes($cutoff), 'log_deleted' => 0);
		if ($cutoff2)
			$options[] = "log_timestamp < " . $dbw->addQuotes($cutoff2);

		$res = $dbw->select('logging',
				array('log_title', 'log_params'),
				$options,
				__METHOD__,
				$limit);

		$oldids = array();
		foreach ($res as $row) {
			// A time long, long ago in a galaxy far, far away, log_params
			// column wasn't json enocded. So we detect whether or not it is,
			// and use the correct result. -Reuben 12/19/2013, MWUP
			$decoded = unserialize($row->log_params);
			if ($decoded !== false) {
				$oldids[] = isset($decoded['curid']) ? $decoded['curid'] : $decoded['4::curid']; // or should this be previd??
			} else {
				$oldids[] = preg_replace("@\n.*@", "", $row->log_params);
			}
		}

		$count = sizeof($oldids);
		if ($count > 0) {
			// set the patrols in recentchanges as not patrolled
			$sql = "UPDATE recentchanges set rc_patrolled=0 where rc_this_oldid IN (" . $dbw->makeList($oldids) . ")";
			if (!empty($unpatrol_limit)) $sql .= " LIMIT " . $unpatrol_limit;
			$res = $dbw->query($sql,__METHOD__);

			Hooks::run('Unpatrol', array(&$oldids));

			if ($res) {
				// set logs to deleted
				// Reuben 1/22/2014: No more deleting of logs, per community managers and bug #49,
				// because how this works in MWUP has changed
				//$res = $dbw->update('logging', array('log_deleted' => 1), $options, __METHOD__, $limit);

				// remove from QG
				$del_res = $dbw->delete("qc", array("qc_rev_id" => $dbw->makeList($oldids), "qc_user" => $user->getID()), __METHOD__);

				// log the change
				$title = Title::newFromText('Special:Unpatrol');
				$log = new LogPage( 'unpatrol', false );
				$msg = wfMessage("unpatrol_log")->rawParams($count, "[[User:" . $user->getName() . "]]", $wgLang->date($cutoff), $cutoff2==null?$wgLang->date(wfTimestampNow()):$wgLang->date($cutoff2))->escaped();
				$log->addEntry('unpatrol', $title, $msg);
			}
		}

		return $count;
	}
}
