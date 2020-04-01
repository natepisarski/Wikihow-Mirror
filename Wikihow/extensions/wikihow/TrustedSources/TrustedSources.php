<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Trusted Sources',
	'author' => 'Bebeth Steudel',
	'description' => 'Trusted sources for references section.'
);

$wgSpecialPages['AdminTrustedSources'] = 'AdminTrustedSources';

$wgAutoloadClasses['TrustedSources']            = __DIR__ . '/TrustedSources.class.php';
$wgAutoloadClasses['AdminTrustedSources']            = __DIR__ . '/AdminTrustedSources.body.php';

$wgMessagesDirs['TrustedSources'] = __DIR__ . '/i18n/';

$wgResourceModules['ext.wikihow.admin_trusted_sources'] = [
	'scripts' => ['admintrustedsources.js'],
	'localBasePath' => __DIR__ . "/scripts" ,
	'remoteExtPath' => 'wikihow/TrustedSources/scripts',
	'targets' => ['desktop'],
	'dependencies' => [
		'wikihow.common.jquery.download',
		'wikihow.common.aim'
	]
];

$wgResourceModules['ext.wikihow.admin_trusted_sources.styles'] = [
	'styles' => ['admintrustedsources.less'],
	'localBasePath' => __DIR__ . "/styles" ,
	'remoteExtPath' => 'wikihow/TrustedSources/styles',
	'targets' => ['desktop']
];

$wgResourceModules['ext.wikihow.trusted_sources.scripts'] = [
	'scripts' => ['trustedsources.js'],
	'localBasePath' => __DIR__ . "/scripts" ,
	'remoteExtPath' => 'wikihow/TrustedSources/scripts',
	'targets' => ['mobile']
];

$wgResourceModules['ext.wikihow.trusted_sources.styles'] = [
	'styles' => ['trustedsources.less'],
	'localBasePath' => __DIR__ . "/styles" ,
	'remoteExtPath' => 'wikihow/TrustedSources/styles',
	'targets' => ['mobile']
];

$wgHooks['BeforePageDisplay'][] = 'TrustedSources::onBeforePageDisplay';

/********
CREATE TABLE `trusted_sources` (
	`ts_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`ts_source` VARBINARY(255) NOT NULL DEFAULT '',
	`ts_name` VARBINARY(255) NOT NULL DEFAULT '',
	`ts_description` BLOB,
	KEY (`ts_source`)
);
******/