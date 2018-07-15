<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['QAtest'] = dirname(__FILE__) . '/QAtest.class.php';

$wgHooks['ProcessArticleHTMLAfter'][] = array('QAtest::onProcessArticleHTMLAfter');

$wgResourceModules['ext.wikihow.q_and_a'] = array(
	'scripts' => 'q_and_a.js',
	'styles' => 'qatest.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/qatest',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);