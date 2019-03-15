<?php
/**
 * Helper class used to display ios deep links
 */

class IOSHelper {
	const IOS_APP_ID = 309209200;
	const IOS_APP_NAME = "wikiHow";

	public static function isSupportedIOSLanguage( $langCode ) {
		return in_array( $langCode, array( 'en' ) );
	}

	public static function getAppName() {
		return self::IOS_APP_NAME;
	}

	public static function getAppId() {
		return self::IOS_APP_ID;
	}

	public static function getArticleUrl( $title ) {
		if ( !$title || !$title->exists() ) {
			return "";
		}

		return "wikihow://article?id=".$title->getArticleID();
	}



	// Certain languages have app indexing capabilities.  Add app indexing link tag if
	// a valid language
	// example link from google:
	//<a href="gsd-wikihow://309209200/?google-deep-link=wikihow%3A%2F%2Farticle%3Fid%3D2053&google-callback-url=googleapp%3A%2F%2F&google-min-sdk-version=1.0.0">wikihow://article?name=Kiss</a>
	public static function addIOSAppIndexingLinkTag() {
		global $wgLanguageCode;

		$request = RequestContext::getMain();
		$out = $request->getOutput();
		if ( !self::isSupportedIOSLanguage($wgLanguageCode) || !WikihowSkinHelper::shouldShowMetaInfo($out) ) {
			return;
		}

		$t = $request->getTitle();
		if ( !$t || !$t->exists() ) {
			return;
		}

		if ( wfMessage( 'ios_app_indexing', 'off' )->text() == 'on' ) {
			$link = Html::element( 'link', array( 'rel' => 'alternate',
				'href' => "ios-app://" . self::getAppId() . "/wikihow/article?id=".$t->getArticleID() ) );
			$out->addHeadItem('ios_app_indexing', $link);
		}
	}

	public static function addIOSAppBannerTag() {
		global $wgLanguageCode;

		// Turning off for now
		return;

		if ( !self::isSupportedIOSLanguage($wgLanguageCode) ) {
			return;
		}

		$ctx = RequestContext::getMain();
		$t = $ctx->getTitle();
		if ( !$t || !$t->exists() ) {
			return;
		}

		// Only show on 10% of articles
		if (mt_rand(1, 10) > 1) {
			return;
		}

		$action = $ctx->getRequest()->getVal('action', 'view');

		$isMainPage = $t
			&& $t->inNamespace(NS_MAIN)
			&& $t->getText() == wfMessage('mainpage')->inContentLanguage()->text()
			&& $action == 'view';

		$isArticlePage = $t
			&& !$isMainPage
			&& $t->inNamespace(NS_MAIN)
			&& $action == 'view';

		$content = 'app-id=309209200';
		// Deep link to specific article if possible
		if ($isArticlePage) {
			$content .= ', app-argument=wikihow://article?id=' . $t->getArticleID();
		}

		// Example meta tag from Safari developer website:
		// <meta name="apple-itunes-app" content="app-id=myAppStoreID, affiliate-data=myAffiliateData, app-argument=myURL">
		$link = Html::element(
			'meta',
			[
				'name' => 'apple-itunes-app',
				'content' => $content
			]);

		$ctx->getOutput()->addHeadItem('apple-itunes-app', $link);
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$t = $skin->getTitle();
		$r = $skin->getRequest();

		if ($t
			&& Misc::isMobileMode()
			&& $t->inNamespace(NS_MAIN)
			&& $r->getVal('action', 'view') == 'view') {
			self::addIOSAppBannerTag();
		}

		return true;
	}
}
