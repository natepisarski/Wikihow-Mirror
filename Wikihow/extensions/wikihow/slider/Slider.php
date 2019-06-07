<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['special'][] = array(
	'name' => 'Slider',
	'author' => 'Scott Cushman',
	'description' => 'The box that slides in to prompt the user for more stuff.',
);

$wgAutoloadClasses['Slider'] = __DIR__ . '/Slider.class.php';
$wgExtensionMessagesFiles['Slider'] = __DIR__ . '/Slider.i18n.php';

$wgResourceModules['ext.wikihow.slider'] =
	$wgResourceModulesDesktopBoiler + [
		'styles' => [ 'slider/slider.css' ],
		'scripts' => [ 'slider/slider.js' ],
		'messages' => [
			'slider_cta_video',
			'slider_url_text_video',
			'slider_cta_category',
			'slider_url_text_category',
		]
	];
