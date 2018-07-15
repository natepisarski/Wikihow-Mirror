<?php

if ( !defined('MEDIAWIKI') ) die();

$wgSpecialPages['BuildWikihowModal'] = 'BuildWikihowModal';
$wgAutoloadClasses['BuildWikihowModal'] = __DIR__ . '/WikihowModal.body.php';

$wgExtensionMessagesFiles['WikihowModal'] = __DIR__ . '/Modal.i18n.php';
$wgExtensionMessagesFiles['BuildWikihowModalAliases'] = __DIR__ . '/WikihowModal.alias.php';

$wgResourceModules['ext.wikihow.first_edit_modal'] = array(
	'scripts' => 'first_edit.js',
	'styles' => array(
		'first_edit.css',
		'../common/font-awesome-4.2.0/css/font-awesome.min.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.helpfulness_followup_modal'] = array(
	'scripts' => 'helpfulness_followup.js',
	'styles' => array(
		'modal.css',
		'helpfulness_followup.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'position' => 'bottom',
	'targets' => array( 'desktop' )
);

$wgResourceModules['ext.wikihow.expertise_modal'] = array(
	'scripts' => 'expertise.js',
	'styles' => array(
		'modal.css',
		'expertise.css'
	),
	'messages' => array(
		'expertise_interests_hdr',
		'expertise_sorry'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'position' => 'bottom',
	'dependencies' => array(
		'jquery.ui.autocomplete',
	),
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.expertise_modal_2'] = array(
	'scripts' => 'expertise_2.js',
	'styles' => array(
		'modal.css',
		'expertise.css',
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.printview_modal'] = array(
	'scripts' => array(
		'../common/jquery.simplemodal.1.4.4.min.js',
		'printview.js',
	),
	'styles' => array(
		'modal.css',
		'printview.css',
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'position' => 'bottom',
	'targets' => array( 'desktop' )
);

$wgResourceModules['ext.wikihow.graphs_modal'] = array(
	'scripts' => array(
		'../common/jquery.simplemodal.1.4.4.min.js',
	),
	'styles' => array(
		'modal.css',
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'position' => 'bottom',
	'targets' => array('desktop'),
);

$wgResourceModules['ext.wikihow.flag_as_details'] = array(
	'scripts' => array(
		'../common/jquery.simplemodal.1.4.4.min.js',
		'flag_as_details.js'
	),
	'styles' => array(
		'modal.css',
		'flag_as_details.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'position' => 'bottom',
	'targets' => array( 'desktop' )
);

$wgResourceModules['ext.wikihow.discuss_tab'] = array(
	'scripts' => array(
		'../common/jquery.simplemodal.1.4.4.min.js',
		'discuss_tab/discuss_tab.js'
	),
	'styles' => array(
		'modal.css',
		'discuss_tab/discuss_tab.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/modal',
	'dependencies' => array(
		'ext.wikihow.common_bottom'
	),
	'position' => 'bottom',
	'targets' => array( 'desktop' )
);
