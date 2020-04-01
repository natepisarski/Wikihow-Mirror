<?php

define('WH_USE_BACKUP_DB', true);
require_once __DIR__ . '/../commandLine.inc';

$maintenance = WAPMaintenance::getInstance(WAPDB::DB_RETRANSLATEFISH);
$maintenance->nightly();

