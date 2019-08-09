<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminMassEdit',
	'author' => 'Aaron G',
	'description' => 'tool for admin to put edits on multiple files',
);

$wgSpecialPages['AdminMassEdit'] = 'AdminMassEdit';
$wgAutoloadClasses['AdminMassEdit'] = __DIR__ . '/AdminMassEdit.body.php';

$wgResourceModules['ext.wikihow.adminmassedit'] = [
	'scripts' => [ 'adminmassedit.js' ],
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'jquery' ],
	'remoteExtPath' => 'wikihow/MassEdit',
	'position' => 'top'
];
