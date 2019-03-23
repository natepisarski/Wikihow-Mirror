<?php

$wgExtensionCredits['Reverification'][] = array(
	'name' => 'Reverification',
	'author' => 'Jordan Small',
	'description' => 'A tool that lets wikiHow experts reverify articles',
);

$wgSpecialPages['Reverification'] = 'Reverification';
$wgSpecialPages['ReverificationQuickFeedback'] = 'ReverificationQuickFeedback';

$wgAutoloadClasses['Reverification'] = __DIR__ . '/reverification_tool/Reverification.body.php';
$wgAutoloadClasses['ReverificationQuickFeedback'] = __DIR__ . '/reverification_quick_feedback/ReverificationQuickFeedback.body.php';
$wgAutoloadClasses['ReverificationData'] = __DIR__ . '/ReverificationData.php';
$wgAutoloadClasses['ReverificationDB'] = __DIR__ . '/ReverificationDB.php';
$wgAutoloadClasses['ReverificationExporter'] = __DIR__ . '/ReverificationExporter.php';
$wgAutoloadClasses['ReverificationMaintenance'] = __DIR__ . '/ReverificationMaintenance.php';
$wgAutoloadClasses['ReverificationSpreadsheetUpdater'] = __DIR__ . '/ReverificationSpreadsheetUpdater.php';
$wgAutoloadClasses['WHMemcachedStorage'] = __DIR__ . '/util/WHMemcachedStorage.php';

$wgAutoloadClasses['ReverificationMaintenanceJob'] = __DIR__ . '/ReverificationMaintenanceJob.php';

$wgJobClasses['ReverificationMaintenanceJob'] = 'ReverificationMaintenanceJob';


$wgMessagesDirs['Reverification'] = [__DIR__ . '/reverification_tool/i18n/'];
$wgMessagesDirs['ReverificationQuickFeedback'] = [__DIR__ . '/reverification_quick_feedback/i18n/'];

$wgExtensionCredits['ReverificationAdmin'][] = array(
	'name' => 'ReverificationAdmin',
	'author' => 'Jordan Small',
	'description' => 'An admin tool to export reverification data',
);

$wgSpecialPages['ReverificationAdmin'] = 'ReverificationAdmin';
$wgAutoloadClasses['ReverificationAdmin'] = __DIR__ . '/admin/ReverificationAdmin.php';
$wgMessagesDirs['ReverificationAdmin'] = [__DIR__ . '/admin/i18n/'];
$wgMessagesDirs['ReverificationExporter'] = [__DIR__ . '/admin/i18n/'];

$wgResourceModules['ext.wikihow.reverification_admin'] = array(
	'styles' => ['reverificationadmin.less'],
	'scripts' => ['reverificationadmin.js'],
	'localBasePath' => __DIR__ . "/admin" ,
	'remoteExtPath' => 'wikihow/reverification/admin',
	'position' => 'bottom',
	'targets' => ['desktop'],
	'messages' => [],
	'dependencies' => [
		'ext.wikihow.common_bottom',
		'wikihow.common.jquery.download',
		'jquery.ui.datepicker',
	]
);

$wgResourceModules['ext.wikihow.reverification'] = array(
	'styles' => ['reverification.less'],
	'scripts' => ['reverification.js'],
	'localBasePath' => __DIR__ . '/reverification_tool',
	'remoteExtPath' => 'wikihow/reverification/reverification_tool',
	'position' => 'bottom',
	'targets' => ['desktop'],
	'messages' => [
		'rv_status_loading',
		'rv_status_eoq',
		'rv_status'
	],
	'dependencies' => ['ext.wikihow.common_bottom'],
);


$wgResourceModules['ext.wikihow.reverification_quick_feedback'] = array(
	'styles' => ['reverification_quick_feedback.less'],
	'scripts' => ['reverification_quick_feedback.js'],
	'localBasePath' => __DIR__ . '/reverification_quick_feedback',
	'remoteExtPath' => 'wikihow/reverification/reverification_quick_feedback',
	'position' => 'bottom',
	'targets' => ['desktop'],
	'messages' => [
		'rvq_status_loading',
		'rvq_status_eoq',
		'rvq_error',
		'rvq_status',
		'rvq_quick_feedback',
		'rvq_quick_feedback_label',
		'rvq_edit_summary',
		'rvq_btn_verified',
		'rvq_edit_summary_error'
	],
	'dependencies' => ['ext.wikihow.common_bottom'],
);
