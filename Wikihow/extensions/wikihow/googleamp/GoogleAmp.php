<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['GoogleAmp'] = __DIR__ . '/GoogleAmp.class.php';
$wgAutoloadClasses['GoogleAmpAds'] = __DIR__ . '/GoogleAmpAds.class.php';
$wgHooks['MathFormulaPostRender'][] = array('GoogleAmp::mathHook');
$wgHooks['TitleSquidURLs'][] = array('GoogleAmp::onTitleSquidURLsPurgeVariants');
$wgHooks['ShowArticleTabs'][] = ['GoogleAmp::onShowArticleTabs'];
$wgParserOutputHooks['ampEmbedVideoParserOutputHook'] = 'GoogleAmp::onAmpEmbedVideoParserOutputHook';
