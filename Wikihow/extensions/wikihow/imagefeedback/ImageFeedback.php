<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Image Feedback',
	'author' => 'Jordan Small',
	'description' => 'Allows for feedback on article images on the site',
);

$wgSpecialPages['ImageFeedback'] = 'ImageFeedback';
$wgAutoloadClasses['ImageFeedback'] = __DIR__ . '/ImageFeedback.body.php';
$wgExtensionMessagesFiles['ImageFeedback'] = __DIR__ . '/ImageFeedback.i18n.php';

$wgResourceModules['ext.wikihow.imagefeedbackadmin'] = [
	'scripts' => ['imagefeedbackadmin.js'],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/imagefeedback',
	'position' => 'bottom',
	'targets' => ['desktop'],
	'dependencies' => ['ext.wikihow.common_top'],
];
