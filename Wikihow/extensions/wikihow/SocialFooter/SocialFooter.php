<?php

$wgAutoloadClasses['SocialFooter'] = __DIR__ . '/SocialFooter.class.php';

$wgHooks['BeforePageDisplay'][] = ['SocialFooter::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.social_footer'] = [
	'styles' => [ 'social_footer.css' ],
	'scripts' => [ 'social_footer.js' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/SocialFooter/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];
