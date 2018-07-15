<?php
/**
 * Used to very occasionally undo un-patrol actions en masse.
 *
 * It's useful to keep around, but can be dangerous and should always
 * be reviewed and tested before running it on live data.
 */

	require_once( '../commandLine.inc' );

print "This is a dangerous script that shouldn't be run without care. Exiting.";
exit;

	print( "Re-patrolling...\n" );

	$user = User::newFromName('Ttrimm');
	$start = '03/09/2012';
	$cutoff = wfTimestamp(TS_MW, $start);
	$cutoff2 = null;
	
	$final_count = doTheUnUnpatrol($user, $cutoff,$cutoff2);

	print( "Done.  ". $final_count." patrolls fixed up.\n\n");
	
	function doTheUnUnpatrol($user,$cutoff,$cutoff2) {
		global $wgLang;
		
		$dbw = wfGetDB(DB_MASTER);
		$options = array('log_user'=>$user->getID(), 'log_type'=>'patrol', "log_timestamp > '{$cutoff}'", 'log_deleted' => 1);
		if ($cutoff2)
			$options[] = "log_timestamp < '{$cutoff2}'";
	
		$res = $dbw->select('logging', array('log_title', 'log_params'), $options);
	
		$oldids = array();
		while ($row = $dbw->fetchObject($res)) {
			#echo "{$row->log_title}\t". str_replace("\n", " ", $row->log_params) . "\n";
			$oldid = preg_replace("@\n.*@", "", $row->log_params);
			if ($oldid) {
				$oldids[] = intval($oldid);
			}
		}
		
		$count = sizeof($oldids);
		$page_size = 1000;
		$pages = ceil($count / $page_size);
		$affected = 0;
		if ($count > 0) {
			//set the patrols in recentchanges as patrolled again
			for ($page = 0; $page < $pages; $page++) {
				$id_page = array_slice($oldids, $page * $page_size, $page_size);
				$sql = "UPDATE recentchanges set rc_patrolled=1 where rc_this_oldid IN (" . implode(", ", $oldids) . ")";
				$res = $dbw->query($sql, __METHOD__);
				$affected += $dbw->affectedRows();
				print "$sql\n";
			}
			
			if ($res) {
				//undelete logs
				//$res = $dbw->update('logging', array('log_deleted' => 0), $options);
			}
			print "affected: $affected\n";
		}
		
		return $count;
	}
