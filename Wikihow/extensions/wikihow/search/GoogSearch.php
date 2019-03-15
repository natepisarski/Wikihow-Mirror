<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'GoogSearch',
    'author' => 'Vu',
    'description' => 'Google Custom Search',
);

$wgExtensionMessagesFiles['GoogSearch'] = __DIR__ . '/GoogSearch.i18n.php';
$wgSpecialPages['GoogSearch'] = 'GoogSearch';
$wgAutoloadClasses['GoogSearch'] = __DIR__ . '/GoogSearch.body.php';


