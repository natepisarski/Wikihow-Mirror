<?
require_once('commandLine.inc');

$db = DatabaseBase::factory('mysql');
$db->open($wgVanillaDB['host'], $wgVanillaDB['user'], $wgVanillaDB['password'], $wgVanillaDB['dbname']);
$oldignore = $db->ignoreErrors(true); 

$res = $db->select('GDN_UserAuthentication', array('UserID','ForeignUserKey')); 

$updates = array();
while ($row = $db->fetchObject($res)) {
	$u = User::newFromID($row->ForeignUserKey); 
	$url = Avatar::getAvatarURL($u->getName());
	$updates[$row->UserID] = $url;
}

foreach($updates as $userid=>$url) {
	echo "Updating {$userid} with avatar {$url}\n";
	$db->update("GDN_User", array("Photo"=>$url), array("UserID"=>$userid));
}


