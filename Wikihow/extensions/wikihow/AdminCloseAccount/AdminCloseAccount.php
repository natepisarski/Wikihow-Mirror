<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCloseAccount',
	'author' => 'Alberto Burgos',
	'description' => "A tool for staff members to close user accounts",
);

$wgSpecialPages['AdminCloseAccount'] = 'AdminCloseAccount';
$wgAutoloadClasses['AdminCloseAccount'] = dirname(__FILE__) . '/AdminCloseAccount.body.php';

$wgMessagesDirs['AdminCloseAccount'] = __DIR__ . '/i18n';

$wgLogTypes[] = 'closeaccount';
$wgLogActionsHandlers['closeaccount/close'] = 'LogFormatter';

$wgResourceModules['ext.wikihow.admincloseaccount'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/AdminCloseAccount/resources',
	'localBasePath' => dirname(__FILE__) . '/resources',
	'styles' => ['admincloseaccount.less'],
	'scripts' => ['admincloseaccount.js'],
];
