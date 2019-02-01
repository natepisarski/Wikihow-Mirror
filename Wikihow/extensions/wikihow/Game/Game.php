<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'Game',
	'author' => 'Trevor Parscal <trevorparscal@gmail.com>',
	'description' => 'Provides a way for users to buy the wikiHow Game'
];

$wgSpecialPages['Game'] = 'SpecialGame';

$wgAutoloadClasses['Game'] = __DIR__ . '/Game.body.php';
$wgAutoloadClasses['SpecialGame'] = __DIR__ . '/SpecialGame.php';
$wgExtensionMessagesFiles['Game'] = __DIR__ . '/Game.i18n.php';
$wgExtensionMessagesFiles['GameAliases'] = __DIR__ . '/Game.alias.php';

$wgResourceModules['ext.wikihow.game'] = [
	'styles' => [ 'resources/index.less' ],
	'scripts' => [ 'resources/index.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Game',
	'position' => 'top',
	'targets' => [ 'desktop', 'mobile' ]
];
