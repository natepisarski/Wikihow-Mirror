<?php
/**
 * This script cleans up articles from the Retranslatefish system
 */

require_once __DIR__ . '/../commandLine.inc';

$wapDB = WAPDB::getInstance(WAPDB::DB_RETRANSLATEFISH);
$wapDB->removeAllUnreservedAndNotCompletedArticles();

