<?php
/**
 * Calculate index_info.ii_policy for every Category page
 *
 * time whrun --lang=all php prod/maintenance/wikihow/onetime/populateIndexTableForCategories.php
 */

require_once __DIR__ . '/../../commandLine.inc';

global $wgLanguageCode;

echo "$wgLanguageCode\n";

$dbr = wfGetDB(DB_REPLICA);
$res = $dbr->select('page', ['page_id', 'page_title'], ['page_namespace' => NS_CATEGORY, 'page_is_redirect' => 0]);
foreach ($res as $row) {
	$title = Title::makeTitle(NS_CATEGORY, $row->page_title);
	$page = WikiPage::factory($title);
	RobotPolicy::clearArticleMemc($page);
	RobotPolicy::isIndexable($title); // This populates the `index_info` table
}
