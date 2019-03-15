<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['TPCoachAdmin'] = 'TPCoachAdmin';
$wgAutoloadClasses['TPCoachAdmin'] = __DIR__ . '/TPCoachAdmin.body.php';
