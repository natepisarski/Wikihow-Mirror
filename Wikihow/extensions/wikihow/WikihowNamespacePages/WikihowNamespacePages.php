<?php

$wgAutoloadClasses['WikihowNamespacePages'] = __DIR__ . '/WikihowNamespacePages.class.php';
$wgMessagesDirs['WikihowNamespacePages'] = __DIR__ . '/i18n/';
$wgExtensionMessagesFiles['WikihowNamespacePagesMagic'] = __DIR__ . '/WikihowNamespacePages.i18n.magic.php';

$wgHooks['WikihowTemplateShowTopLinksSidebar'][] = ['WikihowNamespacePages::onWikihowTemplateShowTopLinksSidebar'];
$wgHooks['BeforePageDisplay'][] = ['WikihowNamespacePages::onBeforePageDisplay'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['WikihowNamespacePages::onMobileProcessArticleHTMLAfter'];
$wgHooks['IsEligibleForMobile'][] = ['WikihowNamespacePages::onIsEligibleForMobile'];
$wgHooks['GetDoubleUnderscoreIDs'][] = ['WikihowNamespacePages::onGetDoubleUnderscoreIDs'];


$wgAutoloadClasses['ScienceCamp'] = __DIR__ . '/ScienceCamp.class.php';
$wgHooks['WebRequestPathInfoRouter'][] = ['ScienceCamp::onWebRequestPathInfoRouter'];
$wgHooks['BeforePageDisplay'][] = ['ScienceCamp::onBeforePageDisplay'];
$wgHooks['WikihowInsertBeforeContent'][] = ['ScienceCamp::onWikihowInsertBeforeContent'];
$wgHooks['WikihowInsertAfterContent'][] = ['ScienceCamp::onWikihowInsertAfterContent'];
$wgHooks['ShowArticleTabs'][] = ['ScienceCamp::showArticleTabs'];


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

$wgResourceModules['ext.wikihow.corona_guide'] = [
	'styles' => [ 'corona_guide.less' ],
	'scripts' => [ 'corona_guide.js' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/WikihowNamespacePages/resources',
	'targets' => [ 'desktop', 'mobile' ]
];

$wgResourceModules['ext.wikihow.teachers_guide'] = [
	'scripts' => [ 'teachers_guide.js' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/WikihowNamespacePages/resources',
	'targets' => [ 'desktop', 'mobile' ]
];

$wgResourceModules['ext.wikihow.science_camp'] = [
	'styles' => [ 'science_camp.less' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/WikihowNamespacePages/resources',
	'targets' => [ 'desktop', 'mobile' ]
];
