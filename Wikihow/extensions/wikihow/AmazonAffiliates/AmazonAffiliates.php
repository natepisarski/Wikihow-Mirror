<?php

$wgAutoloadClasses['AmazonAffiliates'] = __DIR__ . '/AmazonAffiliates.class.php';
$wgMessagesDirs['AmazonAffiliates'] = __DIR__ . '/i18n/';

$wgHooks['ProcessArticleHTMLAfter'][] = ['AmazonAffiliates::onProcessArticleHTMLAfter'];

$wgResourceModules['ext.wikihow.amazon_affiliates'] = [
	'styles' => [ 'amazon_affiliates.css' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/AmazonAffiliates/assets',
	'targets' => [ 'desktop' ],
	'position' => 'top'
];
