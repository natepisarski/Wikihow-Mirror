<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['NewPages'] = __DIR__ . '/NewPages.body.php';
$wgSpecialPages['Newpages'] =  'NewPages';
$wgMessagesDirs['NewPages'] = __DIR__ . '/i18n/';

$wgResourceModules['wikihow.newpages.styles'] = [
	'styles' => [
		'newpages.less',
	],
	'position' => 'top',
	'localBasePath' => __DIR__ . '/less',
	'remoteExtPath' => 'wikihow/newpages/less',
	'targets' => ['mobile', 'desktop'],
];
