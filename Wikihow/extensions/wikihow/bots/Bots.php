<?php

if ($wgLanguageCode == "en") {
	$wgExtensionCredits['APIAIWikihowAgentWebHook'][] = array(
		'name' => 'API.AI WebHook to handle requests for the wikihow agent on API.AI',
		'author' => 'Jordan Small',
		'description' => 'Bot that reads articles',
	);

	$wgSpecialPages['APIAIWikihowAgentWebHook'] = 'APIAIWikihowAgentWebHook';
	$wgAutoloadClasses['APIAIWikihowAgentWebHook'] = __DIR__ . '/api_ai/APIAIWikihowAgentWebHook.php';

	$wgAutoloadClasses['ReadArticleBot'] = __DIR__ . '/read_article/ReadArticleBot.php';
	$wgMessagesDirs['ReadArticleBot'] = [__DIR__ . '/read_article/i18n/'];
	$wgAutoloadClasses['ReadArticleModel'] = __DIR__ . '/read_article/ReadArticleModel.php';
	$wgMessagesDirs['ReadArticleModel'] = [__DIR__ . '/read_article/i18n/'];

	$wgAutoloadClasses['ReadArticleModelV2'] = __DIR__ . '/read_article/ReadArticleModelV2.php';
	$wgMessagesDirs['ReadArticleModelV2'] = [__DIR__ . '/read_article/i18n/'];
	$wgAutoloadClasses['ReadArticleBotV2'] = __DIR__ . '/read_article/ReadArticleBotV2.php';
	$wgMessagesDirs['ReadArticleBotV2'] = [__DIR__ . '/read_article/i18n/'];
}

if (in_array($wgLanguageCode, $wgActiveAlexaApiLanguages)) {
	$wgAutoloadClasses['WikiHowArticleDomExtractor'] = __DIR__ . '/read_article/WikiHowArticleDomExtractor.php';
}



