<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'PageStatCheck',
	'author' => 'Scott Cushman',
	'description' => 'Tool for checking for page stats on created articles',
);

$wgSpecialPages['PageStatCheck'] = 'PageStatCheck';
$wgAutoloadClasses['PageStatCheck'] = dirname( __FILE__ ) . '/PageStatCheck.body.php';

