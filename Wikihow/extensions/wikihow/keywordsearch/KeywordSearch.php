<?php

if ( ! defined( 'MEDIAWIKI' ) )
        die();

$wgSpecialPages['KeywordSearch'] = 'KeywordSearch';
$wgAutoloadClasses['KeywordSearch'] = dirname( __FILE__ ) . '/KeywordSearch.body.php';

