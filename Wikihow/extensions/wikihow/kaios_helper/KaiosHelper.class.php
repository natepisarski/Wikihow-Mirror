<?php
/**
 * Helper class used to enable/disable functionality of mobile web to display within our Android app
 */

class KaiosHelper {

	const QUERY_STRING_PARAM = "kaios";
	const BODY_CLASS_NAME = "kaios";

	public static function isKaiosRequest() {
		return isset($_SERVER['REQUEST_URI']) &&
			stripos($_SERVER['REQUEST_URI'], self::QUERY_STRING_PARAM . '=1') !== false;
	}

	public static function onTitleSquidURLsPurgeVariants($title, &$urls) {
		global $wgLanguageCode;

		if ($title && $title->exists() && $title->inNamespaces(NS_MAIN, NS_CATEGORY, NS_SPECIAL)) {
			$kaiosUrl = $title->getInternalURL();
			$partialUrl = preg_replace("@^(https?:)?//[^/]+/@", "/", $kaiosUrl);
			$domain = Misc::getLangDomain($wgLanguageCode, true);
			$kaiosUrl = "https://" . $domain . $partialUrl . "?" . self::QUERY_STRING_PARAM . "=1";
			$urls[] = $kaiosUrl;
		}
		return true;
	}

	public static function isSupportedLanguage($langCode) {
		return in_array($langCode, ['en']);
	}

	public static function onBeforePageDisplay($out, $skin) {
		if (KaiosHelper::isKaiosRequest()) {
			$style = Misc::getEmbedFile('less', __DIR__ . '/../kaios_helper/kaios_helper.less');
			$out->addHeadItem('kaioscss', HTML::inlineStyle($style));

			$kaiosjs = array( __DIR__. '/../../wikihow/kaios_helper/kaios_top.js' );
			$out->addHeadItem( 'kaiosjs', Html::inlineScript( Misc::getEmbedFiles( 'js', $kaiosjs ) ) );

			$out->addBodyClasses(KaiosHelper::BODY_CLASS_NAME);
			$out->addModules('ext.wikihow.kaios_helper');
		}
	}
}
