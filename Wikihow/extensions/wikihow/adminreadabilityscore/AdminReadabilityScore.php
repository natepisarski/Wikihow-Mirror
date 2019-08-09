<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminReadabilityScore',
	'author' => 'Scott Cushman',
	'description' => 'Tool for figure out the average reading level of different articles',
);

$wgSpecialPages['AdminReadabilityScore'] = 'AdminReadabilityScore';
$wgAutoloadClasses['AdminReadabilityScore'] = __DIR__ . '/AdminReadabilityScore.body.php';

$wgResourceModules['ext.wikihow.adminreadabilityscore'] = array(
   'scripts' => ['adminreadabilityscore.js'],
   'localBasePath' => __DIR__ . '/',
   'remoteExtPath' => 'wikihow/adminreadabilityscore',
   'position' => 'bottom',
   'targets' => ['desktop', 'mobile'],
   'dependencies' => ['ext.wikihow.common_bottom', 'jquery'],
);
