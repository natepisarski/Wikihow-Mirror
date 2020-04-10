<?php

if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Sitemap',
	'author' => 'wikiHow',
	'description' => 'Generates a page of links to the top level categories and their subcatgories',
);

$wgMessagesDirs['Sitemap'] = __DIR__ . '/i18n/';

$wgSpecialPages['Sitemap'] = 'Sitemap';
$wgAutoloadClasses['Sitemap'] = __DIR__ . '/Sitemap.body.php';
$wgExtensionMessagesFiles['SitemapAlias'] = __DIR__ . '/Sitemap.alias.php';

$wgResourceModules['ext.wikihow.sitemap_styles'] = [
	'styles' => [ 'sitemap.less' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Sitemap',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'top'
];