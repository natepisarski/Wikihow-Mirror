<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Facebook Account Linker',
	'author' => 'Jordan Small',
	'description' => 'Links existing wikihow users accounts with their Facebook account',
);

$wgSpecialPages['FBLink'] = 'FBLink';
$wgAutoloadClasses['FBLink'] = __DIR__ . '/FBLink.body.php';
$wgExtensionMessagesFiles['FBLink'] = __DIR__ . '/FBLink.i18n.php';
