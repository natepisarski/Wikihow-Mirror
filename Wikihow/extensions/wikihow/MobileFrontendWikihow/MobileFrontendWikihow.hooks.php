<?php

class MobileFrontendWikiHowHooks {

	private static $isvalidResponsivePage = null;

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
		global $wgTitle, $wgUser, $wgRequest, $wgLang, $wgLanguageCode, $wgDebugToolbar, $IP, $wgProfiler, $wgIsDevServer;
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
				$mobileAllowed = true;
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

			if ($wgTitle && $wgTitle->inNamespace(NS_USER)) {
				// for the responsive on www rollout
				if ( !Misc::isMobileModeLite() ) {
					$mobileAllowed = true;
				}

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

		$stylePaths = [];

		$isFastRenderTest = false;
		if ( Misc::isFastRenderTest() ) {
			$isFastRenderTest = true;
		}

		// do not include style top at all for fast render test
		if ( !$isFastRenderTest ) {
			$stylePaths[] = __DIR__ . '/less/wikihow/style_top.css';
		}

		if (WikihowMobileTools::isInternetOrgRequest()) {
			$stylePaths[] = __DIR__ . '/less/wikihow/iorg.css';
		}

		Hooks::run("MobileEmbedStyles", [&$stylePaths, $context->getTitle()]);

		// the amp css was getting so large we now have a separate css file
		// which contains css that will NOT be used on amp
		$ampStylePaths = $stylePaths;

		// for fast render test do not include this css file either
		if ( !$isFastRenderTest ) {
			$stylePaths[] = __DIR__ . '/less/wikihow/noamp_style_top.css';
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
			$out->addModules('zzz.mobile.wikihow.scripts_late_load');
			$out->addModules('mobile.wikihow.tabsonmobile');
		}
		else {
			//load top
			$out->addModuleStyles('zzz.mobile.wikihow.styles_late_load');
			//add side-styles
			$out->addModuleStyles('ext.wikihow.nonarticle_styles');
		}

		if ($wgTitle->inNamespace(NS_SPECIAL) && $page_title == SpecialPage::getTitleFor( 'Notifications' )) {
			$out->addModuleStyles('zzz.mobile.wikihow.notifications');
		}

		$out->addModules('mobile.wikihow');
		$out->addModules('mobile.wikihow.stable.styles');

		// Add the logged out overlay module.
		// Reuben: disabling this for upgrade since overlays apparently changed
		//$out->addModules('mobile.wikihow.loggedout');

		if (class_exists('Recommendations')) {
			$whr = new Recommendations();
			$whr->addModules();
		}

		if ( $page_title == SpecialPage::getTitleFor('PasswordReset') ) {
			$out->addModuleStyles('zzz.mobile.wikihow.passwordreset');
		}

		$sharedjs = array( __DIR__. '/../../wikihow/commonjs/whshared.compiled.js' );
		$out->addHeadItem( 'sharedjs', Html::inlineScript( Misc::getEmbedFiles( 'js', $sharedjs ) ) );

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

		$showImageFeedback = class_exists('ImageFeedback') && ImageFeedback::isValidPage();
		if ($showImageFeedback) {
			$out->addModuleStyles('ext.wikihow.image_feedback_styles');
			$out->addModules('ext.wikihow.image_feedback');
		}

		$showSlider = class_exists('Slider') && Slider::isValidPage();
		if ($showSlider) {
			$out->addModuleStyles('ext.wikihow.slider_styles');
			$out->addModules('ext.wikihow.slider');
		}

		// this adds the ability to display debug messages to the debug toolbar
		// in javascript which is useful for ajax requests debugging
		WikihowSkinHelper::maybeAddDebugToolbar($out);

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

		if (self::validResponsivePage()) {
			global $wgResourceLoaderLESSImportPaths, $wgUser;
			$wgResourceLoaderLESSImportPaths = [ "$IP/extensions/wikihow/less/" => '' ];

			$embedStyles = [];
			// do not use the responsive.less file on fast render test. it uses its own responsive_fastrender.less file
			// which is included in another BeforePageDisplay hook which outputs higher up in the head
			if ( !$isFastRenderTest ) {
				$embedStyles[] = __DIR__ . '/less/wikihow/responsive.less';
			}
			if (!$wgUser->isAnon()) {
				$embedStyles[] = __DIR__ . '/less/wikihow/responsive_loggedin.less';
			}

			// if we are on the fast render test, there may be no embedStyles to putput, so checking the size here
			if ( count( $embedStyles ) ) {
				$style = Misc::getEmbedFiles('css', $embedStyles);
				$less = ResourceLoader::getLessCompiler();
				$less->parse($style);
				$style = $less->getCss();
				$style = ResourceLoader::filter('minify-css', $style);
				$style = HTML::inlineStyle($style);
				$out->addHeadItem('topcss2', $style);
			}
		}

		if (self::showRCWidget()) {
			$out->addHTML(RCWidget::rcWidgetJS());
			$out->addModules(['ext.wikihow.rcwidget']);
			$out->addModuleStyles(['ext.wikihow.rcwidget_styles']);
		}

		// this adds some extra css to the page if we are on dev site
		if ( $wgIsDevServer && $wgProfiler['visible'] == true ) {
			$pCss = "<style>#profilerout{position:absolute;top:500px;background:white;z-index:10000;max-width:100%}#profilerout span{position:absolute;right:10px;</style>";
			$out->addHeadItem('profilercss', $pCss);
			$out->addHeadItem('profilerjs', $pCss);
		}

		return true;
	}

	public static function validResponsivePage(): bool {
		if (!is_null(self::$isvalidResponsivePage)) return self::$isvalidResponsivePage;

		$title = RequestContext::getMain()->getTitle();
		$isSearchPage = preg_match('@/wikiHowTo@',  $_SERVER['REQUEST_URI']);

		$wHnamespacePagesWithCss = [
			wfMessage('trustworthy-page')->text(),
			wfMessage('about-page')->text(),
			'Privacy-Policy',
			'Jobs',
			'Terms-of-Use',
			'About-wikiHow.health',
			'About-wikiHow.legal',
			'About-wikiHow.mom',
			'About-wikiHow.fitness',
			'About-wikiHow.tech',
			'About-wikiHow.pet',
			'About-wikiHow.life',
			'About-wikiHow-fun',
			'Language-Projects',
			'Mission',
			'Creative-Commons',
			'Gives-Back',
			'Powered-and-Inspired-by-MediaWiki',
			'Carbon-Neutral',
			'Hybrid-Organization',
			'Tour',
			'Free-Basics',
			'History-of-wikiHow'
		];

		$specialPagesWithCss = [
			'Sitemap',
			'NewPages',
			'ReindexedPages',
			'CategoryListing',
			'ArticleReviewers',
			'ProfileBox',
			'Avatar',
			'UserLogin',
			'CreateAccount',
			'UserLogout',
			'PasswordReset',
			'MobileCommunityDashboard',
			'Notifications',
			'HighSchoolHacks',
			'RequestTopic',
			'CreatePage',
			'Newpages'
		];

		$responsiveTools = [
			'TechFeedback',
			'SortQuestions',
			'QuizYourself',
			'MobileSpellchecker',
			'RCLite',
			'MobileUCIPatrol',
			'UnitGuardian',
			'MobileTopicTagging',
			'MobileCategoryGuardian'
		];

		self::$isvalidResponsivePage = $title &&
			$title->inNamespaces( NS_MAIN, NS_USER, NS_USER_TALK, NS_USER_KUDOS ) ||
			($title->inNamespace( NS_PROJECT ) && in_array($title->getDBkey(), $wHnamespacePagesWithCss)) ||
			($title->isSpecialPage() && in_array($title->getText(), $specialPagesWithCss)) ||
			($title->isSpecialPage() && in_array($title->getText(), $responsiveTools)) ||
			($title->isSpecialPage() && stripos($title->getText(), 'VideoBrowser') === 0) ||
			($title->isSpecialPage() && stripos($title->getText(), 'DocViewer/') === 0) ||
			$title->inNamespace( NS_CATEGORY) ||
			$isSearchPage;

		return self::$isvalidResponsivePage;
	}

	public static function onMinvervaTemplateBeforeRender( &$data ) {
		if (self:: validResponsivePage()) $data['is_responsive'] = true;
	}

	private static function showRCWidget(): bool {
		$context = RequestContext::getMain();
		$title = $context->getTitle();
		$user = $context->getUser();
		$isLoggedIn = $user->isLoggedIn();

		return class_exists('RCWidget') &&
			!$title->inNamespaces(NS_USER) &&
			(!$isLoggedIn || $user->getOption('recent_changes_widget_show', true) == 1 ) &&
			($isLoggedIn || $title->isMainPage()) &&
			!in_array($title->getPrefixedText(), ['Special:Avatar', 'Special:ProfileBox']) &&
			strpos($title->getPrefixedText(), 'Special:UserLog') === false &&
			substr( $title->getText(), 0, 9 ) !== "DocViewer" &&
			Action::getActionName($context) != 'edit';
	}

	public static function onSpecialPage_initList( &$list ) {
		global $wgLanguageCode;
		$ctx = MobileContext::singleton();
		if ( $ctx->shouldDisplayMobileView() ) {
			$list['MobileLoggedOutComplete'] = 'SpecialMobileLoggedOutComplete';
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

	public function onHeaderBuilderGetCategoryLinksShowCategoryListing( &$showCategoryListing ) {
		$title = RequestContext::getMain()->getTitle();
		$showCategoryListing = $title && $title->inNamespace(NS_MAIN) && !$title->isMainPage();
	}

}
