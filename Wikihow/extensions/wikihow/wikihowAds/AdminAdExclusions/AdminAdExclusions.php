<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Ad Exclusions Tool',
	'author' => 'Bebeth Steudel',
	'description' => '',
);

$wgSpecialPages['AdminAdExclusions'] = 'AdminAdExclusions';
$wgAutoloadClasses['AdminAdExclusions'] =
$wgAutoloadClasses['ArticleAdExclusions'] =
$wgAutoloadClasses['SearchAdExclusions'] = __DIR__ . '/AdminAdExclusions.body.php';

$wgResourceModules['ext.wikihow.ad_exclusions.articles'] =
$wgResourceModules['ext.wikihow.ad_exclusions.search'] = [
	'targets' => [ 'desktop' ],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/wikihowAds/AdminAdExclusions/resources',
	'localBasePath' => __DIR__ . '/resources',
	'styles' => [ 'adexclusions.less' ],
];

$wgResourceModules['ext.wikihow.ad_exclusions.articles']['scripts'] = [ 'articles.js' ];
$wgResourceModules['ext.wikihow.ad_exclusions.search']['scripts'] = [ 'search.js' ];

/***

CREATE TABLE IF NOT EXISTS adexclusions (
  ae_page int(10) unsigned NOT NULL,
  UNIQUE KEY (ae_page)
);

CREATE TABLE IF NOT EXISTS adexclusions_search (
  aes_lang varchar(2) NOT NULL,
  aes_query varbinary(512) NOT NULL,
  UNIQUE KEY aes_lang_query (aes_lang, aes_query)
);

***/
