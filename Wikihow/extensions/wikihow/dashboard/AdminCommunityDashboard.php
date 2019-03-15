<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCommunityDashboard',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to change admin settings for the community dashboard',
);

$wgSpecialPages['AdminCommunityDashboard'] = 'AdminCommunityDashboard';
$wgAutoloadClasses['AdminCommunityDashboard'] = __DIR__ . '/AdminCommunityDashboard.body.php';

