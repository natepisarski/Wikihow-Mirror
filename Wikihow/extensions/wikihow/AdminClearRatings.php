<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminClearRatings',
	'author' => 'George Bahij',
	'description' => 'Tool for clearing the page ratings of many articles at once'
);

$wgSpecialPages['AdminClearRatings'] = 'AdminClearRatings';
$wgAutoloadClasses['AdminClearRatings'] = dirname(__FILE__) . '/AdminClearRatings.body.php';
