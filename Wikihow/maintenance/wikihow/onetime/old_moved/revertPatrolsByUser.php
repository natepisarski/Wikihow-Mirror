<?
	include_once('commandLine.inc');

	$user 	= $argv[0];
	$start	= $argv[1];
	$end	= $argv[2];

	if (!$user || !$start) {
		echo "usage: php revert_patrols_by_user.php user start-time [end-time]\n";
		echo "e.g.:  Xxxx 20100407160000\n";
		return;
	}

	$cutoff = wfTimestamp(TS_MW, $start);
	$cutoff2 = null;
	if (!$end) {
		echo "reverting changes by $user since {$cutoff}\n";	
	} else {
		$cutoff2 = wfTimestamp(TS_MW, $end);
	}

	$user = User::newFromName($user);

	$dbw = wfGetDB(DB_MASTER);
	$options = array('log_user'=>$user->getID(), 'log_type'=>'patrol', "log_timestamp > '{$cutoff}'");
	if ($cutoff2)
		$options[] = "log_timetamp < '{$cutoff2}'";

	$res = $dbw->select('logging', array('log_title', 'log_params'), $options);


	$oldids = array();
	while ($row = $dbw->fetchObject($res)) {
		#echo "{$row->log_title}\t". str_replace("\n", " ", $row->log_params) . "\n";
		$oldids[]= preg_replace("@\n.*@", "", $row->log_params);
	}

	$count = $dbw->query("UPDATE recentchanges set rc_patrolled=0 where rc_this_oldid IN (" . implode(", ", $oldids) . ");");
	echo "Unpatrolled " . sizeof($oldids) . " patrols by {$user->getName()}\n";
