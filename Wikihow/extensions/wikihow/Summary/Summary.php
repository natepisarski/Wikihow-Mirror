<?php

$wgNamespaceProtection[NS_SUMMARY] = ['summary-edit'];
$wgGroupPermissions['sysop']['summary-edit'] = true;
$wgGroupPermissions['staff']['summary-edit'] = true;

$wgSpecialPages['SummaryEditTool'] = 'SummaryEditTool';

$wgAutoloadClasses['SummarySection'] = __DIR__ . '/SummarySection.class.php';
$wgAutoloadClasses['SummaryEditTool'] = __DIR__ . '/SummaryEditTool.body.php';
$wgExtensionMessagesFiles['SummarySection'] = __DIR__ . '/Summary.i18n.magic.php';
$wgMessagesDirs['SummarySection'] = __DIR__ . '/i18n/';

$wgHooks['ParserFirstCallInit'][] = ['SummarySection::onParserFirstCallInit'];
$wgHooks['ProcessArticleHTMLAfter'][] = ['SummarySection::onProcessArticleHTMLAfter'];
$wgHooks['BeforePageDisplay'][] = ['SummarySection::onBeforePageDisplay'];
$wgHooks['TitleMoveComplete'][] = ['SummarySection::onTitleMoveComplete'];

$wgResourceModules['ext.wikihow.summary_section_edit_link'] = [
	'scripts' => [ 'summary_section_edit_link.js' ],
	'styles' => [ 'summary_section_edit_link.css' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/Summary/assets',
	'messages' => [
		'summary_section_no_edit'
	],
	'targets' => [ 'desktop' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.summary_edit_cta'] = [
	'scripts' => [ 'summary_edit_cta.js' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/Summary/assets',
	'targets' => [ 'desktop' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.summary_edit_tool'] = [
	'scripts' => ['summary_edit_tool.js'],
	'styles' => ['summary_edit_tool.css'],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/Summary/assets',
	'messages' => [
		'set_err_no_summary',
		'set_err_no_last_sentence'
	],
	'targets' => [ 'desktop' ],
	'position' => 'bottom'
];