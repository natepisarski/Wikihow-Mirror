<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['AnswerResponse'][] = array(
	'name' => 'AnswerResponse',
	'author' => 'Scott Cushman',
	'description' => 'Landing page for Q&A answers being helpful/unhelpful',
);

$wgSpecialPages['AnswerResponse'] = 'AnswerResponse';
$wgAutoloadClasses['AnswerResponse'] = __DIR__ . '/AnswerResponse.body.php';
$wgMessagesDirs['AnswerResponse'] = __DIR__ . '/i18n/';

$wgResourceModules['wikihow.answer_response'] = array(
	'styles' => 'answer_response.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/AnswerResponse',
	'position' => 'top',
	'targets' => array('mobile', 'desktop')
);

$wgResourceModules['wikihow.scripts.answer_response'] = array(
	'scripts' => 'answer_response.js',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/AnswerResponse',
	'position' => 'bottom',
	'messages' => [
		'qaar_thanks',
		'qaar_thanks_1'
	],
	'targets' => array('mobile', 'desktop'),
	'dependencies' => 'ext.wikihow.common_bottom'
);
