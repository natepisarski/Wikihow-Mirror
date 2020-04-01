<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Duplicate Article Tool',
	'author' => 'bebeth',
	'description' => 'tool to vote on proposed redirects',
);

$wgSpecialPages['DuplicateTitles'] = 'DuplicateTitles';
$wgAutoloadClasses['DuplicateTitles'] = __DIR__ . '/DuplicateTitles.body.php';
$wgMessagesDirs['DuplicateTitles'] = __DIR__ . '/i18n';

$wgLogTypes[] = 'duplicatetitles';
$wgLogNames['duplicatetitleslog'] = 'duplicatetitleslog';
$wgLogHeaders['duplicatetitles'] = 'duplicatetitles';

$wgResourceModules['ext.wikihow.DuplicateTitles'] = array(
	'styles' => ['duplicatetitles.less'],
	'scripts' => ['duplicatetitles.js'],
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/duptool',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ]
);
