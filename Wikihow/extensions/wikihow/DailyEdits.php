<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['DailyEdits'] = __DIR__ . '/DailyEdits.class.php';
$wgHooks['MarkPatrolledDB'][] = 'DailyEdits::onMarkPatrolledDB';
$wgHooks['ArticleDeleteComplete'][] = 'DailyEdits::onArticleDeleteComplete';
$wgHooks['ArticleUndelete'][] = 'DailyEdits::onArticleUndelete';
$wgHooks['TitleMoveComplete'][] = 'DailyEdits::onTitleMoveComplete';
$wgHooks['QuickSummaryEditComplete'][] = ['DailyEdits::onQuickSummaryEditComplete'];
