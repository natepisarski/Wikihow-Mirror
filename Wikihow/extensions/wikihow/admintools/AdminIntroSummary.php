<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminIntroSummary',
	'author' => 'Bebeth Steudel <bebeth@wikihow.com>',
	'description' => "Tool to get step info to be used in the intro",
);

$wgSpecialPages['AdminIntroSummary'] = 'AdminIntroSummary';
$wgAutoloadClasses['AdminIntroSummary'] = __DIR__ . '/AdminIntroSummary.body.php';
