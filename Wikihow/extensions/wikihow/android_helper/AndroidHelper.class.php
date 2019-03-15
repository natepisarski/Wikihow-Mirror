<?php
/**
 * Helper class used to enable/disable functionality of mobile web to display within our Android app
 */

class AndroidHelper {

	const QUERY_STRING_PARAM = "wh_an";

	public static function isAndroidRequest() {
		return stripos($_SERVER['REQUEST_URI'], self::QUERY_STRING_PARAM . '=1') !== false;
	}

	public static function onTitleSquidURLsPurgeVariants($title, &$urls) {
		global $wgLanguageCode;

		if ($title && $title->exists() && $title->inNamespace(NS_MAIN)) {
			$androidUrl = $title->getInternalURL();
			$partialUrl = preg_replace("@^(https?:)?//[^/]+/@", "/", $androidUrl);
			$domain = Misc::getLangDomain($wgLanguageCode, true);
			$androidUrl = "https://" . $domain . $partialUrl . "?" . self::QUERY_STRING_PARAM . "=1";
			$urls[] = $androidUrl;
		}
		return true;
	}

	public static function isSupportedAndroidLanguage($langCode) {
		return in_array($langCode, array('en', 'es', 'pt', 'it', 'fr', 'ru', 'cs', 'de', 'nl', 'zh', 'id'));
	}

	// Certain languages have app indexing capabilities.  Add app indexing link tag if
	// a valid language
	public static function addAndroidAppIndexingLinkTag() {
		global $wgLanguageCode;

		$request = RequestContext::getMain();
		$out = $request->getOutput();
		$t = $request->getTitle();
		if ($t && $t->exists() && wfMessage('android_app_indexing', 'off')->text() == 'on'
				&& WikihowSkinHelper::shouldShowMetaInfo($out)) {
			$out->addHeadItem('android_app_indexing',
				'<link rel="alternate" href="android-app://com.wikihow.wikihowapp/http/' .
				Misc::getLangDomain($wgLanguageCode) . '/' . $t->getPartialURL() . '"/>');
		}
	}

	// Add a tiny js snippet to the startup module if it's an android request
	static function onResourceLoaderGetStartupModules(&$moduleNames) {
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$moduleNames[]= "ext.wikihow.android_helper_ajax";
		}
		return true;
	}
}
