<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Bot Block IP Whitelist',
	'author' => 'Gaurang Prasad',
	'description' => 'Tool for whitelisting blocked IP addresses',
);

$wgSpecialPages['BotBlockIPWhitelist'] = 'BotBlockIPWhitelist';
$wgAutoloadClasses['BotBlockIPWhitelist'] = __DIR__ . '/BotBlockIPWhitelist.body.php';
$wgMessagesDirs['BotBlockIPWhitelist'] = __DIR__ . '/i18n';

$wgResourceModules['ext.wikihow.specialbotblockipwhitelist'] = array(
	'targets' => array( 'desktop' ),
	'remoteExtPath' => 'wikihow/specialbotblockipwhitelist',
	'scripts' => array(
		'botblockipwhitelist.js',
		'../common/jquery.simplemodal.1.4.4.min.js'
		),
	'localBasePath' => __DIR__ . '/'
);

$wgResourceModules['ext.wikihow.specialbotblockipwhitelist.styles'] = array(
	'styles' => array(
		'botblockipwhitelist.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialbotblockipwhitelist',
	'position' => 'top',
	'targets' => array( 'desktop' )
);

$wgHooks['WebRequestPathInfoRouter'][] = ['BotBlockIPWhitelist::onWebRequestPathInfoRouter'];

$wgExtensionMessagesFiles['BotBlockIPWhitelistAliases'] = __DIR__ . '/BotBlockIPWhitelist.alias.php';
$wgAutoloadClasses['ApiWhitelistIP'] = __DIR__ . '/ApiWhitelistIP.body.php';
$wgAutoloadClasses['ApiWhitelistIP'] = __DIR__ . '/ApiWhitelistIP.body.php';
$wgAPIModules['allwhitelistip'] = 'ApiWhitelistIP';

