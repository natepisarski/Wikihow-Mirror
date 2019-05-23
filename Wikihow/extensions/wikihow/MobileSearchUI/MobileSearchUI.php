<?php

$wgAutoloadClasses['MobileSearchUI'] = __DIR__ . '/MobileSearchUI.class.php';

$wgHooks['BeforePageDisplay'][] = ['MobileSearchUI::onBeforePageDisplay'];
$wgHooks['MobileEmbedStyles'][] = ['MobileSearchUI::onMobileEmbedStyles'];

$wgResourceModules['mobile.wikihow.search_header'] = [
	'scripts' => [ 'mobile_search_header.js' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/MobileSearchUI/resources',
	'targets' => [ 'mobile' ],
	'position' => 'bottom'
];
