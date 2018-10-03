<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgAutoloadClasses['WikihowStatsd'] = __DIR__ . '/WikihowStatsd.body.php';

$wgHooks['PageContentSave'][] = 'WikihowStatsd::onPageContentSave';
$wgHooks['AfterFinalPageOutput'][] = 'WikihowStatsd::onAfterFinalPageOutput';
