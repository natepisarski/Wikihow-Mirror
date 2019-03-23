<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['QAtest'] = __DIR__ . '/QAtest.class.php';

$wgHooks['ProcessArticleHTMLAfter'][] = array('QAtest::onProcessArticleHTMLAfter');

$wgResourceModules['ext.wikihow.q_and_a'] = array(
	'scripts' => 'q_and_a.js',
	'styles' => 'qatest.css',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/qatest',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);
