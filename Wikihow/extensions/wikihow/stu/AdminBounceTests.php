<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminBounceTests',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to get bounce stats, given a list of wikiHow URLs',
);

$wgSpecialPages['AdminBounceTests'] = 'AdminBounceTests';
$wgSpecialPages['Stu'] = 'AdminBounceTests'; // special page alias
$wgAutoloadClasses['AdminBounceTests'] = dirname( __FILE__ ) . '/AdminBounceTests.body.php';

