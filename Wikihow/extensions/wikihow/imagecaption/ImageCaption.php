<?php
if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}
$wgExtensionCredits['parserhook'][] = array(
    'name'=>'ImageCaption',
    'author'=>'Titus',
    'description'=>'Adds a parser function to add captions to images and videos',
);

$wgExtensionMessagesFiles['ImageCaption'] = __DIR__ . '/ImageCaption.i18n.php';
$wgAutoloadClasses['ImageCaption'] = __DIR__ . '/ImageCaption.class.php';
$wgHooks['ParserFirstCallInit'][] = 'ImageCaption::setParserFunction';
$wgHooks['BeforePageDisplay'][] = 'ImageCaption::onBeforePageDisplay';
$wgHooks['AddTopEmbedJavascript'][] = 'ImageCaption::getJavascriptPaths';
