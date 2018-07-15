<?

function wfGetRCPatrols($rcid, $hi, $low, $curid) {
	$dbr = &wfGetDB(DB_SLAVE);
	$res = $dbr->select( 'recentchanges',
		array('rc_id'),
		array('rc_id <= ' . $hi,
			  'rc_id >= ' . $low,
			  'rc_cur_id = ' . $curid,
			  'rc_patrolled <> 1'),
		__METHOD__
	);
	$result = array();
	foreach ($res as $row) {
		$result[] = $row->rc_id;
	}
	return $result;
}

