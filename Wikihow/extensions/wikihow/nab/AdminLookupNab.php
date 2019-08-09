<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminLookupNab',
	'author' => 'wikiHow',
	'description' => 'Tool for support personnel to look up pages in NAB given a list of wikiHow URLs',
);

$wgSpecialPages['AdminLookupNab'] = 'AdminLookupNab';
$wgAutoloadClasses['AdminLookupNab'] = __DIR__ . '/AdminLookupNab.body.php';

$wgResourceModules['ext.wikihow.adminlookup'] = [
	'scripts' => ['adminlookup.js'],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/nab',
	'position' => 'bottom',
	'targets' => ['desktop', 'mobile'],
	'dependencies' => ['ext.wikihow.common_bottom', 'jquery'],
];
