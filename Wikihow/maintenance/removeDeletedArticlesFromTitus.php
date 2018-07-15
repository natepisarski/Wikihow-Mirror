<?
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

$dbw = DatabaseBase::factory('mysql');
$dbw->open(TitusDB::getDBHost(), WH_DATABASE_MAINTENANCE_USER, WH_DATABASE_MAINTENANCE_PASSWORD, TitusDB::getDBName());

$rows = DatabaseHelper::batchSelect('titus_intl', 
			'ti_page_id', 
			array('ti_language_code'=>$wgLanguageCode),
			__METHOD__, 
			array(), 
			2000, 
			$dbw);

$deletedPageIds = array();
foreach ($rows as $row) {
	$t = Title::newFromId($row->ti_page_id);
	if (!($t && $t->exists() && $t->getNamespace() == NS_MAIN)) {
		$deletedPageIds[] = $row->ti_page_id;
	}
}

$chunks = array_chunk($deletedPageIds, 500);
foreach ($chunks as $chunk) {
	$aids = "(" . implode(",", $chunk) . ")";
	$sql = "DELETE FROM titus_intl where ti_page_id IN $aids AND ti_language_code='$wgLanguageCode'";
	var_dump($sql);
	$dbw->query($sql);
}

