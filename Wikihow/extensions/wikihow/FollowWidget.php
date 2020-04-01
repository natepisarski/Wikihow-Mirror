<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'FollowWidget',
	'author' => 'Bebeth Steudel',
	'description' => 'Follow Us Widget',
);

$wgExtensionMessagesFiles['FollowWidget'] = __DIR__ . '/FollowWidget.i18n.php';
$wgSpecialPages['FollowWidget'] = 'FollowWidget';
$wgAutoloadClasses['FollowWidget'] = __DIR__ . '/FollowWidget.body.php';
