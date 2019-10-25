<?php

$wgAutoloadClasses['WikihowNamespacePages'] = __DIR__ . '/WikihowNamespacePages.class.php';
$wgMessagesDirs['WikihowNamespacePages'] = __DIR__ . '/i18n/';

$wgHooks['WikihowTemplateShowTopLinksSidebar'][] = ['WikihowNamespacePages::onWikihowTemplateShowTopLinksSidebar'];
$wgHooks['BeforePageDisplay'][] = ['WikihowNamespacePages::onBeforePageDisplay'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['WikihowNamespacePages::onMobileProcessArticleHTMLAfter'];
$wgHooks['IsEligibleForMobile'][] = ['WikihowNamespacePages::onIsEligibleForMobile'];

$wgResourceModules['mobile.wikihow.wikihow_namespace_styles'] = [
	'styles' => [ 'wikihow_namespace_styles.less' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/WikihowNamespacePages/resources',
	'targets' => [ 'mobile' ],
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.trustworthy_styles'] = [
	'styles' => [ 'trustworthy.css' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/WikihowNamespacePages/resources',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'top'
];

