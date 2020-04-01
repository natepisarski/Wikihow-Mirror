<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminSearchResults',
	'author' => 'Reuben',
	'description' => 'Tool for support personnel to lookup wikiHow articles for given queries using Yahoo Boss',
);

$wgSpecialPages['AdminSearchResults'] = 'AdminSearchResults';
$wgAutoloadClasses['AdminSearchResults'] = __DIR__ . '/AdminSearchResults.body.php';
