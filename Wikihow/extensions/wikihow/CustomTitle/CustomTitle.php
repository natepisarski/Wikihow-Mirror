<?php

$wgAutoloadClasses['CustomTitle'] = __DIR__ . '/CustomTitle.class.php';
$wgMessagesDirs['CustomTitle'] = __DIR__ . '/i18n/';

$wgHooks['PageContentSaveComplete'][] = ['CustomTitle::recalculateCustomTitleOnPageSave'];
$wgHooks['TitleMoveComplete'][] = ['CustomTitle::onTitleMoveComplete'];
$wgHooks['ArticleDelete'][] = ['CustomTitle::onArticleDelete'];
