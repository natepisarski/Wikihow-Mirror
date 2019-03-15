<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['other'][] = array(
    'name' => 'UserTiming',
    'author' => 'Alberto Burgos',
    'description' => "Provides the ability to time events on the website",
);

$wgAutoloadClasses['UserTiming'] = __DIR__ . '/UserTiming.class.php';

$wgHooks['AddTopEmbedJavascript'][] = 'UserTiming::getJavascriptPaths';
