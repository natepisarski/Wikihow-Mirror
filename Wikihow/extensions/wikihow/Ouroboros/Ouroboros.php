<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'Ouroboros',
	'author' => 'George Bahij',
	'namemsg' => 'ouroboros',
	'description' => 'A deconstructive manifestation of the cyclical nature of the multiverse transposed into our physical realm through the fractal medium of emergent complexity at the contemporary intersection of life and technology',
	'descriptionmsg' => 'ouroborosdescription',
	'version' => 1
];

$wgSpecialPages['Ouroboros'] = 'Ouroboros\Special';
$wgAutoloadClasses['Ouroboros\Ouroboros'] = __DIR__ . '/Ouroboros.body.php';
$wgAutoloadClasses['Ouroboros\Special'] = __DIR__ . '/SpecialOuroboros.php';

$wgResourceModules['ext.wikihow.ouroboros.styles'] = [
	'styles' => [
		'resources/ouroboros.css'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Ouroboros',
	'position' => 'top',
	'targets' => ['mobile']
];

$wgResourceModules['ext.wikihow.ouroboros.scripts'] = [
	'scripts' => [
		'resources/ouroboros.js'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Ouroboros',
	'position' => 'bottom',
	'targets' => ['mobile']
];

