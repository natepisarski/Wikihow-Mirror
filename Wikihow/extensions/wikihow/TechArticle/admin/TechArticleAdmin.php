<?php

/**
 * Exposes the /Special:TechArticleAdmin page, which staff members can use
 * to modify the Tech Article Widget data.
 *
 * @author Alberto Burgos
 */

if (!defined('MEDIAWIKI'))
	die();

$wgSpecialPages['TechArticleAdmin'] = 'TechArticle\TechArticleAdmin';

$wgAutoloadClasses['TechArticle\TechArticleAdmin'] = __DIR__ . '/TechArticleAdmin.body.php';

$wgMessagesDirs['TechArticleAdmin'] = __DIR__ . '/i18n';

$wgResourceModules['ext.wikihow.TechArticle.admin'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/TechArticle/admin/resources',
	'localBasePath' => __DIR__ . '/resources',
	'styles' => ['tech_article_admin.less'],
	'scripts' => ['tech_article_admin.js'],
];
