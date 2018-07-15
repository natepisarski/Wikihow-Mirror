<?php
/*
 * This script imports urls into the retranslatefish system
 */

require_once __DIR__ . '/../commandLine.inc';

$wapDB = WAPDB::getInstance(WAPDB::DB_RETRANSLATEFISH);

$simulate = false;
$wapDB->importArticles($simulate);

