<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminLookupPages',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to look up pages info given a list of wikiHow URLs',
);

$wgSpecialPages['AdminLookupPages'] = 'AdminLookupPages';
$wgAutoloadClasses['AdminLookupPages'] = __DIR__ . '/AdminLookupPages.body.php';

$wgSpecialPages['AdminLookupNab'] = 'AdminLookupNab';
$wgAutoloadClasses['AdminLookupNab'] = __DIR__ . '/AdminLookupPages.body.php';
