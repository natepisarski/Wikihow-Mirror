<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Titus Query Tool',
	'author' => 'Jordan Small',
	'description' => 'A tool to query the Titus DB',
);

$wgSpecialPages['TitusQueryTool'] = 'TitusQueryTool';
$wgAutoloadClasses['TitusQueryTool'] = __DIR__ . '/TitusQueryTool.body.php';
$wgExtensionMessagesFiles['TitusQueryTool'] = __DIR__ . '/TitusQueryTool.i18n.php';

$wgResourceModules['ext.wikihow.titusquerytool'] = array(
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/titus',
	'targets' => ['desktop'],
	'styles' => [
		'resources/styles/titusquerytool.css',
	],
	'scripts' => [
		'../common/jquery-canvas-sparkles.min.js',
		'../common/download.jQuery.js',
		'resources/scripts/titusquerytool.js',
	],
	'dependencies' => [
		'ext.wikihow.common_bottom',
		'wikihow.common.ace',
		'wikihow.common.querybuilder',
	],
);
