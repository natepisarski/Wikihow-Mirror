<?php
if ( !defined('MEDIAWIKI') ) die();
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'PageHelpfulness',
	'author' => 'Aaron Von Gutierrez',
	'description' => 'Helfulness side widget for display article rating info and feedback',
);
$wgSpecialPages['PageHelpfulness'] = 'PageHelpfulness';
$wgAutoloadClasses['PageHelpfulness'] = __DIR__ . '/PageHelpfulness.body.php';

$wgResourceModules['ext.wikihow.pagehelpfulness_styles'] =
	$wgResourceModulesDesktopBoilerStyles + [
		'styles' => [ 'pagehelpfulness/pagehelpfulness.css' ]
	];

$wgResourceModules['ext.wikihow.pagehelpfulness_staff'] =
	$wgResourceModulesDesktopBoiler + [
		'scripts' => [ 'pagehelpfulness/phstaff.js' ]
	];
