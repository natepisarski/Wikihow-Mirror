<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['UserCompletedImages'] = 'UserCompletedImages';
$wgAutoloadClasses['UserCompletedImages'] = __DIR__ . '/UserCompletedImages.body.php';
$wgExtensionMessagesFiles['UserCompletedImages'] = __DIR__ . '/UserCompletedImages.i18n.php';
$wgExtensionMessagesFiles['UserCompletedImagesAliases'] = __DIR__ . '/UserCompletedImages.alias.php';

$wgHooks['BeforePageDisplay'][] = ['UserCompletedImages::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.usercompletedimages'] = array(
	'localBasePath' => __DIR__,
	'position' => 'bottom',
	'targets' => array( 'desktop' ),
	'remoteExtPath' => 'wikihow/usercompletedimages',
	'scripts' => array(
		'../common/swipebox/jquery.swipebox.js',
		'../common/fileupload/load-image.all.min.js',
		'../common/fileupload/jquery.ui.fuwidget.js',
		'../common/fileupload/jquery.fileupload.js',
		'../common/fileupload/jquery.fileupload-process.js',
		'../common/fileupload/jquery.fileupload-image.js',
		'../common/fileupload/jquery.fileupload-validate.js',
		'ucifeedback.js',
		'usercompletedimagesupload.js',
		'usercompletedimages.js',
	),
	'styles' => array(
		'../common/swipebox/swipebox.css',
		'usercompletedimages.css',
		'ucifeedback.css',
	),
	'messages' => array(
		'uploaded_timeago'
	),
	'dependencies' => array('mediawiki.user'),
);

$wgResourceModules['mobile.wikihow.uci'] = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/usercompletedimages',
	'scripts' => [
		'../common/fileupload/load-image.all.min.js',
		'../common/fileupload/jquery.ui.fuwidget.js',
		'../common/fileupload/jquery.fileupload.js',
		'../common/fileupload/jquery.fileupload-process.js',
		'../common/fileupload/jquery.fileupload-image.js',
		'../common/fileupload/jquery.fileupload-validate.js',
		'../common/swipebox/jquery.swipebox.js',
		'usercompletedimages.js',
	],
	'dependencies' => [
		'mobile.wikihow',
		'mediawiki.user'
	],
	'targets' => [ 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['mobile.wikihow.uci_after'] = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/usercompletedimages',
	'scripts' => [
		'usercompletedimagesupload.js'
	],
	'targets' => [ 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['mobile.wikihow.uci_styles'] = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/usercompletedimages',
	'styles' => [
		'../common/swipebox/swipebox.css',
		'usercompletedimages.css'
	],
	'targets' => [ 'mobile' ],
	'position' => 'top'
];
