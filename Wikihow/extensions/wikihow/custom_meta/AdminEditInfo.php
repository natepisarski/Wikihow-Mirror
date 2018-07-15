<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminEditInfo',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to hand-edit meta descriptions and page titles of articles, given a list of wikiHow URLs',
);

$wgSpecialPages['AdminEditMetaInfo'] = 'AdminEditInfo';
$wgSpecialPages['AdminEditPageTitles'] = 'AdminEditInfo';
$wgAutoloadClasses['AdminEditInfo'] = dirname( __FILE__ ) . '/AdminEditInfo.body.php';

