<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['GoogleAmp'] = __DIR__ . '/GoogleAmp.class.php';
$wgHooks['MathFormulaPostRender'][] = array('GoogleAmp::mathHook');
$wgHooks['TitleSquidURLs'][] = array('GoogleAmp::onTitleSquidURLsPurgeVariants');
$wgHooks['ArticleFromTitle'][] = array('GoogleAmp::onArticleFromTitle');
$wgHooks['ShowArticleTabs'][] = ['GoogleAmp::onShowArticleTabs'];
$wgParserOutputHooks['ampEmbedVideoParserOutputHook'] = 'GoogleAmp::onAmpEmbedVideoParserOutputHook';
