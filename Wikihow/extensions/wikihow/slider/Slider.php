<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['special'][] = array(
	'name' => 'Slider',
	'author' => 'Scott Cushman',
	'description' => 'The box that slides in to prompt the user for more stuff.',
);

$wgAutoloadClasses['Slider'] = __DIR__ . '/Slider.class.php';
$wgExtensionMessagesFiles['Slider'] = __DIR__ . '/Slider.i18n.php';

$wgResourceModules['ext.wikihow.slider_styles'] = [
	'styles' => [ 'slider.css' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/slider',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.slider'] = [
	'scripts' => [ 'slider.js' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/slider',
	'messages' => [
		'slider_cta_video',
		'slider_url_text_video',
		'slider_cta_category',
		'slider_url_text_category',
		'newsletter_url',
		'slider_cta_newsletter',
		'slider_newsletter',
		'slider_url_text_newsletter'
	],
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];
