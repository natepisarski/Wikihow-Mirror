<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'MMK Manager',
	'author' => 'Scott Cushman',
	'description' => 'Tool for managing our top queries and keywords for our one million [insert Dr. Evil image] keywords',
);

$wgSpecialPages['MMKManager'] = 'MMKManager';
$wgAutoloadClasses['MMKManager'] = dirname( __FILE__ ) . '/MMKManager.body.php';

