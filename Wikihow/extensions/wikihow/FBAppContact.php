<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Facebook App Contact Creator',
	'author' => 'Jordan Small',
	'description' => 'An extension that stores the FB id and email of a user that authorizes wikihow to send them email',
);

$wgSpecialPages['FBAppContact'] = 'FBAppContact';
$wgAutoloadClasses['FBAppContact'] = __DIR__ . '/FBAppContact.body.php';
$wgExtensionMessagesFiles['FBAppContact'] = __DIR__ . '/FBAppContact.i18n.php';
