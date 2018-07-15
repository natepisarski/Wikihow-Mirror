<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['GuidedEditor'] = __DIR__ . '/GuidedEditor.class.php';
$wgAutoloadClasses['GuidedEditorHelper'] = __DIR__ . '/GuidedEditorHelper.class.php';

$wgHooks['CustomEditor'][] = 'GuidedEditor::onCustomEdit';
$wgHooks['MediaWikiPerformAction'][] = 'GuidedEditor::onMediaWikiPerformAction';
$wgHooks['EditPage::showStandardInputs:options'][] = 'GuidedEditor::addHiddenFormInputs';

$wgExtensionMessagesFiles['GuidedEditor'] = __DIR__ . '/GuidedEditor.i18n.php';

// This global declaration is needed for INTL Special:CreatePage, since the
// GuidedEditor module is included is a funny way there
global $wgResourceModulesDesktopBoiler;

$wgResourceModules['ext.wikihow.guided_editor'] = $wgResourceModulesDesktopBoiler + [
	'styles' => [
		'guidededitor/guidededitor.css',
		'winpop.css',
		'video/importvideo.css',
		'cattool/categorizer.css',
		'cattool/categorizer_editpage.css',
	],
	'scripts' => [
		'guidededitor/guidededitor_events.js',
		'winpop.js',
		'video/previewvideo.js',
		'../../skins/common/ac.js',
		'video/importvideo.js',
	]
];

$wgResourceModules['ext.wikihow.guidededitor_quicktips'] = [
	'styles' => 'guidededitor_quicktips.css',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];
