<?php

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCoauthorIntl',
	'author' => 'Alberto Burgos',
	'description' => "Manage coauthor blurb translations",
);

$wgSpecialPages['AdminCoauthorIntl'] = 'AdminCoauthorIntl';
$wgAutoloadClasses['AdminCoauthorIntl'] = __DIR__ . '/AdminCoauthorIntl.body.php';

$wgResourceModules['ext.wikihow.AdminCoauthorIntl'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/socialproof/AdminCoauthorIntl',
	'localBasePath' => __DIR__,
	'styles' => ['AdminCoauthorIntl.less'],
	'scripts' => ['AdminCoauthorIntl.js'],
];
