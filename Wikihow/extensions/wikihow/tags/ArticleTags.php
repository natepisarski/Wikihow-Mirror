<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['ConfigStorage'] = __DIR__ . '/ConfigStorage.php';
$wgAutoloadClasses['AdminTags'] = __DIR__ . '/SpecialAdminTags.php';
$wgAutoloadClasses['ArticleTag'] = __DIR__ . '/ArticleTag.php';
$wgAutoloadClasses['ArticleTagList'] = __DIR__ . '/ArticleTagList.php';
$wgExtensionMessagesFiles['ArticleTagAlias'] = __DIR__ . '/ArticleTags.alias.php';

$wgHooks['ConfigStorageStoreConfig'] = ['ArticleTag::onConfigStorageStoreConfig'];
$wgSpecialPages['AdminTags'] = 'AdminTags';
$wgSpecialPages['AdminConfigEditor'] = 'AdminTags'; // alias from old special page name
