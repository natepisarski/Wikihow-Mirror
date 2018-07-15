<?php

if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 * An extension that allows users to upload an image while on the edit page
 * without leaving that page.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:ImageUpload-Extension Documentation
 * @author Reuben Smith <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Image Upload',
	'author' => 'wikiHow',
	'description' => 'Provides an easy way of uploading and adding images to articles',
	'url' => 'http://www.wikihow.com/WikiHow:ImageUpload-Extension',
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
