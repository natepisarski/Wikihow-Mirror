<?php

/*
 * Specialized version of the MinervaTemplate with wikiHow customizations
 */
class MinervaTemplateWikihow extends MinervaTemplate {
	/**
	 * @var Boolean
	 */

	protected $isMainPage;
	protected $isArticlePage;
	protected $isUserPage;
	protected $isSearchPage;
	protected $showCurrentArticle;
	protected $breadCrumbs = '';

	public function execute() {
		$this->isMainPage = $this->getSkin()->getTitle()->isMainPage();
		$title = $this->getSkin()->getTitle();
		$action = $this->getSkin()->getRequest()->getVal('action', 'view');
		$this->isArticlePage = $title && !$this->isMainPage && $title->inNamespace(NS_MAIN) && $action == 'view';
		$this->isSearchPage = preg_match('@/wikiHowTo@',  $_SERVER['REQUEST_URI']);
		$this->isUserPage = $title && $title->inNamespaces(NS_USER, NS_USER_TALK, NS_USER_KUDOS);
		$this->showCurrentArticle = $this->getSkin()->getTitle()->exists() && PagePolicy::showCurrentTitle($this);
		$this->breadCrumbs = WikihowHeaderBuilder::getBreadcrumbs();
		parent::execute();
	}

	public function getWikihowTools() {
		return $this->data['wikihow_urls'];
	}

	private function getPageCenterHtml( $data ) {
		$pageCenterInner = '';

		// TODO this does not need to be so high up in the page load!
		$pageCenterInner .= $this->getGDPRHtml( $data );

		if (class_exists('ArticleTopMessage')) {
			$pageCenterInner .= ArticleTopMessage::showArticleTopMessage();
		}

		$pageCenterInner .= $this->getBannersHtml( $data );

		if ($this->isMainPage) {
			$pageCenterInner .= WikihowMobileHomepage::showTopSection();
		}

		$pageCenterInner .= $this->getContentWrapper( $data );

		$pageCenterInner .= Html::openElement( 'br', ['class' => 'clearall'] );

		$pageCenterInner .= SchemaMarkup::getSchema( $this->getSkin()->getOutput() );

		$pageCenterInner .= $this->getFooterHtml( $data );

		$pageCenterHtml = Html::rawElement( 'div', ['id' => 'mw-mf-page-center'], $pageCenterInner );

		return $pageCenterHtml;
	}

	private function getViewportHtml( $data ) {
		$innerHtml = '';
		$innerHtml .= $this->getPageLeftHtml( $data );
		$innerHtml .= $this->getPageCenterHtml( $data );

		// JRS 06/23/14 Add a hook to add classes to the top-level viewport object
		// to make it easier to customize css based on classes
		$viewportClass = [];
		if ( !$data['rightRailHtml'] ) {
			$viewportClass[] = 'no_sidebar';
		}
		Hooks::run( 'MinervaViewportClasses', array( &$viewportClass ) );
		$viewport = Html::rawElement( 'div', ['id' => 'mw-mf-viewport', 'class' => $viewportClass], $innerHtml );
		return $viewport;
	}

	protected function getPreContentHtml( $data ) {
		global $wgLanguageCode, $wgUser;

		$preContentHtml = '';

		//Scott - use this hook to tweak display title
		Hooks::run( 'MobilePreRenderPreContent', array( &$data ) );

		$internalBanner = $data[ 'internalBanner' ];
		$isSpecialPage = $this->isSpecialPage;
		$preBodyText = isset( $data['prebodytext'] ) ? $data['prebodytext'] : '';

		if ( $internalbanner || $preBodyText ) {
			//XXCHANGED: BEBETH 2/3/2015 to put in unnabbed alert
			$skin = $this->getSkin();
			$title = $skin->getTitle();
			if ($wgLanguageCode == "en" && $title->inNamespace(NS_MAIN) && !NewArticleBoost::isNABbedNoDb($title->getArticleID())) {
				/* Show element if showdemoted option is enabled */
				$style = ($wgUser->getOption('showdemoted') == '1') ? "style='display:block'" : '';
				$topAlerts =  "<div class='unnabbed_alert_top' $style>" . wfMessage('nab_warning_top')->parse() . "</div>";
				$preContentHtml .= $topAlerts;
			}

			$preContentInner = '';
			// FIXME: Temporary solution until we have design
			if ( isset( $data['_old_revision_warning'] ) ) {
				$preContentInner .= $data['_old_revision_warning'];
				//XX CHANGED: BEBETH
			}
			//XXCHANGED: BEBETH
			$preContentInner .= $preBodyText;
			$preContentInner .= $internalBanner;
			$preContentInner = $preContentHtml . $preContentInner;
			$preContent .= Html::rawElement( 'div', ['class' => 'pre-content'], $preContentInner );

			return $preContent;
		}
	}

	private function getContentWrapper( $data ) {
		$content = '';

		$content .= $this->getTopContentJS( $data );

		$content .= $this->getActionBarHtml();

		$content .= $this->getContentInner( $data );

		$content .= $data['rightRailHtml'];

		$content .= Html::openElement( 'br', ['class' => 'clearall'] );

		$contentWrapper = Html::rawElement( 'div', ['id' => 'content_wrapper', 'role' => 'main'], $content );

		return $contentWrapper;
	}

	private function getContentInner( $data ) {
		$contentInner = '';

		if ( !$data['amp'] ) {
			$contentInner .= "<script>if (typeof mw != 'undefined') { mw.mobileFrontend.emit( 'header-loaded' ); }</script>";
		}

		if ( class_exists('MobileAppCTA') ) {
			$cta = new MobileAppCTA();
			if ($cta->isTargetPage()) {
				$contentInner .= $cta->getHtml();
			}
		}

		//was: $this->renderContent( $data );
		// NOTE: we don't call parent::render() because it adds the
		// header before the content, which we've already added. We
		// might need to consult that function, which is in the file
		// /prod/skins/MinervaNeue/includes/skins/SkinMinerva.php
		// in case there are things missing from our mobile page that
		// would be displayed in vanilla Mediawiki MobileFrontend.
		$contentInner .= $this->getPreContentHtml( $data );
		$contentInner .= $this->getContentHtml($data);

		$result = Html::rawElement( 'div', ['id' => 'content_inner'], $contentInner );
		return $result;
	}

	protected function getMainMenuHtml( $data ) {
		$result = '';

		$discoveryItems = '';
		foreach( $this->get('discovery_urls') as $key => $val ) {
			$discoveryItems .= $this->makeListItem( $key, $val );
		}
		$discovery = Html::rawElement( 'ul', [], $discoveryItems );

		$personalItems = '';
		foreach( $this->get('personal_urls') as $key => $val ) {
			$personalItems .= $this->makeListItem( $key, $val );
		}
		$personal = Html::rawElement( 'ul', [], $personalItems );
		$result = $discovery . $personal;
		return $result;
	}

	protected function getFooterHtml( $data ) {
		global $IP;

		$footerHtml = '';

		if ($this->isArticlePage && $data['titletext'])
			$footerPlaceholder = wfMessage('howto', $data['titletext'])->text();
		elseif ($this->isMainPage)
			$footerPlaceholder = wfMessage('hp_search_placeholder')->text();
		else
			$footerPlaceholder = wfMessage('footer-search-placeholder')->text();

		Hooks::run( 'MobileTemplateBeforeRenderFooter', array( &$footerPlaceholder ) );

		if ( !$data['disableSearchAndFooter'] ) {

			if ($data['amp']) {
				$search_box = GoogleAmp::getSearchBar( "footer_search", $footerPlaceholder );
			}
			else {
				EasyTemplate::set_path( $IP.'/extensions/wikihow/mobile/' );
				$search_box = EasyTemplate::html(
					'search-box.tmpl.php',
					[
						'id' => 'search_footer',
						'placeholder' => $footerPlaceholder,
						'class' => '',
						'lang' => RequestContext::getMain()->getLanguage()->getCode(),
						'form_id' => 'cse-search-box-bottom'
					]
				);
			}

			$vars = [
				'disableFooter' => @$data['disableFooter'],
				'mainLink' => Title::newMainPage()->getLocalURL(),
				'logoImage' => '/skins/owl/images/wikihow_logo_intl.png',
				'imageAlt' => 'wikiHow',
				'crumbs' => $this->breadCrumbs,
				'searchBox' => $search_box,
				'links' => $this->footerLinks( $data['amp'] ),
				'socialFooter' => class_exists('SocialFooter') ? SocialFooter::getSocialFooter() : '',
				'slider' => class_exists('Slider') ? Slider::getBox() : '',
				'amp' => $data['amp']
			];

			$footerHtml = $this->footerHtml($vars);
		}

		if (class_exists("MobileSlideshow")) {
			$footerHtml .= MobileSlideshow::getHtml();
		}

		return $footerHtml;
	}

	protected function footerHtml(array $vars): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/../../templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render('footer.mustache', $vars);
	}

	protected function footerLinks( $amp ): array {
		$is_intl = RequestContext::getMain()->getLanguage()->getCode() != 'en';

		$links = [
			['link' => wfMessage('footer_home')->parse()]
		];

		if (!wfMessage('footer_about_wh')->isBlank())
			$links[] = ['link' => wfMessage('footer_about_wh')->parse()];

		$links[] = ['link' => wfMessage('footer_experts')->parse()];

		if (!$is_intl && !AlternateDomain::onAlternateDomain())
			$links[] = ['link' => wfMessage('footer_jobs')->parse()];

		if (!$is_intl)
			$links[] = ['link' => wfMessage('footer_contact_us')->parse()];

		$links[] = ['link' => wfMessage('footer_site_map')->parse()];

		$links[] = ['link' => wfMessage('footer_terms')->parse()];

		$ccpaHref = '#';
		if ( $amp ) {
			$ccpaHref = '?open_ccpa=1';
		}
		$links[] = ['link' => Html::element( 'a', ['id' => 'footer_ccpa', 'href' => $ccpaHref], wfMessage('footer_ccpa')->parse() ) ];
		$links[] = ['link' => Html::element( 'a', ['id' => 'footer_ccpa_optout', 'href' => '#'], wfMessage('footer_ccpa_optout')->parse() ) ];

		if ( $amp ) {
			if ( !class_exists( 'AndroidHelper' ) || !AndroidHelper::isAndroidRequest() ) {
				$consentHtml = GoogleAmp::getConsentElement();
				$links[] = ['link' => $consentHtml];
			}
		}

		return $links;
	}

	protected function renderMetaSections() {
		//NO META SECTIONS FOR YOU!
	}

	private function generateTwitterLink() {
		global $wgCanonicalServer;

		$title = $this->getSkin()->getContext()->getTitle();

		$howto = $wgLanguageCode != 'de' ? wfMessage('howto', htmlspecialchars($title->getText()))->text() : htmlspecialchars($title->getText());
		$twitterlink = "https://www.twitter.com/intent/tweet?";
		$twitterlink .= "&text=" . urlencode($howto);
		$twitterlink .= "&via=wikihow";
		$twitterlink .= "&url=" . urlencode($wgCanonicalServer . '/' . $title->getPartialURL());
		$twitterlink .= "&related=" . urlencode("JackH:Founder of wikiHow");
		$twitterlink .="&target=_blank";
		return $twitterlink;
	}

	// this function exists in this class instead of the google amp
	// helper class because it calls some protected functions on the class
	private function getAmpSidebarHtml() {
		$items = '';
		foreach( $this->get('discovery_urls') as $key => $val ) {
			$items .= $this->makeListItem( $key, $val );
		}
		return GoogleAmp::getAmpSidebar( $items );
	}

	private function getTopContentJS( $data ) {
		if ( $data['amp'] ) {
			return;
		}
		$rightRail = $data['rightrail'];
		$html = $rightRail->mAds->getTopAdsJavascript();
		return $html;
	}

	private function getGDPRHtml( $data ) {
		$result = '';
		if ( !class_exists( 'GDPR' ) ) {
			return $result;
		}
		if ( $data['amp'] ) {
			return $result;
		}

		if ( $this->isMainPage || $this->isArticlePage || $this->isSearchPage ) {
			$result = GDPR::getHTML();
			$result .= GDPR::getInitJs();
		}
		return $result;
	}

	private function getBannersHtml( $data ) {
		$result = '';
		foreach( $this->data['banners'] as $banner ) {
			$result .= $banner;
		}
		return $result;
	}

	private function getRightRailHtml( $data ) {
		$result = '';
		if ( $data['amp'] ) {
			return $result;
		}

		$rightRailHtml = '';
		$customSideBar = '';
		Hooks::run('CustomSideBar', array(&$customSideBar));

		if ($customSideBar) {
			$widgets = $this->getSkin()->mSidebarWidgets;

			if (count($widgets)) {
				foreach($widgets as $sbWidget) {
					$rightRailHtml .= $sbWidget;
				}
			}
		} else {
			$rightRail = $data['rightrail'];
			$rightRailHtml = $rightRail->getRightRailHtml();
		}

		$result = Html::rawElement( 'div', ['id' => 'sidebar'], $rightRailHtml );

		return $result;
	}

	private function getPageLeftHtml( $data ) {
		// in amp mode we have to add the header as a direct decendent of <body>
		// so the sidebar is added in a different place
		if ( $data['amp'] ) {
			return '';
		}

		// Don't show desktop link to anons if the page is noindex
		$desktopLink = $this->data['mobile-switcher'];

		$innerHtml = $this->getMainMenuHtml( $data );
		$innerHtml .= $desktopLink;

		$pageLeftHtml = Html::rawElement( 'div', ['id' => 'mw-mf-page-left'], $innerHtml );

		return $pageLeftHtml;
	}

	private function getHeaderSearch( $data ) {
		if ( $data['disableSearchAndFooter'] ) {
			return $data['specialPageHeader'];
		}

		$query = $this->isSearchPage ? $this->getSkin()->getRequest()->getVal( 'search', '' ) : '';
		$query = filter_var( $query, FILTER_SANITIZE_STRING );
		$classes = [];
		if ( $this->isSearchPage ) {
			$classes[] = 'hs_active';
		}
		if ( $data['secondaryButtonData'] ) {
			$classes[] = 'hs_notif';
		}
		$label = wfMessage('aria_search')->showIfExists();

		$inputAttr = [
			'id' => 'hs_query',
			'type' => 'text',
			'role' => 'textbox',
			'tabindex' => '0',
			'name' => 'search',
			'value' => $query,
			'aria-label' => $label,
			'required' => '',
			'placeholder' => '',
			'x-webkit-speech' => ''
		];

		if ( $data['amp'] ) {
			$inputAttr['on'] = 'tap:hs.toggleClass(class="hs_active",force=true)';
			unset( $inputAttr['x-webkit-speech'] );
		}

		$formInput = Html::openElement( 'input', $inputAttr );

		$buttonAttr = [ 'type' => 'submit', 'id' => 'hs_submit' ];
		$formButton = Html::element( 'button', $buttonAttr );

		$closeAttr = [ 'id' => 'hs_close', 'role' => 'button', 'tabindex' => '0' ];
		if ( $data['amp'] ) {
			$closeAttr['on'] = 'tap:hs.toggleClass(class="hs_active",force=false)';
		}
		$formClose = Html::element( 'div', $closeAttr );

		$formAttributes = [ 'action' => '/wikiHowTo', 'class' => 'search', 'target' => '_top', 'id' => 'search_form_top' ];
		$formInner = $formInput . $formButton . $formClose;
		$searchForm = Html::rawElement( 'form', $formAttributes, $formInner );

		$outerAttr = array(
			'id' => 'hs',
			'class' => $classes
		);
		$outer = Html::rawElement( 'div', $outerAttr, $searchForm );

		return $outer;
	}

	private function getActionBarHtml() {
		$articleTabs = WikihowHeaderBuilder::getArticleTabs();
		if ( !$articleTabs && !$this->breadCrumbs ) {
			return '';
		}

		$actionBarContents = $articleTabs;

		if ( $this->breadCrumbs ) {
			$breadCrumbsAttr = [
				'id' => 'breadcrumb',
				'class' => 'breadcrumbs',
			];
			$ariaBreadCrumbs = wfMessage('aria_breadcrumbs')->showIfExists();
			if ( $ariaBreadCrumbs ) {
				$breadCrumbsAttr['aria-label'] = $ariaBreadCrumbs;
			}
			$breadCrumbHtml = Html::rawElement( 'ul', $breadCrumbsAttr, $this->breadCrumbs );
			$actionBarContents .= $breadCrumbHtml;
		}

		$actionBarAttr = [
			'id' => 'actionbar',
			'role' => 'navigation'
		];
		$actionBar = Html::rawElement( 'div', $actionBarAttr, $actionBarContents );
		return $actionBar;
	}

	protected function getHeaderContainer( $data ) {
		$headerContents = '';

		$headerContents .= $data['menuButton'];

		if ( $data['amp'] ) {
			$headerContents .= GoogleAmp::getHeaderSidebarButton();
		}

		$headerLogoAttr = [
			'id' => 'header_logo',
			'href' => Title::newMainPage()->getLocalURL(),
		];
		$headerLogoClass = '';
		Hooks::run( 'MinervaTemplateWikihowBeforeCreateHeaderLogo', array( &$headerLogoClass ) );
		if ( $headerLogoClass ) {
			$headerLogoAttr['class'] = $headerLogoClass;
		}
		$headerLogoHtml = Html::element( 'a', $headerLogoAttr );
		$headerContents .= $headerLogoHtml;

		if ( !( Misc::isAltDomain() ) ) {
			$noScriptLogoAttr = [
				'id' => 'noscript_header_logo',
				'href' => '/Hello',
				'class' => 'hide',
			];
			if ( $headerLogoClass ) {
				$noScriptLogoAttr['class'] = 'hide ' . $headerLogoClass;
			}
			$noScriptLogo = Html::element( 'a', $noScriptLogoAttr );
		}

		$headerContents .= $this->getHeaderSearch( $data );

		$headerContents .= $this->getActionsMenubarHtml( $data );

		$headerContents .= $data['secondaryButtonData'];

		$headerHtmlAttr = [
			'id' => 'header',
			'class' => 'header',
			'role' => 'navigation'
		];
		$headerHtml = Html::rawElement( 'div', $headerHtmlAttr, $headerContents );

		$headerContainerAttr = [
			'id' => 'header_container'
		];
		if ( $data['is_responsive'] ) {
			$headerContainerAttr['data-responsive'] = "1";
		}

		$headerContainer = Html::rawElement( 'div', $headerContainerAttr, $headerHtml );
		if ( $data['fastRenderTest']  ) {

			// add js to open the menu
			$snippet = "<script>var menu = document.getElementById('mw-mf-main-menu-button');
				menu.addEventListener('click', function(){
					var menu = document.getElementById('mw-mf-viewport');
					menu.classList.toggle('menuopen');
					//var body = document.getElementsByTagName('body')[0];
					//body.classList.toggle('navigation-enabled');
					//body.classList.toggle('primary-navigation-enabled');
				});</script>";

			$snippet .= "<style>.menuopen #mw-mf-page-left{display:block;z-index:9;}</style>";

			$headerContainer .= $snippet;
		}

		if ( $data['amp'] ) {
			$ampSidebar = $this->getAmpSidebarHtml();
			$headerContainer .= $ampSidebar;
		}
		return $headerContainer;
	}

	protected function getActionsMenubarHtml( $data ) {
		$html = '';
		if ( $data['amp'] ) {
			return $html;
		}

		$notifications = 0;
		$navTabs = WikihowHeaderBuilder::genNavigationTabs($this->getSkin(), $notifications);

		$liItems = '';
		foreach ( $navTabs as $tabId => $tab ) {
			$navIcon = Html::element( 'div', ['class' => 'nav_icon'] );

			$navAnchorAttr = [
				'id' => $tabId,
				'class' => 'nav',
				'href' => $tab['link']
			];
			if ( $tab['data-link'] ) {
				$navAnchorAttr['data-link'] = $tab['data-link'];
			}
			$navAnchor = Html::rawElement( 'a', $navAnchorAttr, $tab['text'] );

			$liAttr = [
				'id' => $tabId . '_li',
				'class' => 'nav_item',
				'role' => 'menuitem',
				'aria-labelledby' => $tabId
			];
			$liInner = $navIcon . $navAnchor . $tab['menu'];
			$liItem = Html::rawElement( 'li', $liAttr, $liInner );
			$liItems .= $liItem;
		}
		$ulAttr = [
			'id' => 'actions',
			'role' => 'menubar',
			'aria-label' => wfMessage('aria_header')->showIfExists()
		];
		$html = Html::rawElement( 'ul', $ulAttr, $liItems );
		return $html;
	}

	protected function getJSTimingScripts( $data ) {
		$result = '';
		if ( $data['amp'] ) {
			return $result;
		}
		$result = Misc::getTTIBody();
		$result .= Misc::getFIDBody();
		return $result;
	}

	protected function getTopAdsJS( $data ) {
		$result = '';
		if ( $data['amp'] ) {
			return $result;
		}
		$result = $data['rightrail']->mAds->getGPTDefine();
		return $result;
	}


	protected function getGAConfigVars( $data ) {
		$html = '';
		if ( $data['amp'] ) {
			return $html;
		}
		EasyTemplate::set_path( __DIR__ );
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$propertyId = WH_GA_ID_ANDROID_APP; // Android app
		} elseif(class_exists('QADomain') && QADomain::isQADomain()) {
			$propertyId = WH_GA_ID_QUICKANSWERS; //QuickAnswers
		} else{
			$propertyId = WH_GA_ID; // wikihow.com;
		}

		$gaConfig = json_encode(Misc::getGoogleAnalyticsConfig());

		$html = HTML::inlineScript(
			EasyTemplate::html(
				'analytics-js.tmpl.php',
				array(
					'propertyId' => $propertyId,
					'gaConfig' => $gaConfig
				)
			)
		);

		return $html;
	}

	protected function getEndOfPageHtml( $data ) {
		if ( $data['amp'] ) {
			return '';
		}

		$html = Html::element( 'div', ['id' => 'servedtime'], Misc::reportTimeMS() );

		// adds the debug toolbar if that is enabled
		$html .= MWDebug::getDebugHTML( $this->getSkin()->getContext() );

		// adds extra scripts such as resource loader startup
		$html .= $data['bottomscripts'];

		$html .= wfReportTime();

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

		// Script to be loaded for ad blocker detection
		if (class_exists('AdblockNotice')) {
			$html .= AdblockNotice::getBottomScript();
		}

		$html .= EasyTemplate::html('wh_mobileFrontendFooter.tmpl.php', $footerVars);

		if ( isset( $data['rightrail'] ) ) {
			$html .= $data['rightrail']->mAds->getEndOfPageHtml();
		}

		return $html;
	}

	// this function will add extra js to the head of the page
	// it is in this class because it makes it simpler to orgainze where all the js is going in the page
	// to have it here even though it is called by the SkinMinervaWikihow before the rest of the
	// functions in this class are ever used. but by the time the render function in this class is called,
	// it is too late to add items to the head of the page
	public static function getAdditionalHeadItems() {
		global $wgOut;

		$result = '';
		$result .= Misc::getFCPHead();
		$result .= Misc::getTTIHead();
		$result .= Misc::getFIDHead();

		return $result;
	}

	// try to get all of the js to be included at the top of the page in one group and in one function
	protected function getTopJs( $data ) {
		$result = '';

		$result .= $this->getJSTimingScripts( $data );

		$result .= $this->getTopAdsJS( $data );

		$result .= $this->getGAConfigVars( $data );

		return $result;
	}

	protected function render( $data ) { // FIXME: replace with template engines
		$html = '';

		$fastRenderTest = false;
		if ( Misc::isFastRenderTest() ) {
			$data['fastRenderTest'] = true;
			$fastRenderTest = true;
		}

		Hooks::run( "MinvervaTemplateBeforeRender", array( &$data ) );

		$data['rightRailHtml'] = '';
		$useRightRail = true;
		Hooks::run("UseMobileRightRail", [&$useRightRail]);
		if ( $useRightRail ) {
			$data['rightRailHtml'] = $this->getRightRailHtml( $data );
		}

		// the head element includes opening and closing <head> tags
		// to add more items to the head, use the function getAdditionalHeadItems above
		$headElementHtml = $data[ 'headelement' ];
		echo $headElementHtml;

		// GA config variables, js timing scripts and js for ads
		$topJs = $this->getTopJs( $data );
		echo $topJs;

		// this is the html for the top menu
		$headerContainer = $this->getHeaderContainer( $data );
		echo $headerContainer;

		// this contains all the actual content of the page and right rail
		$viewport = $this->getViewportHtml( $data );
		echo $viewport;

		if ( !$fastRenderTest ) {
			// adds extra js to bottom of page such as optimizely and resource loader startup
			$endOfPageHtml = $this->getEndOfPageHtml( $data );
			echo $endOfPageHtml;
		}

		$closeBody = Html::closeElement( 'body' );
		echo $closeBody;

		$closeHtml = Html::closeElement( 'html' );
		echo $closeHtml;
	}
}

