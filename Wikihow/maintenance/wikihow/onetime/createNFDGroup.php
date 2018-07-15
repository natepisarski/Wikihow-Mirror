<?

require_once( "../../commandLine.inc" );

$dbw = wfGetDB(DB_MASTER);

$res = $dbw->select("user_groups", "*", array('ug_group' => 'newarticlepatrol'), __FILE__);

$count = 0;
foreach($res as $row) {
	$dbw->insert("user_groups", array('ug_user' => $row->ug_user, 'ug_group' => 'nfd'), __FILE__);
	$count++;
}

echo "Added {$count} users to the new nfd group\n";