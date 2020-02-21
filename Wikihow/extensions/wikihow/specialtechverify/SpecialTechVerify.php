<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Special Tech Testing',
	'author' => 'Aaron',
	'description' => 'tool to help people test tech articles',
);

$wgSpecialPages['SpecialTechVerify'] = 'SpecialTechVerify';
$wgAutoloadClasses['SpecialTechVerify'] = __DIR__ . '/SpecialTechVerify.body.php';
$wgMessagesDirs['SpecialTechVerify'] = __DIR__ . '/i18n';

$wgLogTypes[] = 'test_tech_articles';
$wgLogNames['test_tech_articles'] = 'test_tech_articles';
$wgLogHeaders['test_tech_articles'] = 'test_tech_articles';

$wgResourceModules['ext.wikihow.specialtechverify'] = array(
	'scripts' => array('specialtechverify.js'),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialtechverify',
	'targets' => array( 'desktop', 'mobile' ),
	'dependencies' => [
		'wikihow.common.querybuilder',
		'jquery.cookie'
	],
);

$wgResourceModules['ext.wikihow.specialtechverify.styles'] = array(
	'styles' => array(
		'specialtechverify.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialtechverify',
	'position' => 'top',
	'targets' => array( 'desktop' )
);

$wgExtensionMessagesFiles['SpecialTechVerifyAliases'] = __DIR__ . '/SpecialTechVerify.alias.php';
