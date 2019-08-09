<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCommunityDashboard',
	'author' => 'Reuben',
	'description' => 'Tool for support personnel to change admin settings for the community dashboard',
);

$wgSpecialPages['AdminCommunityDashboard'] = 'AdminCommunityDashboard';
$wgAutoloadClasses['AdminCommunityDashboard'] = __DIR__ . '/AdminCommunityDashboard.body.php';

$wgResourceModules['ext.wikihow.admincommunitydashboard_styles'] = array(
	'scripts' => ['admincommunitydashboard.css'],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/dashboard',
	'position' => 'bottom',
	'targets' => ['desktop', 'mobile'],
);

$wgResourceModules['ext.wikihow.admincommunitydashboard'] = array(
	'scripts' => ['admincommunitydashboard.js'],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/dashboard',
	'position' => 'bottom',
	'targets' => ['desktop', 'mobile'],
	'dependencies' => ['ext.wikihow.common_bottom', 'jquery'],
);
