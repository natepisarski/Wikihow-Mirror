<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Newcontributors',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'A list of users who have made their first contribution to the site',
);

$wgExtensionMessagesFiles['Newcontributors'] = dirname(__FILE__) . '/Newcontributors.i18n.php';
$wgExtensionMessagesFiles['NewcontributorsAliases'] = dirname(__FILE__) . '/Newcontributors.alias.php';

$wgSpecialPages['Newcontributors'] = 'Newcontributors';
$wgAutoloadClasses['Newcontributors'] = dirname( __FILE__ ) . '/Newcontributors.body.php';

