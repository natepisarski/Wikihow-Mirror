<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Duplicate Article Tool',
	'author' => 'bebeth',
	'description' => 'tool to vote on proposed redirects',
);

$wgSpecialPages['DedupTool'] = 'DedupTool';
$wgAutoloadClasses['DedupTool'] = __DIR__ . '/DedupTool.body.php';
$wgMessagesDirs['DedupTool'] = __DIR__ . '/i18n';
$wgSpecialPages['AdminDedupTool'] = 'AdminDedupTool';
$wgAutoloadClasses['AdminDedupTool'] = __DIR__ . '/AdminDedupTool.body.php';
$wgMessagesDirs['AdminDedupTool'] = __DIR__ . '/i18n';
$wgResourceModules['ext.wikihow.DedupTool'] = array(
	'styles' => ['deduptool.less'],
	'scripts' => ['deduptool.js'],
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/dedup',
	'position' => 'top',
	'targets' => [ 'desktop' ]
);
