<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['SchemaMarkup'] = __DIR__ . '/SchemaMarkup.class.php';
$wgAutoloadClasses['YouTubeInfoJob'] = __DIR__ . '/YouTubeInfoJob.class.php';
$wgHooks['AfterGoodRevisionUpdated'][] = array('SchemaMarkup::onAfterGoodRevisionUpdated');
$wgHooks['ArticlePurge'][] = array('SchemaMarkup::beforeArticlePurge');
$wgJobClasses['YouTubeInfoJob'] = 'YouTubeInfoJob';