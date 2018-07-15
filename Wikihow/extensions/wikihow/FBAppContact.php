<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Facebook App Contact Creator',
	'author' => 'Jordan Small',
	'description' => 'An extension that stores the FB id and email of a user that authorizes wikihow to send them email',
);

$wgSpecialPages['FBAppContact'] = 'FBAppContact';
$wgAutoloadClasses['FBAppContact'] = dirname(__FILE__) . '/FBAppContact.body.php';
$wgExtensionMessagesFiles['FBAppContact'] = dirname(__FILE__) . '/FBAppContact.i18n.php';
