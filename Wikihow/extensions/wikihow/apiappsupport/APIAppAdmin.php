<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['APIAppAdmin'] = 'APIAppAdmin';
$wgAutoloadClasses['APIAppAdmin'] = dirname( __FILE__ ) . '/APIAppAdmin.body.php';
