<?
/*
	this script takes the article ids from the config storage db table for wikihow watermark article list
	and puts it into a db table with one entry per article id

	this is done so that we can iterate over this list and process the articles in it, adding watermarks to the images as necessary 
	and removing the data from this table
*/

require_once( "commandLine.inc" );

/*
    wikiphoto_article_watermark DB Table
    CREATE TABLE `wikiphoto_article_watermark` (
    `waw_article_id` int(8) unsigned NOT NULL,
	`waw_version` tinyint(3) unsigned default NULL,
    PRIMARY KEY  (`waw_article_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

echo "migrating articleids...\n";

$fname = 'migrateUnwatermarkedImagesList';

$dbw = wfGetDB( DB_MASTER );

$article_ids = explode("\n", ConfigStorage::dbGetConfig('wikihow-watermark-article-list'));
$insertArray = array();
foreach ($article_ids as $id) {
	array_push($insertArray, array("waw_article_id" => $id));
}

$table = "wikiphoto_article_watermark";

$c = count($insertArray);
echo "inserting $c articles into $table ...\n";
foreach (array_chunk($insertArray, 1000) as $input) {
	$dbw->insert($table, $insertArray, $fname, array('IGNORE'), $fname);
}
$dbw->update($table, array("waw_version=0"), array("waw_version is NULL"), $fname);
echo "done...\n";

?>
