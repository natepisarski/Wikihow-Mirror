<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'EditFinder',
	'author' => 'Scott Cushman',
	'description' => 'Tool for experienced users to edit articles that need it.',
);

$wgSpecialPages['EditFinder'] = 'EditFinder';
$wgAutoloadClasses['EditFinder'] = dirname( __FILE__ ) . '/EditFinder.body.php';
$wgExtensionMessagesFiles['EditFinder'] = dirname(__FILE__) . '/EditFinder.i18n.php';

$wgLogTypes[] = 'EF_format';
$wgLogNames['EF_format'] = 'editfinder_format';
$wgLogHeaders['EF_format'] = 'editfindertext_format';

// $wgLogTypes[] = 'EF_stub';
// $wgLogNames['EF_stub'] = 'editfinder_stub';
// $wgLogHeaders['EF_stub'] = 'editfindertext_stub';

$wgLogTypes[] = 'EF_topic';
$wgLogNames['EF_topic'] = 'editfinder_topic';
$wgLogHeaders['EF_topic'] = 'editfindertext_topic';

$wgLogTypes[] = 'EF_cleanup';
$wgLogNames['EF_cleanup'] = 'editfinder_cleanup';
$wgLogHeaders['EF_cleanup'] = 'editfindertext_cleanup';

// Log type names can only be 10 chars
$wgLogTypes[] = 'EF_copyedi';
$wgLogNames['EF_copyedi'] = 'editfinder_copyedit';
$wgLogHeaders['EF_copyedi'] = 'editfindertext_copyedit';

$wgResourceModules['ext.wikihow.greenhouse'] = array(
	'scripts' => array(
		'../common/mousetrap.min.js',
		'../common/jquery.cookie.js',
		'../../../skins/common/editor_script.js',
		'../../../skins/common/preview.js',
		'editfinder.js',
	),
	'messages' => array(
		'app-name',
		'change_topics',
		'gh_topic_chosen',
		'gh_interests'
	),
	'dependencies' => 'jquery.ui.dialog',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/editfinder',
	'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.greenhouse.styles'] = array(
	'styles' => 'editfinder.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/editfinder',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);
