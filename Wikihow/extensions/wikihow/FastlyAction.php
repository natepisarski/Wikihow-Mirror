<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['FastlyAction'] = __DIR__ . '/FastlyAction.body.php';
$wgAutoloadClasses['FastlyActionJob'] = __DIR__ .'/FastlyActionJob.php';

$wgJobClasses['FastlyAction'] = 'FastlyActionJob';

