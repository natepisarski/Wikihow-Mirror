<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();


$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Unguard',
    'author' => 'Scott',
    'description' => 'Reverse all QC votes by a user',
);

$wgSpecialPages['Unguard'] = 'Unguard';
$wgAutoloadClasses['Unguard'] = __DIR__ . '/Unguard.body.php';
