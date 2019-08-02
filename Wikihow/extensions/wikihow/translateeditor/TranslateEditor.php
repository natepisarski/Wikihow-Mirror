<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'TranslateEditor',
	'author' => 'Gershon Bialer',
	'description' => 'Translators will have a special editor with language link'
);

$wgSpecialPages['TranslateEditor'] = 'TranslateEditor';

$wgAutoloadClasses['TranslateEditor'] = __DIR__ . '/TranslateEditor.body.php';
$wgAutoloadClasses['EditMapper\TranslatorEditMapper'] = __DIR__ . '/TranslatorEditMapper.class.php';

$wgHooks['CustomEditor'][] = 'TranslateEditor::onCustomEdit';

$wgResourceModules['ext.wikihow.translateeditor'] = [
    'scripts' => [ 'translateeditor.js' ],
    'localBasePath' => __DIR__,
    'targets' => [ 'desktop' ],
    'dependencies' => [ 'ext.wikihow.desktop_base', 'jquery' ],
    'remoteExtPath' => 'wikihow/translateeditor',
    'position' => 'top',
];
