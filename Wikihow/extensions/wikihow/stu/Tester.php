<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

//
// Used to load test our infra and db specifically
//
// NOTE: this must enabled in imports.php to work
//

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Tester',
	'author' => 'Reuben',
	'description' => 'REST endpoint for load testing',
);

$wgSpecialPages['Tester'] = 'SpecialTester';
$wgAutoloadClasses['SpecialTester'] = __DIR__ . '/SpecialTester.php';
