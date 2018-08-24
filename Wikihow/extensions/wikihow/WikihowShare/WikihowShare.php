<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'WikihowShare',
	'author' => 'wikiHow',
	'description' => 'Class for general social network sharing of wikiHow articles',
);

$wgSpecialPages['WikihowShare'] = 'WikihowShareRest';
$wgAutoloadClasses['WikihowShareRest'] = dirname( __FILE__ ) . '/WikihowShare.body.php';
$wgAutoloadClasses['WikihowShare'] = dirname( __FILE__ ) . '/WikihowShare.body.php';

