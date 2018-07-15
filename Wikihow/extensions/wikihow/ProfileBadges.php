<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ProfileBadges',
    'author' => 'Bebeth Steudel',
    'description' => 'Page which shows the current Author Badges available',
);
$wgExtensionMessagesFiles['ProfileBadges'] = dirname(__FILE__) . '/ProfileBadges.i18n.php';

$wgSpecialPages['ProfileBadges'] = 'ProfileBadges';
$wgAutoloadClasses['ProfileBadges'] = dirname( __FILE__ ) . '/ProfileBadges.body.php';
