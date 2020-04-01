<?php

if (!defined('MEDIAWIKI'))
	die();

$wgExtensionCredits['other'][] = array(
	'name' => 'TechArticle',
	'author' => 'Alberto Burgos',
	'description' => 'Provides the Tech Article Widget features and admin tools'
);

$wgAutoloadClasses['TechArticle\TechArticleDao'] = __DIR__ . '/core/TechArticleDao.class.php';
$wgAutoloadClasses['TechArticle\TechArticle'] = __DIR__ . '/core/TechArticle.class.php';

$wgAutoloadClasses['TechArticle\TechComponentDao'] = __DIR__ . '/core/TechComponentDao.class.php';
$wgAutoloadClasses['TechArticle\TechComponent'] = __DIR__ . '/core/TechComponent.class.php';
$wgAutoloadClasses['TechArticle\TechProduct'] = __DIR__ . '/core/TechComponent.class.php';
$wgAutoloadClasses['TechArticle\TechPlatform'] = __DIR__ . '/core/TechComponent.class.php';

require_once("$IP/extensions/wikihow/TechArticle/admin/TechArticleAdmin.php");
// [sc] 10/2017 consolidated stamp logic into SocialProofStats.php
require_once("$IP/extensions/wikihow/TechArticle/stamp/TechArticleStamp.php");
require_once("$IP/extensions/wikihow/TechArticle/widget/TechArticleWidget.php");
