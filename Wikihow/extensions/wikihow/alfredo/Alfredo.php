<?php
if ( ! defined( 'MEDIAWIKI' ) )
  die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Alfredo',
	'author' => 'Gershon Bialer',
	'description' => 'Add wikiphotos to international',
);

$wgSpecialPages['Alfredo'] = 'Alfredo';
$wgAutoloadClasses['Alfredo'] = dirname(__FILE__) . '/Alfredo.body.php';

