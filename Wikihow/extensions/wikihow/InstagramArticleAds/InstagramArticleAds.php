<?php

$wgAutoloadClasses['InstagramArticleAds'] = __DIR__ . '/InstagramArticleAds.class.php';
$wgAutoloadClasses['InstagramArticleAdsAPI'] = __DIR__ . '/InstagramArticleAds.api.php';

$wgAPIModules['instagram_article_ads'] = 'InstagramArticleAdsAPI';

$wgMessagesDirs['InstagramArticleAds'] = __DIR__ . '/i18n';

$wgHooks['BeforePageDisplay'][] = ['InstagramArticleAds::onBeforePageDisplay'];

$wgResourceModules['mobile.wikihow.iphonetips_ig_ad'] = [
	'scripts' => [ 'iphonetips_ig_ad.js' ],
	'styles' => [ 'iphonetips_ig_ads.css' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/InstagramArticleAds/resources',
	'targets' => [ 'mobile' ],
	'position' => 'bottom'
];
