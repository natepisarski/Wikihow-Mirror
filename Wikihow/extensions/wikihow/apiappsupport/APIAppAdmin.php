<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['APIAppAdmin'] = 'APIAppAdmin';
$wgAutoloadClasses['APIAppAdmin'] = __DIR__ . '/APIAppAdmin.body.php';
