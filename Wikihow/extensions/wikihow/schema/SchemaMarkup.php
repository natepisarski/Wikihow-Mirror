<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['SchemaMarkup'] = dirname(__FILE__) . '/SchemaMarkup.class.php';
$wgHooks['AfterGoodRevisionUpdated'][] = array('SchemaMarkup::onAfterGoodRevisionUpdated');
