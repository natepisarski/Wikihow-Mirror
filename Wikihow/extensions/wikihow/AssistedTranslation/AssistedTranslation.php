<?php

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AssistedTranslation',
	'description' => "Makes it easier for translators to create or update INTL articles,
		by taking the EN wikiText as the starting point, and processing it a little
		before the manual translation work begins",
);

$wgAutoloadClasses['EditorUtil'] = __DIR__ . '/common/EditorUtil.class.php';

// Special:TranslateEditor (Gershon, 2013)

$wgAutoloadClasses['TranslateEditor'] = __DIR__ . '/TranslateEditor/TranslateEditor.body.php';
$wgAutoloadClasses['EditMapper\TranslatorEditMapper'] = __DIR__ . '/TranslateEditor/TranslatorEditMapper.class.php';

$wgSpecialPages['TranslateEditor'] = 'TranslateEditor';

$wgResourceModules['ext.wikihow.translateeditor'] = [
	'scripts' => [ 'translateeditor.js' ],
	'localBasePath' => __DIR__ . '/TranslateEditor',
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery' ],
	'remoteExtPath' => 'wikihow/AssistedTranslation/TranslateEditor',
	'position' => 'top',
];

// Special:RetranslateEditor (Alberto, 2019)

$wgAutoloadClasses['RetranslateEditor'] =
$wgAutoloadClasses['RetranslateEditorHooks'] = __DIR__ . '/RetranslateEditor/RetranslateEditor.body.php';
$wgAutoloadClasses['EditMapper\RetranslatorEditMapper'] = __DIR__ . '/RetranslateEditor/RetranslatorEditMapper.class.php';

$wgSpecialPages['RetranslateEditor'] = 'RetranslateEditor';

$wgResourceModules['ext.wikihow.retranslateeditor'] = [
	'scripts' => [ 'retranslateeditor.js' ],
	'localBasePath' => __DIR__ . '/RetranslateEditor',
	'targets' => [ 'desktop' ],
	'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery' ],
	'remoteExtPath' => 'wikihow/AssistedTranslation/RetranslateEditor',
	'position' => 'top',
];

// Hooks (order matters)

$wgHooks['CustomEditor'][] = 'RetranslateEditorHooks::onCustomEdit';
$wgHooks['CustomEditor'][] = 'TranslateEditor::onCustomEdit';
