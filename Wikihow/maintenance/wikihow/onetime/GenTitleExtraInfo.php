<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'GenTitleExtraInfo',
	'author' => 'Reuben Smith',
	'description' => 'Tmp extension to gen extra info for a list of titles',
);

$wgSpecialPages['GenTitleExtraInfo'] = 'GenTitleExtraInfo';
$wgAutoloadClasses['GenTitleExtraInfo'] = dirname( __FILE__ ) . '/GenTitleExtraInfo.body.php';

