<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Image Feedback',
	'author' => 'Jordan Small',
	'description' => 'Allows for feedback on article images on the site',
);

$wgSpecialPages['ImageFeedback'] = 'ImageFeedback';
$wgAutoloadClasses['ImageFeedback'] = dirname(__FILE__) . '/ImageFeedback.body.php';
$wgExtensionMessagesFiles['ImageFeedback'] = dirname(__FILE__) . '/ImageFeedback.i18n.php';
