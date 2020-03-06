<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ProfileBadges',
    'author' => 'Bebeth Steudel',
    'description' => 'Page which shows the current Author Badges available',
);
$wgExtensionMessagesFiles['ProfileBadges'] = __DIR__ . '/ProfileBadges.i18n.php';

$wgSpecialPages['ProfileBadges'] = 'ProfileBadges';
$wgAutoloadClasses['ProfileBadges'] = __DIR__ . '/ProfileBadges.body.php';

$wgResourceModules['ext.wikihow.profilebadges'] = [
	'styles' => ['ProfileBadges.css'],
	'localBasePath' => __DIR__ ,
	'remoteExtPath' => 'wikihow/',
	'position' => 'top',
	'targets' => ['mobile', 'desktop']
];
