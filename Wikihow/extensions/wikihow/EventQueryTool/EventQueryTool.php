<?php

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'EventQueryTool',
	'author' => 'Alberto',
	'description' => "UI to query the event_log table",
);

$wgSpecialPages['EventQueryTool'] = 'EventQueryTool';
$wgAutoloadClasses['EventQueryTool'] = __DIR__ . '/EventQueryTool.body.php';

$wgResourceModules['ext.wikihow.EventQueryTool'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/EventQueryTool',
	'localBasePath' => __DIR__,
	'styles' => [
		'../common/select2/select2.min.css',
		'../common/jquery-ui-1.12.1/jquery-ui.min.css',
		'EventQueryTool.less',
	],
	'scripts' => [
		'../common/jquery.extendext.whmodified.js',
		'../common/select2/select2.js',
		'../common/jquery-ui-1.12.1/jquery-ui.min.js',
		'EventQueryTool.js',
	],
];
