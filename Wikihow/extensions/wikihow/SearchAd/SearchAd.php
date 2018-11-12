<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['SearchAd'] = dirname(__FILE__) . '/SearchAd.class.php';

$wgSpecialPages['SearchAd'] = 'SearchAd';
$wgExtensionMessagesFiles['SearchAd'] = dirname(__FILE__) . '/SearchAd.i18n.php';
