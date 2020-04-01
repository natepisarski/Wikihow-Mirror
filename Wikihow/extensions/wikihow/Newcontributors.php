<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'NewContributors',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'A list of users who have made their first contribution to the site',
);

$wgExtensionMessagesFiles['NewContributors'] = __DIR__ . '/Newcontributors.i18n.php';
$wgExtensionMessagesFiles['NewContributorsAliases'] = __DIR__ . '/Newcontributors.alias.php';

$wgSpecialPages['NewContributors'] = 'NewContributors';
$wgAutoloadClasses['NewContributors'] = __DIR__ . '/Newcontributors.body.php';
