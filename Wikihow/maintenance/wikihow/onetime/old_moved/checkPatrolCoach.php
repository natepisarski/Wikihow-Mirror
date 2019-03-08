<?
require_once('commandLine.inc');

$dbr = wfGetDB(DB_SLAVE);

$res = $dbr->select('rctest_users', array('ru_user_id', 'ru_base_patrol_count'), array('ru_next_test_patrol_count' => 2, 'ru_base_patrol_count > 0'));
while ($row = $dbr->fetchObject($res)) {
	$u = User::newFromId($row->ru_user_id);
	$u->load();
	$enabled = RCTest::isEnabled($u->getId()) ? "on" : "off";
	$base = $row->ru_base_patrol_count;
	$wgUser = $u;
	$total = $dbr->selectField('logging', 'count(*)', RCPatrolStandingsIndividual::getOpts());
	$adjusted = $total - $base;
	if ($adjusted > 4) {
		echo "User: " . $u->getName() . ", preference: $enabled, adjusted: $adjusted\n";
	}
}
