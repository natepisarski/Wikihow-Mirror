<?php

$wgExtensionCredits['AdminCategoryDescriptions'][] = array(
	'name' => '',
	'author' => 'Bebeth Steudel',
	'description' => '',
);

$wgSpecialPages['AdminCategoryDescriptions'] = 'AdminCategoryDescriptions';
$wgAutoloadClasses['AdminCategoryDescriptions'] = __DIR__ . '/AdminCategoryDescriptions.body.php';

$wgResourceModules['wikihow.admincategorydescriptions'] = [
	'scripts' => ['admincategorydescriptions.js'],
	'localBasePath' => __DIR__ ,
	'remoteExtPath' => 'wikihow/categories/admin',
	'position' => 'bottom',
	'targets' => ['desktop'],
	'dependencies' => [
		'wikihow.common.jquery.download',
		'wikihow.common.aim'
	]
];
