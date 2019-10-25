<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ArticleStats',
	'author' => 'Travis Derouin',
	'description' => 'Basic dashboard that gives some summarized information on a page',
);

$wgSpecialPages['ArticleStats'] = 'ArticleStats';
$wgExtensionMessagesFiles['Cite'] = __DIR__ . '/Articlestats.i18n.php';
$wgAutoloadClasses['ArticleStats'] = __DIR__ . '/Articlestats.body.php';

$wgExtensionMessagesFiles['ArticleStatsAlias'] = __DIR__ . '/Articlestats.alias.php';
