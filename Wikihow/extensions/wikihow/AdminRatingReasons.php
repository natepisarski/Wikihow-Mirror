<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminRatingReasons',
	'author' =>'Argutier <aaron@wikihow.com>',
	'description' => 'Tool for support personnel to manage items were rating poorly.',
);

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ArticleHelpfulness',
	'author' =>'Argutier <aaron@wikihow.com>',
	'description' => 'Page to show article helpfulness feedback',
);

$wgSpecialPages['AdminRatingReasons'] = 'AdminRatingReasons';
$wgAutoloadClasses['AdminRatingReasons'] = __DIR__ . '/AdminRatingReasons.body.php';

$wgSpecialPages['AdminRemoveRatingReason'] = 'AdminRemoveRatingReason';
$wgAutoloadClasses['AdminRemoveRatingReason'] = __DIR__ . '/AdminRatingReasons.body.php';

$wgSpecialPages['ArticleHelpfulness'] = 'ArticleHelpfulness';
$wgAutoloadClasses['ArticleHelpfulness'] = __DIR__ . '/AdminRatingReasons.body.php';
