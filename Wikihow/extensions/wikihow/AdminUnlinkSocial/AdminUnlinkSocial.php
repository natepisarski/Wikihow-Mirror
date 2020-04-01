<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'AdminUnlinkSocial',
    'author' => 'Alberto Burgos',
    'description' => "This tool can be used to unlink a user's Google or Facebook account "
                   . "from their wikiHow account",
);

$wgSpecialPages['AdminUnlinkSocial'] = 'AdminUnlinkSocial';
$wgAutoloadClasses['AdminUnlinkSocial'] = __DIR__ . '/AdminUnlinkSocial.body.php';
$wgExtensionMessagesFiles['AdminUnlinkSocial'] = __DIR__ . '/AdminUnlinkSocial.i18n.php';

$wgResourceModules['ext.wikihow.adminunlinksocial.scripts'] = array(
    'scripts'       => 'AdminUnlinkSocial.js',
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/AdminUnlinkSocial',
    'position'      => 'bottom',
    'targets'       => array('desktop', 'mobile')
);

$wgResourceModules['ext.wikihow.adminunlinksocial.styles'] = array(
    'styles'        => 'AdminUnlinkSocial.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/AdminUnlinkSocial',
    'targets'       => array('desktop', 'mobile'),
);
