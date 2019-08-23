<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Bot Block IP Whitelist',
	'author' => 'Gaurang Prasad',
	'description' => 'Tool for whitelisting blocked IP addresses',
);

$wgAutoloadClasses['BotBlockIPWhitelist'] = __DIR__ . '/BotBlockIPWhitelist.body.php';
$wgSpecialPages['BotBlockIPWhitelist'] = 'BotBlockIPWhitelist';
$wgHooks['WebRequestPathInfoRouter'][] = ['BotBlockIPWhitelist::onWebRequestPathInfoRouter'];

$wgAutoloadClasses['ApiWhitelistIP'] = __DIR__ . '/ApiWhitelistIP.body.php';
$wgAPIModules['allwhitelistip'] = 'ApiWhitelistIP';
$wgExtensionMessagesFiles['BotBlockIPWhitelistAliases'] = __DIR__ . '/BotBlockIPWhitelist.alias.php';
