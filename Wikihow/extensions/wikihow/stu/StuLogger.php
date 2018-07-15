<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Stu Logger',
	'author' => 'Ryo',
	'description' => 'AJAX end-point to log simple timing data',
);

$wgSpecialPages['StuLogger'] = 'StuLogger';
$wgSpecialPages['StuInspector'] = 'StuInspector';
$wgAutoloadClasses['StuLogger'] = __DIR__ . '/StuLogger.body.php';
$wgAutoloadClasses['StuInspector'] = __DIR__ . '/StuInspector.php';
$wgExtensionMessagesFiles['StuLogger'] = __DIR__ . '/StuLogger.i18n.php';
$wgExtensionMessagesFiles['StuInspectorAliases'] = __DIR__ . '/StuInspector.alias.php';

$wgHooks['AddTopEmbedJavascript'][] = 'StuLogger::getJavascriptPaths';
$wgHooks['BeforePageDisplay'][] = 'StuLogger::onBeforePageDisplay';
$wgHooks['BeforePageDisplay'][] = 'StuInspector::onBeforePageDisplay';
$wgHooks['JustBeforeOutputHTML'][] = 'StuLogger::onJustBeforeOutputHTML';
$wgHooks['WebRequestPathInfoRouter'][] = 'StuInspector::onAddPathRouter';

$wgResourceModules['ext.wikihow.stu_inspector'] = [
    'localBasePath' => __DIR__,
    'targets' => [ 'desktop', 'mobile' ],
    'styles' => [ 'stu_inspector.css' ],
    'scripts' => [ 'stu_inspector.js' ],
    'remoteExtPath' => 'wikihow',
    'position' => 'top' ];

// The basis for this code was taken from:
// https://www.mediawiki.org/wiki/API:Extensions
$wgExtensionCredits['api'][] = array(
	'path' => __FILE__,
	'name' => 'Stu API',
	'description' => 'An API extension to fetch info for stu',
	'descriptionmsg' => 'apidataextension-desc',
	'version' => 1,
	'author' => 'Reuben',
	'url' => 'https://www.mediawiki.org/wiki/API:Extensions',
);

$wgAutoloadClasses['ApiStu'] = __DIR__ . '/ApiStu.php';
$wgAPIModules['whstu'] = 'ApiStu';
