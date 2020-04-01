<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Special Tech Verify Admin',
	'author' => 'Aaron',
	'description' => 'admin for tech verify tool',
);

$wgSpecialPages['SpecialTechVerifyAdmin'] = 'SpecialTechVerifyAdmin';
$wgAutoloadClasses['SpecialTechVerifyAdmin'] = __DIR__ . '/SpecialTechVerifyAdmin.body.php';
$wgMessagesDirs['SpecialTechVerifyAdmin'] = __DIR__ . '/i18n';

$wgResourceModules['ext.wikihow.specialtechverifyadmin'] = array(
	'targets' => array( 'desktop' ),
	'remoteExtPath' => 'wikihow/specialtechverify',
	'scripts' => array(
		'specialtechverifyadmin.js',
		'../common/jquery.simplemodal.1.4.4.min.js'
		),
	'localBasePath' => __DIR__ . '/',
	'messages' => [
		'stva_batch_label',
		'stva_platform_label',
		'stva_addnew_list',
		'stva_addnew_submit',
		'stva_addnew_done',
		'stva_addnew_list_example',
		'stva_err_no_batch_name',
		'stva_err_no_platform',
		'stva_err_no_articles'
	]
);

$wgResourceModules['ext.wikihow.specialtechverifyadmin.styles'] = array(
	'styles' => array(
		'specialtechverifyadmin.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialtechverify',
	'position' => 'top',
	'targets' => array( 'desktop' )
);

$wgExtensionMessagesFiles['SpecialTechVerifyAdminAliases'] = __DIR__ . '/SpecialTechVerifyAdmin.alias.php';
