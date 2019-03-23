<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['SocialStamp'] = __DIR__ . '/SocialStamp.class.php';
$wgMessagesDirs['SocialStamp'] = __DIR__ . '/i18n';

$wgHooks['MobilePreRenderPreContent'][] = ['SocialStamp::addMobileByline'];
$wgHooks['ProcessArticleHTMLAfter'][] = ['SocialStamp::addDesktopByline'];
