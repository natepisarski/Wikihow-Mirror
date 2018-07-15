<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['TPCoachAdmin'] = 'TPCoachAdmin';
$wgAutoloadClasses['TPCoachAdmin'] = dirname( __FILE__ ) . '/TPCoachAdmin.body.php';
