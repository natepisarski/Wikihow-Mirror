<?php


if (in_array($wgLanguageCode, $wgActiveAlexaApiLanguages)) {
	$wgAutoloadClasses['WikiHowArticleDomExtractor'] = __DIR__ . '/read_article/WikiHowArticleDomExtractor.php';
}



