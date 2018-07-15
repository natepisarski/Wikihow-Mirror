<?php
if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}
$wgExtensionCredits['parserhook'][] = array(
    'name'=>'ImageCaption',
    'author'=>'Titus',
    'description'=>'Adds a parser function to add captions to images and videos',
);

$wgHooks['LanguageGetMagic'][] = 'ImageCaption::languageGetMagic';
$wgAutoloadClasses['ImageCaption'] = dirname(__FILE__) . '/ImageCaption.class.php';
$wgHooks['ParserFirstCallInit'][] = 'ImageCaption::setParserFunction';
$wgHooks['BeforePageDisplay'][] = 'ImageCaption::onBeforePageDisplay';
$wgHooks['AddTopEmbedJavascript'][] = 'ImageCaption::getJavascriptPaths';
