<?php

if ( ! defined( 'MEDIAWIKI' ) )
    die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Sitemap',
    'author' => 'wikiHow',
    'description' => 'Generates a page of links to the top level categories and their subcatgories',
);

$wgExtensionMessagesFiles['Sitemap'] = __DIR__ . '/Sitemap.i18n.php';

$wgSpecialPages['Sitemap'] = 'Sitemap';
$wgAutoloadClasses['Sitemap'] = __DIR__ . '/Sitemap.body.php';
$wgExtensionMessagesFiles['SitemapAlias'] = __DIR__ . '/Sitemap.alias.php';

$wgHooks["IsEligibleForMobileSpecial"][] = ["Sitemap::isEligibleForMobileSpecial"];
