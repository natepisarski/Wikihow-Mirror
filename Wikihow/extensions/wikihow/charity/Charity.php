<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['Charity'] = 'Charity';
$wgSpecialPages['AdminNonProfitStories'] = 'AdminNonProfitStories';

$wgAutoloadClasses['Charity'] = __DIR__ . '/Charity.body.php';
$wgAutoloadClasses['AdminNonProfitStories'] = __DIR__ . '/admin/AdminNonProfitStories.body.php';

$wgExtensionMessagesFiles['Charity'] = __DIR__ . '/Charity.i18n.php';
$wgExtensionMessagesFiles['CharityAliases'] = __DIR__ . '/Charity.alias.php';

$wgAutoloadClasses['Donate'] = __DIR__ . '/Donate.class.php';

$wgResourceModules['ext.wikihow.charity.js'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'scripts' => [ 'charity.js' ],
	'remoteExtPath' => 'wikihow/charity',
	'position' => 'bottom',
	'dependencies' => ['ext.wikihow.common_bottom']
];

$wgResourceModules['ext.wikihow.charity.css'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'styles' => [ 'charity.less' ],
	'remoteExtPath' => 'wikihow/charity',
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.donate'] = [
	'styles' => ['donate.css'],
	'scripts' => ['donate.js'],
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/charity',
	'messages' => [
		'donate_button_response'
	],
	'position' => 'bottom',
	'targets' => [ 'desktop', 'mobile' ]
];

$wgResourceModules['ext.wikihow.admin_nonprofit_stories'] = [
	'styles' => [
		'admin_nonprofit_stories.css',
		'../../common/font-awesome-4.2.0/css/font-awesome.min.css'
	],
	'scripts' => ['admin_nonprofit_stories.js'],
	'localBasePath' => __DIR__ . '/admin/' ,
	'remoteExtPath' => 'wikihow/charity/admin',
	'dependencies' => [
		'ext.wikihow.charity.css',
		'jquery.ui.sortable'
	],
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ]
];

$wgHooks['BeforePageDisplay'][] = 'Donate::onBeforePageDisplay';
$wgHooks["IsEligibleForMobileSpecial"][] = ["Charity::isEligibleForMobileSpecial"];
