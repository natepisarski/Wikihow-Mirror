<?php
/*
*  This script imports urls into the babelfish system given a filename
*/

require_once __DIR__ . '/../commandLine.inc';

$wapDB = WAPDB::getInstance(WAPDB::DB_BABELFISH);

$file = $argv[1];
$simulate = false;
$wapDB->removeAllUnreservedAndNotCompletedArticles();
$wapDB->importArticles($file, $simulate);
