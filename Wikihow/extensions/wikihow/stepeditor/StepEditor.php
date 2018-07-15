<?php
if ( !defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['StepEditor'] = 'StepEditor';
$wgAutoloadClasses['StepEditor'] = dirname(__FILE__) . '/StepEditor.class.php';
$wgAutoloadClasses['StepEditorParser'] = dirname(__FILE__) . '/StepEditor.class.php';

$wgResourceModules['ext.wikihow.stepeditor'] = array(
	'scripts' =>
		array(
			'stepeditor.js',
			'../common/zero-clipboard/ZeroClipboard.min.js'
		),
	'styles' => array('stepeditor.css'),
	'dependencies' => array('jquery.ui.dialog'),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/stepeditor',
	'messages' => array('stepedit-active'),
);

$wgHooks['ListDefinedTags'][] = 'StepEditor::onListDefinedTags';
$wgHooks['captchaEditCallback'][] = 'StepEditorParser::onCaptchaEditCallback';