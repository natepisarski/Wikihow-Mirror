<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminSamples',
	'author' => 'Scott Cushman',
	'description' => 'Tool for managing Sample pages',
);

$wgSpecialPages['AdminSamples'] = 'AdminSamples';
$wgAutoloadClasses['AdminSamples'] = dirname( __FILE__ ) . '/AdminSamples.body.php';

