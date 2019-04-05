<?php
if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['parserhook'][] = array(
    'name'=>'WHVid',
    'author'=>'Jordan Small',
    'description'=>'Adds a parser function to embed wikihow-created videos',
);

$wgExtensionMessagesFiles['WHVid'] = __DIR__ . '/WHVid.i18n.php';
$wgAutoloadClasses['WHVid'] = __DIR__ . '/WHVid.body.php';
$wgHooks['LanguageGetMagic'][] = 'WHVid::languageGetMagic';
$wgHooks['BeforePageDisplay'][] = 'WHVid::onBeforePageDisplay';
$wgHooks['AddTopEmbedJavascript'][] = 'WHVid::onAddTopEmbedJavascript';
$wgHooks['DesktopTopStyles'][] = ['WHVid::addCSS'];
$wgHooks['MobileEmbedStyles'][] = ['WHVid::addCSS'];
$wgHooks['AddMobileTOCItemData'][] = ['WHVid::onAddMobileTOCItemData'];
$wgHooks['ProcessArticleHTMLAfter'][] = ['WHVid::onProcessArticleHTMLAfter'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['WHVid::onProcessArticleHTMLAfter'];

if (defined('MW_SUPPORTS_PARSERFIRSTCALLINIT')) {
    $wgHooks['ParserFirstCallInit'][] = 'WHVid::setParserFunction';
} else {
	$wgExtensionFunctions[] = "WHVid::setParserFunction";
}
// Warning: until this module is used only with pages that have wikivideo, include no other messages/javascript/css with it because it will be included with all pages
$wgResourceModules['ext.wikihow.wikivid'] = array(
	'messages' => array('ten-second-video')	,
	'position' => 'top',
	'localBasePath' => __DIR__
);
