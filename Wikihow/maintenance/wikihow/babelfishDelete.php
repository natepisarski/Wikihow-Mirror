<?php
/*
* This script cleans up articles from the Babelfish system
*/

require_once __DIR__ . '/../commandLine.inc';

$wapDB = WAPDB::getInstance(WAPDB::DB_BABELFISH);

$wapDB->removeAllUnreservedAndNotCompletedArticles();
