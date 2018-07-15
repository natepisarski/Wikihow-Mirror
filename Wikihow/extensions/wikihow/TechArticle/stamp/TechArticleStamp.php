<?php

/**
 * "Community Tested" stamp in the upper right corner of the article intro box
 *
 * @author Alberto Burgos
 */

if (!defined('MEDIAWIKI'))
	die();

$wgAutoloadClasses['TechArticle\TechArticleStampHooks'] = dirname( __FILE__ ) . '/TechArticleStamp.hooks.php';

$wgHooks['ProcessArticleHTMLAfter'][] = 'TechArticle\TechArticleStampHooks::onProcessArticleHTMLAfter';
$wgHooks['BeforeRenderPageActionsMobile'][] = 'TechArticle\TechArticleStampHooks::onBeforeRenderPageActionsMobile';

$wgMessagesDirs['TechArticleStamp'] = __DIR__ . '/i18n';
