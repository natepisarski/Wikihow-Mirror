<?php

$wgSpecialPages['HighSchoolHacks'] = 'HighSchoolHacks';
$wgAutoloadClasses['HighSchoolHacks'] = __DIR__ . '/HighSchoolHacks.body.php';

$wgMessagesDirs['HighSchoolHacks'] = __DIR__ . '/i18n';

$wgHooks['BeforePageDisplay'][] = ['HighSchoolHacks::onBeforePageDisplay'];
$wgHooks['WebRequestPathInfoRouter'][] = ['HighSchoolHacks::onWebRequestPathInfoRouter'];

$wgResourceModules['ext.wikihow.high_school_hacks.styles'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/HighSchoolHacks/resources',
	'localBasePath' => __DIR__ . '/resources',
	'styles' => [
		'high_school_hacks.css',
		'icons.css'
	]
];

$wgResourceModules['ext.wikihow.high_school_hacks.scripts'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'bottom',
	'remoteExtPath' => 'wikihow/HighSchoolHacks/resources',
	'localBasePath' => __DIR__ . '/resources',
	'scripts' => ['high_school_hacks.js']
];

$wgResourceModules['ext.wikihow.high_school_hacks.article_icon'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'bottom',
	'remoteExtPath' => 'wikihow/HighSchoolHacks/resources',
	'localBasePath' => __DIR__ . '/resources',
	'styles' => [
		'article_icon.css',
		'icons.css'
	],
	'scripts' => ['article_icon.js']
];

