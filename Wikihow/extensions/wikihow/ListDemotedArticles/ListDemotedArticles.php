<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'ListDemotedArticles',
	'version' => '1.0',
	'license-name' => 'GPL-2.0+',
	'author' => 'Lojjik Braughler',
	'url' => 'http://src.wikihow.com',
	'descriptionmsg' => 'listdemotedarticles-desc');


$wgSpecialPages['ListDemotedArticles'] = 'ListDemotedArticles';
$wgAutoloadClasses['ListDemotedArticles'] = __DIR__ . '/ListDemotedArticles.body.php';
$wgMessagesDirs['ListDemotedArticles'] = __DIR__ . '/i18n';

$wgAvailableRights[] = 'listdemotedarticles';
$wgGroupPermissions['autoconfirmed']['listdemotedarticles'] = true;
