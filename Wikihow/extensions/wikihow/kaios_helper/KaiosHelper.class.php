<?php
/**
 * Helper class used to enable/disable functionality of mobile web to display within our Android app
 */

class KaiosHelper {

	const QUERY_STRING_PARAM = "kaios";

	public static function isKaiosRequest() {
		return isset($_SERVER['REQUEST_URI']) &&
			stripos($_SERVER['REQUEST_URI'], self::QUERY_STRING_PARAM . '=1') !== false;
	}

	public static function onTitleSquidURLsPurgeVariants($title, &$urls) {
		global $wgLanguageCode;

		if ($title && $title->exists() && $title->inNamespace(NS_MAIN)) {
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

	// Add some special js for KaiOS requests
	static function onResourceLoaderGetStartupModules(&$moduleNames) {
//		if (class_exists('KaiosHelper') && KaiosHelper::isKaiosRequest()) {
//			$moduleNames[]= "ext.wikihow.kaios_helper";
//		}
//		return true;
	}
}
