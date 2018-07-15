<?php

if (!defined('MEDIAWIKI')) die();

$wgSpecialPages['TwitterAccounts'] = 'TwitterAccounts';
$wgSpecialPages['MyTwitter'] = 'MyTwitter';
$wgAutoloadClasses['TwitterAccounts'] = __DIR__ . '/TwitterFeed.body.php';
$wgAutoloadClasses['MyTwitter'] = __DIR__ . '/TwitterFeed.body.php';
$wgAutoloadClasses['TwitterFeedHooks'] = __DIR__ . '/TwitterFeedHooks.php';
$wgAutoloadClasses['Twitter'] = __DIR__ . '/../common/twitterapi.php';

$wgHooks["MarkTitleAsRisingStar"][] = "TwitterFeedHooks::notifyTwitterRisingStar";
$wgHooks["ArticleSaveComplete"][] = "TwitterFeedHooks::notifyTwitterOnSave";
$wgHooks["NABArticleFinished"][] = "TwitterFeedHooks::notifyTwitterOnNAB";

$wgExtensionMessagesFiles['MyTwitter'] = __DIR__ . '/TwitterFeed.i18n.php';


$wgHooks['ArticleInsertComplete'][] = array("TwitterFeedHooks::myTwitterInsertComplete");
$wgHooks["NABArticleFinished"][] = array("TwitterFeedHooks::myTwitterNAB");
$wgHooks["UploadComplete"][] = array("TwitterFeedHooks::myTwitterUpload");
$wgHooks["EditFinderArticleSaveComplete"][] = array("TwitterFeedHooks::myTwitterEditFinder"); 
$wgHooks["ArticleSaveComplete"][] = array("TwitterFeedHooks::myTwitterOnSave"); 

