<?php

if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Image Upload',
	'author' => 'wikiHow',
	'description' => 'Provides an easy way of uploading and adding images to articles',
);

$wgSpecialPages['ImageUploader'] = 'ImageUploader';
$wgAutoloadClasses['ImageUploader'] = __DIR__ . '/SpecialImageUploader.php';
$wgMessagesDirs['ImageUpload'] = __DIR__ . '/i18n';

$wgHooks['EditPage::showEditForm:initial'][] = ['ImageUploader::onEditPageShowEditFormInitial'];
$wgHooks['UploadStashGetFile'][] = ['ImageUploader::onUploadStashGetFile'];
$wgHooks['UploadStashProcessFile'][] = ['ImageUploader::onUploadStashProcessFile'];

$wgResourceModules['ext.wikihow.imageupload'] =
    $wgResourceModulesDesktopBoiler + [
		'styles' => [ 'imageupload/imageupload.css' ],
		'scripts' => [
			'imageupload/cursorhelper.js',
			'imageupload/ext/aim.js',
			'imageupload/imageupload.js'
		],
		'messages' => [
			'eiu-network-error', 'eiu-user-name-not-found-error', 'eiu-insert',
			'eiu-preview', 'cancel', 'added-image', 'next-page-link', 'prev-page-link',
		],
	];
