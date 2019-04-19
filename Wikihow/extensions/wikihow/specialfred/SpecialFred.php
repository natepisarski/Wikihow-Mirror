<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'new fred page',
	'author' => 'Aaron',
	'description' => 'admin for fred tool',
);

$wgSpecialPages['SpecialFred'] = 'SpecialFred';
$wgAutoloadClasses['SpecialFred'] = __DIR__ . '/SpecialFred.body.php';
$wgMessagesDirs['SpecialFred'] = __DIR__ . '/i18n';

$wgResourceModules['ext.wikihow.specialfred'] = array(
	'targets' => array( 'desktop' ),
	'remoteExtPath' => 'wikihow/specialfred',
	'scripts' => array(
		'specialfred.js',
		'../common/jquery.simplemodal.1.4.4.min.js'
		),
	'localBasePath' => __DIR__ . '/',
	// TODO this
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

$wgResourceModules['ext.wikihow.specialfred.styles'] = array(
	'styles' => array(
		'specialfred.less',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__ . '/' ,
	'remoteExtPath' => 'wikihow/specialfred',
	'position' => 'top',
	'targets' => array( 'desktop' )
);

$wgExtensionMessagesFiles['SpecialFredAliases'] = __DIR__ . '/SpecialFred.alias.php';
