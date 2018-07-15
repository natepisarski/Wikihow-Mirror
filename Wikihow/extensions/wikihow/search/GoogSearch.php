<?php 

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'GoogSearch',
    'author' => 'Vu',
    'description' => 'Google Custom Search',
);

$wgExtensionMessagesFiles['GoogSearch'] = dirname(__FILE__) . '/GoogSearch.i18n.php';
$wgSpecialPages['GoogSearch'] = 'GoogSearch';
$wgAutoloadClasses['GoogSearch'] = dirname( __FILE__ ) . '/GoogSearch.body.php';


