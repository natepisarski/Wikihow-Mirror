<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ThankAuthors',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'A way for users to leave fan mail on authors user_kudos page',
);

$wgSpecialPages['ThankAuthors'] = 'ThankAuthors';
$wgAutoloadClasses['ThankAuthors'] = dirname( __FILE__ ) . '/ThankAuthors.body.php';
$wgAutoloadClasses['ThankAuthorsJob'] = dirname( __FILE__ ) .'/ThankAuthorsJob.php';

define('NS_USER_KUDOS', 18);
define('NS_USER_KUDOS_TALK', 19);

$wgExtraNamespaces[NS_USER_KUDOS] = "User_kudos";
$wgExtraNamespaces[NS_USER_KUDOS_TALK] = "User_kudos_talk";

$wgJobClasses['thankAuthors'] = 'ThankAuthorsJob';
