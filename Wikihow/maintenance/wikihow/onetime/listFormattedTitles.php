<?php

require_once __DIR__ . '/../../commandLine.inc';

$rows = DatabaseHelper::batchSelect('page', 
	array('page_id', 'page_title', 'page_is_redirect'), 
	array(
		'page_namespace' => NS_MAIN,
		'page_is_redirect' => 0,
	),
	__METHOD__
);

foreach ($rows as $row) {
	$title = Title::newFromDBkey($row->page_title);
	if (!$title) continue;
	$text = $title->getText();
	$formatted = GuidedEditorHelper::formatTitle($text);
	if ($formatted != $text && strlen($formatted) != strlen($text)) {
		$redir = $row->page_is_redirect ? ' (redirect)' : '';
		print "$title$redir ->\n$formatted\n";
	}
}
