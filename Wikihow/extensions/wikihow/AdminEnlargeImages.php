<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminEnlargeImages',
	'author' => 'Reuben',
	'description' => 'Tool for support personnel to enlarge all the images in an article, given a list of wikiHow URLs',
);

$wgSpecialPages['AdminEnlargeImages'] = 'AdminEnlargeImages';
$wgAutoloadClasses['AdminEnlargeImages'] = __DIR__ . '/AdminEnlargeImages.body.php';
