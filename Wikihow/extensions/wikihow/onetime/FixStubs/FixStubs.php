<?php

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'FixStubs',
	'author' => 'Alberto',
	'description' => "See LH #3084",
);

$wgSpecialPages['FixStubs'] = 'FixStubsSpecialPage';
$wgAutoloadClasses['FixStubsSpecialPage'] = __DIR__ . '/FixStubs.class.php';
