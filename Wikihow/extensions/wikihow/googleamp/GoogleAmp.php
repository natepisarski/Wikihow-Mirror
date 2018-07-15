<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['GoogleAmp'] = dirname(__FILE__) . '/GoogleAmp.class.php';
$wgHooks['MathFormulaPostRender'][] = array('GoogleAmp::mathHook');
$wgHooks['TitleSquidURLs'][] = array('GoogleAmp::onTitleSquidURLsPurgeVariants');
$wgHooks['ArticleFromTitle'][] = array('GoogleAmp::onArticleFromTitle');
$wgHooks['MobileProcessDomAfterSetSourcesSection'][] = array('GoogleAmp::onMobileProcessDomAfterSetSourcesSection');
$wgParserOutputHooks['ampEmbedVideoParserOutputHook'] = 'GoogleAmp::onAmpEmbedVideoParserOutputHook';
