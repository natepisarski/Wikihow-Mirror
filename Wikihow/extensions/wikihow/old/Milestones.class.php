<?php

class Milestones {
	
	var $editMilestones = array(2, 5, 10, 30, 50, 100);
	var $emailSeparation = 50000; //minimum number of days between emails (for now we only want them to get it once)
	var $tableName = "milestones";
	var $userField = "ms_user";
	var $tablePrefix = "ms";
	var $emailField = "ms_lastemail";
	var $emailCTAs = 8;
	var $minRegistration = "20121021000000";
	
	function Milestones() {
		global $wgExtensionMessagesFiles;
		
		$wgExtensionMessagesFiles['Milestones'] = dirname(__FILE__) . '/Milestones.i18n.php';
	}
	
	function updateEditMilestones($datestamp) {
		
		$dbr = wfGetDB(DB_SLAVE);
		
		echo "Updating the editing milestones for " . $datestamp . "\n";
		
		$timestamp = $datestamp . "000000";
		$dateUnix = wfTimestamp(TS_UNIX, $timestamp);
		$tomorrowUnix = strtotime("+1 day", $dateUnix);
		$tomorrowTimestamp = wfTimestamp(TS_MW, $tomorrowUnix);
		$tomorrowDatestamp = substr($tomorrowTimestamp, 0, 8);
		
		echo "starting " . $timestamp . " " . $tomorrowTimestamp . "\n";
		
		$res = $dbr->select('recentchanges', array('rc_user', 'rc_comment'), array('rc_namespace' => 0, "rc_timestamp >= '{$timestamp}'", "rc_timestamp < '{$tomorrowTimestamp}'", 'rc_user != 0'), __METHOD__);
		$users = array();
		$usersArray = array();
		foreach($res as $object) {
			$userArray[] = $object;
		}
		
		
		foreach($userArray as $user) {
			if($users[$user->rc_user] == null) {
				if(stripos($user->rc_comment, "Reverted edits by") !== false)
					continue;
				$users[$user->rc_user] = $user;
			}
		}
		
		echo "Found " . count($users) . " users who edited\n";
		
		foreach($users as $user) {
			Milestones::updateUserStat('edit', $user, $datestamp);
			$userObj = User::newFromId($user->rc_user);
		}
		
	}
	
	function updateUserStat($activity, $userInfo, $datestamp) {
		if($activity == "")
			return;
		
		$dbr = wfGetDB(DB_SLAVE);
		$dbw = wfGetDB(DB_MASTER);
		
		$timestamp = $datestamp . "000000";
		$dateUnix = wfTimestamp(TS_UNIX, $timestamp);
		$yesterdayUnix = strtotime("-1 day", $dateUnix);
		$yesterdayTimestamp = wfTimestamp(TS_MW, $yesterdayUnix);
		$yesterdayDatestamp = substr($yesterdayTimestamp, 0, 8);
		
		$dateField = "ms_{$activity}stamp";
		$countField = "ms_{$activity}count";
		
		$row = $dbr->selectRow($this->tableName, '*', array($this->userField => $userInfo->rc_user), __METHOD__);
		
		if(!$row) {
			//this user doesn't exist in the table yet, so add a row
			$dbw->insert($this->tableName, array($this->userField => $userInfo->rc_user, $countField => 1, $dateField => $datestamp));
			return;
		}
		
		if($row->{$dateField} == $datestamp) {
			//we've obviously already processed today, so don't do anything
			return;
		}
		elseif($row->{$dateField} == $yesterdayDatestamp) {
			//did activity yesterday, so increment the count
			$dbw->update($this->tableName, array($countField => $row->{$countField} + 1, $dateField => $datestamp), array($this->userField => $userInfo->rc_user));
		}
		else {
			//activity didn't happen yesterday, so start back at 1
			$dbw->update($this->tableName, array($countField => 1, $dateField => $datestamp), array($this->userField => $userInfo->rc_user));
		}
		
	}
	
	/***
	 * 
	 */
	function getMilestonesComplete($datestamp) {
		
		$dbr = wfGetDB(DB_SLAVE);
		
		$res = $dbr->select('milestones', array('*'), array('ms_editstamp' => $datestamp), __METHOD__, array('GROUP BY' => 'ms_user'));
		
		$users = array();
		foreach($res as $user) {
			$users[] = $user;
		}
		
		$todayUnix = wfTimestamp(TS_UNIX, $datestamp + "000000");
		$minUnix = strtotime("-" . $this->emailSeparation . " days", $todayUnix);
		$minDate = substr(wfTimestamp(TS_MW, $minUnix), 0, 8);
		
		//now check to see if these are all valid
		foreach($users as $index => $user) {
			if(!in_array($user->ms_editcount, $this->editMilestones)) {
				unset($users[$index]);
				continue;
			}
			if($user->ms_lastemail > $minDate && $user->ms_editcount == $this->editMilestones[0]) {
				unset($users[$index]);
				continue;
			}
		}
		
		return $users;
	}
	
	function sendMilestoneEmails($datestamp) {
		
		$dbw = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);
		
		$users = $this->getMilestonesComplete($datestamp);
		
		$emailCount = 0;
		foreach($users as $userInfo) {
			$user = User::newFromId($userInfo->{$this->userField});
			
			$email = $user->getEmail();
			
			if($email == "")
				continue;
			
			$registration = $user->getRegistration();
			//need to check for false for the users who have no registration date
			//like Krystle and JackHerrick
			if( $registration == NULL || $registration <= $this->minRegistration) {
				echo "No email sent to " . $user->getName() . ". Not a new registration\n";
				wfDebug("No email sent to " . $user->getName() . ". Not a new registration\n");
				continue;
			}
			if($user->getOption('disablemarketingemail') == '1') {
				wfDebug("No email sent to " . $user->getName() . ". Marketing email disabled.\n");
				continue;
			}
			
			$todayUnix = wfTimestamp(TS_UNIX, $datestamp + "000000");
			$minUnix = strtotime("-30 days", $todayUnix);
			$minDate = wfTimestamp(TS_MW, $minUnix);
			
			$views = 0;
			$articles = 0;
			$res = $dbr->select(
				array('revision','pageview'),
				array('pv_30day'),
				array('rev_page=pv_page', 'rev_user' => $user->getID(), "rev_timestamp > {$minDate}"),
				__METHOD__,
				array('GROUP BY' => 'rev_page')
				);
			
			foreach ($res as $object) {
				$views += intval($object->pv_30day);
				$articles++;
			}
		
			
			$from_name = wfMessage('milestone_from')->text();
			$subject = wfMessage('milestone_subject')->text();
			$message = wfMessage('milestone_message_' . $userInfo->ms_editcount)->text();
			if ($views > 20) {
				$viewership = wfMessage('milestone_viewership', number_format($articles), number_format($views));
			} else {
				$viewership = "";
			}
			$cta = AuthorEmailNotification::getCTA('email_roll', 'email');
			$body = wfMessage('milestone_body', $user->getName(), $message, $viewership , $cta)->text();
			
			wfDebug($user->getEmail() . " " . $subject . " " . $body . "\n");
			$emailCount++;
			
			AuthorEmailNotification::notify($user, $from_name, $subject, $body, "", true);
			
			$dbw->update($this->tableName, array($this->emailField => $datestamp), array($this->userField => $userInfo->{$this->userField}));
		}
		echo $emailCount . " milestone emails were sent\n";
	}
	
}

/**
CREATE TABLE `milestones` (
  `ms_user` mediumint(8) unsigned NOT NULL,
  `ms_editstamp` varchar(14) default 0,
  `ms_editcount` int(8) default 0,
  `ms_lastemail` varchar(14) default 0,
  PRIMARY KEY  (`ms_user`),
  KEY `ms_editcount` (`ms_editcount`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
*/
