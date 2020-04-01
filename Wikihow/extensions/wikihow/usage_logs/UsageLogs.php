<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgAutoloadClasses['UsageLogs'] = __DIR__ . '/UsageLogs.body.php';

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'UsageLogs',
	'author' => 'David Morrow',
	'description' => 'This endpoint is called via POST ajax requests to log user events.',
);

$wgSpecialPages['UsageLogs'] = 'UsageLogs';

$wgResourceModules['ext.wikihow.UsageLogs'] = array(
	'scripts' => array(
		'usage_logs.js'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/usage_logs',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'dependencies' => array(
		'mediawiki.page.ready',
		'wikihow.common.underscore',
		'wikihow.common.bowser'
	)
);
