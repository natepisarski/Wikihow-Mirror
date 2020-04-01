<?php
if ( !defined('MEDIAWIKI') ) die();
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'PageHelpfulness',
	'author' => 'Aaron Von Gutierrez',
	'description' => 'Helfulness side widget for display article rating info and feedback',
);
$wgSpecialPages['PageHelpfulness'] = 'PageHelpfulness';
$wgAutoloadClasses['PageHelpfulness'] = __DIR__ . '/PageHelpfulness.body.php';

$wgHooks['BeforePageDisplay'][] = ['PageHelpfulness::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.pagehelpfulness_styles'] =
	$wgResourceModulesResponsiveBoilerStyles + [
		'styles' => [ 'pagehelpfulness/pagehelpfulness.css' ],
		'position' => 'bottom'
	];

$wgResourceModules['ext.wikihow.pagehelpfulness_staff'] =
	$wgResourceModulesResponsiveBoiler + [
		'scripts' => [ 'pagehelpfulness/phstaff.js' ]
	];
