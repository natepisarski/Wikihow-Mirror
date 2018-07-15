<?
require_once('commandLine.inc');

define('WH_USE_BACKUP_DB', true);
$dbr = wfGetDB(DB_SLAVE);
$dbr->selectDB(WH_DATABASE_NAME_SHARED);
$res = $dbr->select('user_properties', 'up_user', array('up_property' => 'defaulteditor', 'up_value' => 'advanced'), __FILE__);
$dbr->selectDB($wgDBname);
while ($row = $dbr->fetchObject($res)) {
	$u = User::newFromId($row->up_user);
	echo $row->up_user . "\n";
	$u->setOption('articlecreator', 0);
	$u->saveSettings();
}

