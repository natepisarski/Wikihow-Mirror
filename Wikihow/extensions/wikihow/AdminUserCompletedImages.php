<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminUserCompletedImages',
	'author' => 'George Bahij',
	'description' => 'Tool for viewing and managing User Completed Images.'
);

$wgSpecialPages['AdminUserCompletedImages'] = 'AdminUserCompletedImages';
$wgAutoloadClasses['AdminUserCompletedImages'] = dirname(__FILE__) . '/AdminUserCompletedImages.body.php';