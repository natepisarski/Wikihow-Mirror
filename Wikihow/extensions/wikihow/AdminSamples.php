<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminSamples',
	'author' => 'Scott Cushman',
	'description' => 'Tool for managing Sample pages',
);

$wgSpecialPages['AdminSamples'] = 'AdminSamples';
$wgAutoloadClasses['AdminSamples'] = __DIR__ . '/AdminSamples.body.php';
