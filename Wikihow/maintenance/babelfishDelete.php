<?
/*
* This script cleans up articles from the Babelfish system
*/

require_once('commandLine.inc');

$wapDB = WAPDB::getInstance(WAPDB::DB_BABELFISH);

$wapDB->removeAllUnreservedAndNotCompletedArticles();
