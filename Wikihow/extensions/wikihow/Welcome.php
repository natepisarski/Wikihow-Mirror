<?php 

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Welcome',
    'author' => 'Vu Nguyen',
    'description' => 'Welcome to new wikiHow users',
);

$wgExtensionMessagesFiles['Welcome'] = dirname(__FILE__) . '/Welcome.i18n.php';
$wgSpecialPages['Welcome'] = 'Welcome';
$wgAutoloadClasses['Welcome'] = dirname( __FILE__ ) . '/Welcome.body.php';

$wgHooks['ConfirmEmailComplete'][] = array("Welcome::sendWelcomeUser");

