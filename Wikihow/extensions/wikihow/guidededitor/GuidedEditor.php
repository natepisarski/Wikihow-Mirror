<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['GuidedEditor'] = __DIR__ . '/GuidedEditor.class.php';
$wgAutoloadClasses['GuidedEditorHelper'] = __DIR__ . '/GuidedEditorHelper.class.php';

$wgHooks['CustomEditor'][] = 'GuidedEditor::onCustomEdit';
$wgHooks['MediaWikiPerformAction'][] = 'GuidedEditor::onMediaWikiPerformAction';
$wgHooks['EditPage::showStandardInputs:options'][] = 'GuidedEditor::addHiddenFormInputs';

$wgExtensionMessagesFiles['GuidedEditor'] = __DIR__ . '/GuidedEditor.i18n.php';

$wgResourceModules['ext.wikihow.guided_editor_styles'] = [
	'styles' => [
		'guidededitor/guidededitor.css',
		'winpop.css',
		'video/importvideo.css',
		'cattool/categorizer.css',
		'cattool/categorizer_editpage.css',
	],
	'group' => 'prio2',
	'localBasePath' => __DIR__ . '/..',
	'remoteExtPath' => 'wikihow',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];

$wgResourceModules['ext.wikihow.guided_editor'] = [
	'scripts' => [
		'guidededitor/guidededitor_events.js',
		'winpop.js',
		'video/previewvideo.js',
		'../../skins/common/ac.js',
		'video/importvideo.js',
	],
	'dependencies' => [ 'ext.wikihow.desktop_base' ],
	'localBasePath' => __DIR__ . '/..',
	'remoteExtPath' => 'wikihow',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];

$wgResourceModules['ext.wikihow.guidededitor_quicktips_styles'] = [
	'styles' => 'guidededitor_quicktips.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/guidededitor',
	'position' => 'top',
	'targets' => [ 'desktop' ]
];
