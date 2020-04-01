<?php

if ( ! defined( 'MEDIAWIKI' ) )
        die();

$wgSpecialPages['KeywordSearch'] = 'KeywordSearch';
$wgAutoloadClasses['KeywordSearch'] = __DIR__ . '/KeywordSearch.body.php';

