<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCopyCheck',
	'author' => 'Scott Cushman',
	'description' => 'Tool for checking for plagiarism',
);

$wgSpecialPages['AdminCopyCheck'] = 'AdminCopyCheck';
$wgAutoloadClasses['AdminCopyCheck'] = dirname( __FILE__ ) . '/AdminCopyCheck.body.php';

