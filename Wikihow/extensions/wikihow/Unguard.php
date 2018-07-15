<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();


$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Unguard',
    'author' => 'Scott',
    'description' => 'Reverse all votes by a user',
);

$wgSpecialPages['Unguard'] = 'Unguard';
$wgAutoloadClasses['Unguard'] = dirname( __FILE__ ) . '/Unguard.body.php';

