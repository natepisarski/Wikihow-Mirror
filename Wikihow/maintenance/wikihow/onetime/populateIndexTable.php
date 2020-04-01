<?php
/*
 * Initially process all titles to get their
 * index values for the index table
 */

require_once __DIR__ . '/../../commandLine.inc';
var_dump(microtime(true));
$res = DatabaseHelper::batchSelect('page', array('page_id'), array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ), __FUNCTION__);

foreach ($res as $row) {
	RobotPolicy::recalcArticlePolicyBasedOnId($row->page_id);
}
var_dump(microtime(true));
