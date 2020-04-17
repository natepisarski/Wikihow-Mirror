<?php

$wgAutoloadClasses['Disclaimer'] = __DIR__ . '/Disclaimer.class.php';
$wgMessagesDirs['Disclaimer'] = __DIR__ . '/i18n/';

$wgHooks['MobileProcessArticleHTMLAfter'][] = ['Disclaimer::onMobileProcessArticleHTMLAfter'];
$wgHooks['BeforePageDisplay'][] = ['Disclaimer::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.Disclaimer'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'bottom',
	'remoteExtPath' => 'wikihow/Disclaimer',
	'localBasePath' => __DIR__,
	'scripts' => [ 'Disclaimer.js' ],
];
