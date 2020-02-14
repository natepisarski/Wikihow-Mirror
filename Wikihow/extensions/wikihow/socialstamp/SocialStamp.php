<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['SocialStamp'] = __DIR__ . '/SocialStamp.class.php';
$wgMessagesDirs['SocialStamp'] = __DIR__ . '/i18n';

$wgHooks['MobilePreRenderPreContent'][] = ['SocialStamp::addMobileByline'];
$wgHooks['ProcessArticleHTMLAfter'][] = ['SocialStamp::addDesktopByline'];
$wgHooks['MobilePreRenderPreContent'][] = ['SocialStamp::addMobileTrustBanner'];
$wgHooks['ProcessArticleHTMLAfter'][] = ['SocialStamp::addDesktopTrustBanner'];

$wgResourceModules['ext.wikihow.reader_success_stories_dialog'] = array(
	'styles' => ['css/reader_success_stories_dialog.less'],
	'scripts' => ['js/reader_success_stories_dialog.js'],
	'targets' => array( 'desktop', 'mobile' ),
	'localBasePath' => __DIR__ . "/reader_success_stories_dialog",
	'dependencies' => array(
		'ext.wikihow.magnificpopup'
	),
	'remoteExtPath' => 'wikihow/socialstamp/reader_success_stories_dialog',
	'messages' => []
);

$wgMessagesDirs['reader_success_stories_dialog'] = __DIR__ . '/reader_success_stories_dialog/i18n';
