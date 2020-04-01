<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['Contribute'] = 'Contribute';

$wgAutoloadClasses['Contribute'] = __DIR__ . '/Contribute.body.php';

//$wgExtensionMessagesFiles['Contribute'] = __DIR__ . '/Contribute.i18n.php';

$wgResourceModules['ext.wikihow.contribute.js'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop'],
	'scripts' => [ 'contribute.js' ],
	'remoteExtPath' => 'wikihow/contribute',
	'position' => 'bottom',
	'dependencies' => ['ext.wikihow.common_bottom']
];

$wgResourceModules['ext.wikihow.contribute.css'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'styles' => [ 'contribute.less' ],
	'remoteExtPath' => 'wikihow/contribute',
	'position' => 'top'
];
