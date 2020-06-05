<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgSpecialPages['AdminCommonAvatars'] = 'AdminCommonAvatars';
$wgAutoloadClasses['AdminCommonAvatars'] = __DIR__ . '/AdminCommonAvatars.body.php';

$wgResourceModules['ext.wikihow.adminCommonAvatars'] = [
	'styles' => [ 'admincommonavatars.less' ],
	'scripts' => [ 'admincommonavatars.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/AdminCommonAvatars',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];
