<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminSearchResults',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to lookup wikiHow articles for given queries using Yahoo Boss',
);

$wgSpecialPages['AdminSearchResults'] = 'AdminSearchResults';
$wgAutoloadClasses['AdminSearchResults'] = dirname( __FILE__ ) . '/AdminSearchResults.body.php';

