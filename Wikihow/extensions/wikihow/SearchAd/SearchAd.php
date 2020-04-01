<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['SearchAd'] = __DIR__ . '/SearchAd.class.php';

$wgSpecialPages['SearchAd'] = 'SearchAd';
$wgExtensionMessagesFiles['SearchAd'] = __DIR__ . '/SearchAd.i18n.php';
