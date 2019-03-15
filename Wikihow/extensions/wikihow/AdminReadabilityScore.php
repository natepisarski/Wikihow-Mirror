<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminReadabilityScore',
	'author' => 'Scott Cushman',
	'description' => 'Tool for figure out the average reading level of different articles',
);

$wgSpecialPages['AdminReadabilityScore'] = 'AdminReadabilityScore';
$wgAutoloadClasses['AdminReadabilityScore'] = __DIR__ . '/AdminReadabilityScore.body.php';
