<?php

require_once __DIR__ . '/../../commandLine.inc';

$conds = [];
if(!$argv || $argv[0] != "all") {
	$conds['LIMIT'] = 10;
}

$res = DatabaseHelper::batchSelect("page", ["page_id", "page_title"], ["page_namespace" => NS_MAIN, "page_is_redirect" => 0 ], __METHOD__, $conds);

$titles = [];
$keywords = [];
$count = 1;
foreach($res as $row) {
	$keyword = wfMessage("howto", $row->page_title)->text();
	if(strlen($keyword) > 80) {
		$keyword = substr($keyword, 0, 80);
	}
	$titles[$keyword] = $row->page_id;
	$keywords[] = $keyword;

	if($count % 800 == 0) {
		$results = SearchVolume::hitAPI($keywords);
		$values = [];
		foreach($results as $keyword => $volume) {
			$values[] = ['sv_page_id' => $titles[$keyword], 'sv_volume' => $volume];
		}
		SearchVolume::addNewTitles($values);
		$titles = [];
		$keywords = [];
		usleep(5800000);
	}
	$count++;
}
//see if there was anything left in the queue
if(count($keywords) > 0) {
	$results = SearchVolume::hitAPI($keywords);
	$values = [];
	foreach($results as $keyword => $volume) {
		$values[] = ['sv_page_id' => $titles[$keyword], 'sv_volume' => $volume];
	}
	SearchVolume::addNewTitles($values);
}