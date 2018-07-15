<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Image Removal Tool',
	'author' => 'Bebeth Steudel',
	'description' => '',
);

$wgSpecialPages['AdminImageRemoval'] = 'AdminImageRemoval';
$wgAutoloadClasses['AdminImageRemoval'] = __DIR__ . '/AdminImageRemoval.body.php';

$wgResourceModules['ext.wikihow.image_removal'] = $wgResourceModulesDesktopBoiler + [
	'scripts' => [ 'adminimageremoval.js' ] ];
