<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Suggested Topics',
    'author' => 'Bebeth',
    'description' => 'Suggested topics: help authors find topics to write about on wikiHow',
];

$wgSpecialPages['ListRequestedTopics'] = 'ListRequestedTopics';
$wgSpecialPages['ManageSuggestedTopics'] = 'ManageSuggestedTopics';
$wgSpecialPages['RecommendedArticles'] = 'RecommendedArticles';
$wgSpecialPages['RenameSuggestion'] = 'RenameSuggestion';
$wgSpecialPages['RequestTopic'] = 'RequestTopic';
$wgSpecialPages['SuggestCategories'] = 'SuggestCategories';
$wgSpecialPages['YourArticles'] = 'YourArticles';

$dir = __DIR__ . '/';

$wgExtensionMessagesFiles['ListRequestedTopics'] =
	$wgExtensionMessagesFiles['ManageSuggestedTopics'] =
	$wgExtensionMessagesFiles['RecommendedArticles'] =
	$wgExtensionMessagesFiles['RequestTopic'] =
	$wgExtensionMessagesFiles['YourArticles'] = $dir . 'SuggestedTopics.i18n.php';

$wgExtensionMessagesFiles['ListRequestedTopicsAlias'] = $dir . 'ListRequestedTopics.alias.php';
$wgExtensionMessagesFiles['RequestTopicAlias'] = $dir . 'RequestTopic.alias.php';

$wgAutoloadClasses['ListRequestedTopics']       = $dir . 'ListRequestedTopics.body.php';
$wgAutoloadClasses['ManageSuggestedTopics']     = $dir . 'ManageSuggestedTopics.body.php';
$wgAutoloadClasses['RecommendedArticles']       = $dir . 'RecommendedArticles.body.php';
$wgAutoloadClasses['RenameSuggestion']          = $dir . 'RenameSuggestion.body.php';
$wgAutoloadClasses['RequestTopic']              = $dir . 'RequestTopic.body.php';
$wgAutoloadClasses['SuggestCategories']         = $dir . 'SuggestCategories.body.php';
$wgAutoloadClasses['SuggestedTopicsHooks']      = $dir . 'SuggestedTopics.hooks.php';
$wgAutoloadClasses['YourArticles']              = $dir . 'YourArticles.body.php';

$wgHooks['NABArticleFinished'][] = [ 'SuggestedTopicsHooks::notifyRequesterOnNab' ];

$wgResourceModules['ext.wikihow.SuggestedTopics'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'styles' => [ 'suggestedtopics.css' ],
	'scripts' => [ 'suggestedtopics.js' ],
	'messages' => [
		'suggest_please_enter_title',
		'suggest_please_select_cat',
		'suggest_please_enter_email'
	],
	'dependencies' => [ 'ext.wikihow.common_top', 'jquery.ui.dialog' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top'
];

$wgResourceModules['ext.wikihow.ManageSuggestedTopics'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ],
	'scripts' => [ 'managesuggestedtopics.js' ],
	'dependencies' => [ 'ext.wikihow.SuggestedTopics' ],
	'remoteExtPath' => 'wikihow',
	'position' => 'top'
];
