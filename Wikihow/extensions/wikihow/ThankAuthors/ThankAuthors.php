<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ThankAuthors',
	'author' => 'Travis Derouin (wikiHow)',
	'description' => 'A way for users to leave fan mail on authors user_kudos page',
);

$wgSpecialPages['ThankAuthors'] = 'ThankAuthors';
$wgAutoloadClasses['ThankAuthors'] = __DIR__ . '/ThankAuthors.body.php';
$wgAutoloadClasses['ThankAuthorsJob'] = __DIR__ .'/ThankAuthorsJob.php';

define('NS_USER_KUDOS', 18);
define('NS_USER_KUDOS_TALK', 19);

$wgExtraNamespaces[NS_USER_KUDOS] = "User_kudos";
$wgExtraNamespaces[NS_USER_KUDOS_TALK] = "User_kudos_talk";

$wgJobClasses['thankAuthors'] = 'ThankAuthorsJob';

$wgResourceModules[ 'ext.wikihow.thankAuthors' ] = [
	'scripts' => [ 'thankauthors.js' ],
	'dependencies' => [ 'oojs-ui-core', 'oojs-ui-widgets', 'oojs-ui-windows' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/ThankAuthors',
	'position' => 'bottom',
	'targets' => [ 'desktop', 'mobile' ],
	'messages' => [
		'enjoyed-reading-article',
		'enjoyed-reading-article-anon',
		'send-kudos',
		'send-kudos-error',
		'send-kudos-submit',
		'send-kudos-cancel',
		'send-kudos-done',
		'thank-you-kudos',
		'howto'
	]
];
