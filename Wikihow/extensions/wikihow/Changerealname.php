<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();
    
$wgAvailableRights[] = 'changerealname';
$wgGroupPermissions['sysop']['changerealname'] = true;

$wgExtensionCredits['other'][] = array(
	'name' => 'ChangeRealName',
	'author' => 'Travis Derouin',
	'description' => 'Changes the real name of a user',
);

$wgExtensionMessagesFiles['ChangeRealName'] = dirname(__FILE__) . '/Changerealname.i18n.php';
$wgSpecialPages['ChangeRealName'] = 'ChangeRealName';
$wgAutoloadClasses['ChangeRealName'] = dirname( __FILE__ ) . '/Changerealname.body.php';
