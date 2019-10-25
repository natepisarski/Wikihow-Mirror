<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['special'][] = array(
	'name' => 'Bloggers',
	'author' => 'Reuben',
	'description' => 'Display a Google form for bloggers',
);

$wgSpecialPages['Bloggers'] = 'Bloggers';
$wgAutoloadClasses['Bloggers'] = __DIR__ . '/Bloggers.body.php';
