<?php

/**
 * Embeds the Tech Article Widget into the edit page.
 *
 * @author Alberto Burgos
 */

if (!defined('MEDIAWIKI'))
	die();

$wgAutoloadClasses['TechArticle\TechArticleWidgetHooks'] = dirname( __FILE__ ) . '/TechArticleWidget.hooks.php';
$wgAutoloadClasses['TechArticle\TechArticleWidgetModel'] = dirname( __FILE__ ) . '/TechArticleWidgetModel.class.php';

$wgHooks['PageContentSaveComplete'][] = 'TechArticle\TechArticleWidgetHooks::onPageContentSaveComplete';

$wgMessagesDirs['TechArticleWidget'] = __DIR__ . '/i18n';

$wgResourceModules['ext.wikihow.TechArticle.widget'] = [
	'targets' => ['desktop'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/TechArticle/widget/resources',
	'localBasePath' => dirname(__FILE__) . '/resources',
	'styles' => ['tech_article_widget.less'],
	'scripts' => ['tech_article_widget.js'],
	'messages' => [
		'taw_please_fill_widget'
	],
	'dependencies' => [
		'wikihow.common.select2',
	],
];
