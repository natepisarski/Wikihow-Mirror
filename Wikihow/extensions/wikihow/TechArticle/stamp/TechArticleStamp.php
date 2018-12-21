<?php

/**
 * "Community Tested" stamp in the upper right corner of the article intro box
 *
 * @author Alberto Burgos
 */

if (!defined('MEDIAWIKI'))
	die();

$wgAutoloadClasses['TechArticle\TechArticleStampHooks'] = dirname( __FILE__ ) . '/TechArticleStamp.hooks.php';

$wgHooks['BylineStamp'][] = 'TechArticle\TechArticleStampHooks::setBylineInfo';

$wgMessagesDirs['TechArticleStamp'] = __DIR__ . '/i18n';
