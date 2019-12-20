<?php

$wgAutoloadClasses['SocialFooter'] = __DIR__ . '/SocialFooter.class.php';

$wgHooks['BeforePageDisplay'][] = ['SocialFooter::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.social_footer_styles'] = [
	'styles' => [ 'social_footer.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/SocialFooter/assets',
	'targets' => [ 'desktop', 'mobile' ],
];

$wgResourceModules['ext.wikihow.social_footer'] = [
	'scripts' => [ 'social_footer.js' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/SocialFooter/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];
