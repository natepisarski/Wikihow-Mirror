<?php
/*
 * Add different convert templates to our articles
 *
 */

require_once __DIR__ . '/../commandLine.inc';

//use the spare1 db so the reads don't affect master
define('WH_USE_BACKUP_DB', true);

UnitGuardian::checkAllDirtyArticles();
