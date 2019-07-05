<?php

class MobileFrontendWikiHowHooks {

	static function onSpecialPageBeforeExecute($special, $subPage) {
		global $wgOut, $wgRequest, $wgIsAnswersDomain;

		if (Misc::isMobileMode()) {
			$mobileAllowed = false;

			$title = $special->getTitle();

			if ($special->isMobileCapable()) {
				$mobileAllowed = true;
			}

			Hooks::run( 'IsEligibleForMobileSpecial', array( &$mobileAllowed ) );

			if (!$mobileAllowed && !$wgIsAnswersDomain) {
				$context = MobileContext::singleton();
				$context->setTempRedirectCookie();
				self::mobileSetView($wgOut, $wgRequest->getFullRequestURL(), 'desktop');
				$wgOut->output();
				return true;
			}
		}
	}

	static function onBeforePageDisplay( &$out ) {
		global $wgTitle, $wgUser, $wgRequest, $wgLang, $wgLanguageCode, $wgDebugToolbar, $IP;
		if (QADomain::isQADomain()) {
			return true;
		}
		if (Misc::isMobileMode()) {
			$mobileAllowed = false;

			//we're checking this elsewhere, so if we get here, it's ok
			if ($wgTitle && $wgTitle->inNamespace(NS_SPECIAL)) {
				$mobileAllowed = true;
			}

			// Category Pages
			if ($wgTitle && $wgTitle->inNamespace(NS_CATEGORY)) {
				$mobileAllowed = true;
			}

			//article pages
			if ($wgTitle && $wgTitle->inNamespace(NS_MAIN)) {
				$action = $wgRequest ? $wgRequest->getVal('action') : '';
				if ($action != "edit") {
					$mobileAllowed = true;
				}
			}
			//main page
			if ($wgTitle && $wgTitle->isMainPage()) {
				$mobileAllowed = true;
			}

			//user talk pages
			if ($wgTitle && $wgTitle->inNamespace(NS_USER_TALK)) {
				$mobileAllowed = true;
			}

			//discussion pages for logged out users (we're 404ing instead of redirecting them now)
			if ($wgTitle && $wgTitle->inNamespace(NS_TALK) && !$wgUser->isLoggedIn()) {
				$mobileAllowed = true;
			}

			if ($wgTitle && $wgTitle->inNamespace(NS_USER_KUDOS)) {
				$mobileAllowed = true;
			}

			if ($wgTitle && $wgTitle->inNamespace(NS_SPECIAL) && $wgTitle->getText() == "UserLogin") {
				$mobileAllowed = true;
			}

			if ($wgTitle && $wgTitle->inNamespace(NS_SPECIAL) && $wgTitle->getText() == "Spellchecker") {
				$mobileAllowed = true;
			}

			if ($wgTitle && $wgTitle->inNamespace(NS_SPECIAL) && $wgTitle->getText() == "PicturePatrol") {
				$mobileAllowed = true;
			}

			if ($wgTitle && $wgTitle->inNamespace(NS_USER)) {
				if ($wgUser->getID() > 0) { //if the current user is logged in
					$userName = $wgTitle->getText();
					$user = User::newFromName($userName);
					if ($user && $user->getID() > 0) {
						$mobileAllowed = true;
					}
				}
			}

			Hooks::run( 'IsEligibleForMobile', array( &$mobileAllowed ) );

			if (!$mobileAllowed) {
				$context = MobileContext::singleton();
				$context->setTempRedirectCookie();
				self::mobileSetView($out, $wgTitle->getFullURL(), 'desktop');
				$out->output();
				return true;
			}

		}
		$context = MobileContext::singleton();
		if ( !$context->shouldDisplayMobileView() ) {
			return true;
		}

		// retinaAvailable JS var is used (and must be defined) for image zoom on mobile
		$showHighDPI = WikihowMobileTools::isHighDPI($context->getTitle());
		$out->addHeadItem('whretina',  HTML::inlineScript('retinaAvailable = ' . ($showHighDPI ? 'true;' : 'false;')));

		$page_title = trim($out->getContext()->getTitle());

		$stylePaths = [__DIR__ . '/less/wikihow/style_top.css'];

		if (WikihowMobileTools::isInternetOrgRequest()) {
			$stylePaths[] = __DIR__ . '/less/wikihow/iorg.css';
		}

		Hooks::run("MobileEmbedStyles", [&$stylePaths, $context->getTitle()]);

		// the amp css was getting so large we now have a separate css file
		// which contains css that will NOT be used on amp
		$ampStylePaths = $stylePaths;
		$stylePaths[] = __DIR__ . '/less/wikihow/noamp_style_top.css';

		// TODO get this working for some reason when I add the css in this way it does not work yet probably due to loading order
		//$less = ResourceLoader::getLessCompiler();
		//$style = Misc::getEmbedFile('css', __DIR__ . '/less/wikihow/responsive.css');
		//$style = $less->compile($style);
		//$style = ResourceLoader::filter('minify-css', $style);
		//$style = HTML::inlineStyle($style);
		//$out->addHeadItem('topcss2', $style);
		// only add this on regular article pages for now:
		//we're checking this elsewhere, so if we get here, it's ok
		if ( $wgTitle && $wgTitle->inNamespace( NS_MAIN ) && !$wgTitle->isMainPage() ) {
			$stylePaths[] = __DIR__ . '/less/wikihow/responsive.css';
		}

		$top_style = Misc::getEmbedFiles('css', $stylePaths, null, $wgLang->isRTL());

		// Add some custom meta info for android phone requests.
		// These requests should always have the wh_an=1 query string parameter set.
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest() && class_exists('ArticleMetaInfo')) {
			ArticleMetaInfo::addAndroidAppMetaInfo();
		}

		if ( GoogleAmp::isAmpMode( $out ) ) {
			$amp_top_style = Misc::getEmbedFiles('css', $ampStylePaths, null, $wgLang->isRTL());
			GoogleAmp::addAmpStyle( $amp_top_style, $out );
		} else {
			$out->addHeadItem('topcss', HTML::inlineStyle($top_style));

			// Add some css and js modules for android phone requests.
			// These requests should always have the wh_an=1 query string parameter set.
			if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
				$out->addModules('ext.wikihow.android_helper');
				$top_style_android = Misc::getEmbedFile('css', __DIR__ . '/../android_helper/android_helper.css');
				$out->addHeadItem('androidcss', HTML::inlineStyle($top_style_android));
			}

			if ( class_exists( 'OptimizelyPageSelector' ) ) {
				$out->addHeadItem( 'optijs', OptimizelyPageSelector::getOptimizelyTag( $context, 'head' ) );
			}
		}

		if ($wgTitle->inNamespace(NS_MAIN) && !NewArticleBoost::isNABbedNoDb($wgTitle->getArticleID())) {
			$unnabbed = Misc::getEmbedFile('css', __DIR__ . '/less/wikihow/noindex.css');
			$out->addHeadItem('unnabbed', HTML::inlineStyle($unnabbed));
		}

		if ($wgTitle->inNamespace(NS_MAIN)) {
			//load bottom
			$out->addModules('zzz.mobile.wikihow.styles_late_load');
		}
		else {
			//load top
			$out->addModuleStyles('zzz.mobile.wikihow.styles_late_load');
		}
		$out->addModules('mobile.wikihow');

		$out->addModules('mobile.wikihow.stable.styles');

		// Add the logged out overlay module.
		$out->addModules('mobile.wikihow.loggedout');

		if (class_exists('Recommendations')) {
			$whr = new Recommendations();
			$whr->addModules();
		}

		$isLoginPage = $page_title == SpecialPage::getTitleFor( 'Userlogin' );

		if ( $isLoginPage ) {
			$out->addModules('ext.wikihow.sociallogin.buttons');
		}

		if ($isLoginPage) {
			$out->addModules('mobile.wikihow.login');
			$out->addModuleStyles('zzz.mobile.wikihow.login.styles');
			$pagetitle = $out->getPageTitle();
			$out->setPageTitle('');
			$out->setHTMLTitle($pagetitle);
		}

		if ( $page_title == SpecialPage::getTitleFor('PasswordReset') ) {
			$out->addModuleStyles('zzz.mobile.wikihow.passwordreset');
		}

		// Include Javascript for setting global size vars
		if ($out->getTitle()->inNamespace(NS_MAIN) || $out->getTitle()->inNamespace(NS_SPECIAL)) {
			$varScript = Misc::getEmbedFile('js', "$IP/extensions/wikihow/load_images/sizing-vars.compiled.js");
			$out->addHeadItem('vars', HTML::inlineScript($varScript));
		}

		if ( $out->getTitle()->inNamespace(NS_MAIN) || $out->getTitle()->getText() == 'TopicTagging' ) {
			$sharedjs = array( __DIR__. '/../../wikihow/commonjs/whshared.compiled.js' );
			$out->addHeadItem( 'sharedjs', Html::inlineScript( Misc::getEmbedFiles( 'js', $sharedjs ) ) );
		}

		$gdprjs = array( __DIR__. '/../../wikihow/GDPR/gdpr.js' );
		$out->addHeadItem( 'gdpr', Html::inlineScript( Misc::getEmbedFiles( 'js', $gdprjs ) ) );

		//include noscript styling for people without javascript (like internet.org users)
		if ($wgTitle && !$wgTitle->isMainPage()) {
			$template = new EasyTemplate( __DIR__ );
			$noScript = $template->execute('templates/mobile-noscript.tmpl.php');
			$minNoscript = CSSMin::minify( $noScript );
			$out->addScript($minNoscript);
		}

		if ( $wgTitle->inNamespace(NS_MAIN) ) {
			$out->addModules('mobile.wikihow.socialproof');
		}

		// this adds the ability to display debug messages to the debug toolbar
		// in javascript which is useful for ajax requests debugging
		WikihowSkinHelper::maybeAddDebugToolbar($out);

		if (wikihowAds::isEligibleForAds() && !wikihowAds::isExcluded($wgTitle)) {
			wikihowAds::addMobileAdSetup( $out );
			wikihowAds::getGlobalChannels();
		}

		if ($wgTitle->inNamespace(NS_MAIN)
			&& !$wgTitle->isMainPage()
			&& class_exists('Ouroboros\Special')
			&& Ouroboros\Special::isActive()
		) {
			$out->addModules(array(
				'ext.wikihow.ouroboros.styles',
				'ext.wikihow.ouroboros.scripts'
			));
		}

		return true;
	}

	public static function onSpecialPage_initList( &$list ) {
		global $wgLanguageCode;
		$ctx = MobileContext::singleton();
		if ( $ctx->shouldDisplayMobileView() ) {
			$list['MobileLoggedOutComplete'] = 'SpecialMobileLoggedOutComplete';
			if ( $wgLanguageCode == "en" ) {
				$list['Spellchecker'] = 'MobileSpellchecker';
				$list['PicturePatrol'] = 'MobileUCIPatrol';
				$list['RCPatrol'] = 'RCLite';
			}
		}
		return true;
	}

	// Redirect to our desktop or mobile URL using our own functions
	// because URL templating didn't work for our setup
	static function onMobileToggleView( $outputPage, $url, $view, $temporary ) {
		self::mobileSetView($outputPage, $url, $view);

		return true;
	}

	static function mobileSetView($outputPage, $url, $view) {
		$parsedUrl = wfParseUrl( $url );

		if ( isset($parsedUrl['path']) && strpos($parsedUrl['path'], '/') === 0 ) {
			$path = $parsedUrl['path'];
		} else {
			$path =  '/';
		}


		if ( $view == 'desktop' ) {
			$baseSite = WikihowMobileTools::getNonMobileSite();
		} else {
			$baseSite = WikihowMobileTools::getMobileSite();
		}

		$desktopUrl = $baseSite . $path;
		$outputPage->redirect( $desktopUrl, 301 );

		// Lojjik 12/17/2014
		// Fix to redirect mobile home page earlier before the output has been sent
		if ( $outputPage->getTitle()->isMainPage() ) {
			$outputPage->output();
		}
	}

	// Called at very end of page while html is displaying, before </body></html>
	public static function onMobileEndOfPage($data) {
		if ( $data['amp'] ) {
			return true;
		}
		// Include any deferred scripts, such as possibly ResourceLoader startup
		// scripts, at start of footer
		$context = $data['skin']->getContext();

		EasyTemplate::set_path( __DIR__ );

		// Include GA and other 3rd party scripts
		$footerVars = array();

		// Include Optimizely script
		$footerVars['optimizelyJs'] = '';
		if ( class_exists('OptimizelyPageSelector') ) {
			$footerVars['optimizelyJs'] =
				OptimizelyPageSelector::getOptimizelyTag( $context, 'body' );
		}

		$footerVars['showInternetOrgAnalytics'] = WikihowMobileTools::isInternetOrgRequest();

		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$propertyId = WH_GA_ID_ANDROID_APP; // Android app
		} elseif(class_exists('QADomain') && QADomain::isQADomain()) {
			$propertyId = WH_GA_ID_QUICKANSWERS; //QuickAnswers
		} else{
			$propertyId = WH_GA_ID; // wikihow.com;
		}

		$gaConfig = json_encode(Misc::getGoogleAnalyticsConfig());

		$html = '';
		$html .= HTML::inlineScript(
			EasyTemplate::html(
				'includes/skins/analytics-js.tmpl.php',
				array(
					'propertyId' => $propertyId,
					'gaConfig' => $gaConfig
				)
			)
		);

		// Script to be loaded for ad blocker detection
		if (class_exists('AdblockNotice')) {
			$html .= AdblockNotice::getBottomScript();
		}

		$html .= EasyTemplate::html('includes/skins/wh_mobileFrontendFooter.tmpl.php', $footerVars);

		print $html;

		return true;
	}
}
