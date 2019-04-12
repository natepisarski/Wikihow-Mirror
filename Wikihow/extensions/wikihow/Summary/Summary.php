<?php

$wgNamespaceProtection[NS_SUMMARY] = ['summary-edit'];
$wgGroupPermissions['sysop']['summary-edit'] = true;
$wgGroupPermissions['staff']['summary-edit'] = true;
$wgGroupPermissions['translator']['summary-edit'] = true;

$wgSpecialPages['SummaryEditTool'] = 'SummaryEditTool';
$wgSpecialPages['TranslateSummaries'] = 'TranslateSummariesTool';
$wgSpecialPages['TranslateSummariesAdmin'] = 'TranslateSummariesAdmin';

$wgAutoloadClasses['SummarySection'] = __DIR__ . '/SummarySection.class.php';
$wgAutoloadClasses['SummaryEditTool'] = __DIR__ . '/SummaryEditTool.body.php';
$wgAutoloadClasses['TranslateSummaries'] = __DIR__ . '/TranslateSummaries.class.php';
$wgAutoloadClasses['TranslateSummariesTool'] = __DIR__ . '/TranslateSummariesTool.body.php';
$wgAutoloadClasses['TranslateSummariesAdmin'] = __DIR__ . '/TranslateSummariesAdmin.body.php';
$wgAutoloadClasses['EditMapper\TranslateSummariesEditMapper'] = __DIR__ . '/TranslateSummariesEditMapper.class.php';

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

$wgResourceModules['ext.wikihow.translate_summaries'] = [
	'scripts' => [ 'translate_summaries_tool.js' ],
	'styles' => [ 'translate_summaries_tool.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/Summary/assets',
	'targets' => [ 'desktop' ],
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.translate_summaries_admin'] = [
	'scripts' => [ 'translate_summaries_admin.js' ],
	'styles' => [ 'translate_summaries_admin.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/Summary/assets',
	'targets' => [ 'desktop' ],
	'position' => 'top',
	'dependencies' => [
		'jquery.ui.datepicker'
	]
];

$wgResourceModules['ext.wikihow.summary_ns_hide'] = [
	'styles' => [ 'summary_namespace_hide.css' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/Summary/assets',
	'targets' => [ 'desktop' ],
	'position' => 'top'
];
