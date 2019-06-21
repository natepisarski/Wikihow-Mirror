<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['SchemaMarkup'] = __DIR__ . '/SchemaMarkup.class.php';
$wgHooks['AfterGoodRevisionUpdated'][] = array('SchemaMarkup::onAfterGoodRevisionUpdated');
$wgHooks['ArticlePurge'][] = array('SchemaMarkup::beforeArticlePurge');
