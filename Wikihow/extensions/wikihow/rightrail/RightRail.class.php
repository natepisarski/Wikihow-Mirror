<?php

class RightRail {

	public static function showSocialProofSidebar() {
		$context = RequestContext::getMain();
		$title = $context->getTitle();
		$action = Action::getActionName($context);

		$isArticlePage = $title &&
			$title->inNamespace( NS_MAIN ) &&
			!$title->isMainPage() &&
			$action == 'view';

		if ( !( $isArticlePage
			|| ( $title->inNamespace( NS_PROJECT ) && $action == 'view' )
			|| ( $title->inNamespace( NS_USER ) && $title->isSubpage() && $action == 'view' )
			|| ( $title->inNamespace( NS_CATEGORY ) && !$title->exists() ) ) ) {
			return false;
		}

		if ( $title->getArticleId() == 0 || !$title->inNamespace(NS_MAIN) ) {
			return false;
		}

		$showCurrentArticle = $title->exists() && PagePolicy::showCurrentTitle($context);
		if ( !$showCurrentArticle ) {
			return false;
		}

		if ( $context->getUser()->getIntOption('showarticleinfo') != 1 ) {
			return false;
		}

		return true;
	}
	/*
	* this is a kind of constructor/helper to create a new right rail object
	* since maby of the arguments to the constructor of the RightRail class are
	* created from globals, this function will make help create the arguments for the constructor
	* it will be used by mobile first then refactor the desktop page to use it later
	*/
	public static function createRightRail( $skin ) {
		global $wgTitle, $wgUser, $wgLanguageCode, $wgRequest;

		// TODO replace globals with stuff from temlpate?

		$action = $wgRequest->getVal('action', 'view');

		$isMainPage = $wgTitle
			&& $wgTitle->inNamespace( NS_MAIN )
			&& $wgTitle->getText() == wfMessage( 'mainpage' )->inContentLanguage()->text()
			&& $action == 'view';

		$context = $skin->getContext();
		$ads = new Ads( $context, $wgUser, $wgLanguageCode, array(), $isMainPage );

		$isLoggedIn = $wgUser->getID() > 0;
		$isArticlePage = $wgTitle && !$isMainPage && $wgTitle->inNamespace( NS_MAIN ) && $action == 'view';
		$isEnglishAnonView = !$isLoggedIn && $wgLanguageCode == 'en' && $isArticlePage;

		$isDocViewer = substr( $wgTitle->getText(), 0, 9 ) === "DocViewer";

		$isResponsive = Misc::isMobileMode();

		$showRCWidget =
			class_exists('RCWidget') &&
			!$wgTitle->inNamespace(NS_USER) &&
			(!$isLoggedIn || $wgUser->getOption('recent_changes_widget_show', true) == 1 ) &&
			($isLoggedIn || $isMainPage) &&
			!in_array($wgTitle->getPrefixedText(),
				array('Special:Avatar', 'Special:ProfileBox')) &&
			strpos($wgTitle->getPrefixedText(), 'Special:UserLog') === false &&
			!$isDocViewer &&
			$action != 'edit';

		$relatedWikihows = new RelatedWikihows( $context, $context->getUser());

		// TODO add adblock notice
		$siteNotice = $isMainPage || $isDocViewer ? '' : WikihowSkinHelper::getSiteNotice();
		$cookieNotice = $isMainPage || $isDocViewer ? '' : WikihowSkinHelper::getCookieNotice();

		Hooks::run( "WikihowTemplateAfterCreateNotices", array( &$siteNotice, &$cookieNotice, &$adblockNotice ) );

		$socialProofSidebar = '';
		if ( !Misc::isIntl() && self::showSocialProofSidebar() && !$isMainPage) {
			$parenttree = CategoryHelper::getCurrentParentCategoryTree();
			$fullCategoryTree = CategoryHelper::cleanCurrentParentCategoryTree( $parenttree );
			$sp = new SocialProofStats($context, $fullCategoryTree);
			$socialProofSidebar = $sp->getDesktopSidebarHtml();
		}

		$showWikiTextWidget = class_exists( 'WikitextDownloader' )
			&& WikitextDownloader::isAuthorized()
			&& !$isDocViewer
			&& !$isEnglishAnonView;

		$showStaffStats = !$isMainPage
			&& $isLoggedIn
			&& Misc::isUserInGroups( $wgUser, ['staff', 'staff_widget', 'editor_team'] )
			&& $wgTitle->inNamespace( NS_MAIN )
			&& class_exists( 'PageStats' );

		$showGraphs = $showStaffStats && Misc::isUserInGroups( $wgUser, ['staff', 'staff_widget'] );

		$showPageHelpfulness = !$isMainPage
			&& $isLoggedIn
			&& ( Misc::isUserInGroups( $wgUser, array( 'staff', 'staff_widget', 'newarticlepatrol', 'sysop', 'editor_team' ) ) )
			&& $wgTitle->inNamespace( NS_MAIN )
			&& class_exists( 'PageHelpfulness' )
			&& $wgUser->getIntOption( 'showhelpfulnessdata' ) == 1;

		$showMethodHelpfulness = !$isMainPage
			&& $isLoggedIn
			&& ( Misc::isUserInGroups( $wgUser, array( 'staff', 'staff_widget', 'newarticlepatrol', 'sysop' ) ) )
			&& $wgTitle->inNamespace( NS_MAIN )
			&& class_exists( 'MethodHelpfulness\MethodHelpfulness' )
			&& $wgUser->getIntOption( 'showhelpfulnessdata' ) == 1;

		$showVideoBrowserWidget =
			class_exists('VideoBrowser') &&
			$isMainPage &&
			!$isResponsive &&
			in_array($wgLanguageCode, array('en'));


		$userCompletedImagesSidebar = null;
		if ( class_exists( 'UserCompletedImages' ) && $wgTitle->exists() &&
			$wgTitle->getArticleId() != 0 && $wgTitle->inNamespace( NS_MAIN ) && !$isEnglishAnonView ) {
			$userCompletedImagesSidebar = UserCompletedImages::getDesktopSidebarHtml( $context );
		}

		$press_sidebox = '';
		if ( class_exists( 'WikihowNamespacePages' ) ) {
			$press_sidebox = WikihowNamespacePages::getAboutPagePressSidebox();
		}
		// TODO this
		//if ( $userCompletedImagesSidebar ) {
		//$out->addModules('ext.wikihow.usercompletedimages');
		//}

		$rightRail = new RightRail( $skin, $wgUser, $wgLanguageCode, $isMainPage, $action, $ads, $isEnglishAnonView, $isDocViewer, $showRCWidget, $relatedWikihows, $siteNotice, $cookieNotice, $socialProofSidebar, $showWikiTextWidget, $showStaffStats, $showGraphs, $showPageHelpfulness, $showMethodHelpfulness, $showVideoBrowserWidget, $userCompletedImagesSidebar, $press_sidebox, $isResponsive );
		return $rightRail;
	}

	public function __construct( $skin, $user, $languageCode, $isMainPage, $action, $ads, $isEnglishAnonView, $isDocViewer, $showRCWidget, $relatedWikihows, $siteNotice, $cookieNotice, $socialProofSidebar, $showWikiTextWidget, $showStaffStats, $showGraphs, $showPageHelpfulness, $showMethodHelpfulness, $showVideoBrowserWidget, $userCompletedImagesSidebar, $pressSidebox, $isResponsive ) {
		$this->mSkin = $skin;
		$this->mTitle = $skin->getContext()->getTitle();
		$this->mUser = $user;
		$this->mLanguageCode = $languageCode;
		$this->mEnglishSite = $languageCode == "en";
		$this->mContext = $skin->getContext();
		$this->mIsMainPage = $isMainPage;
		$this->mAction = $action;
		$this->mShowCurrentArticle = $this->mTitle->exists() && PagePolicy::showCurrentTitle( $this->mContext );
		$this->mAlternateDomain = class_exists( 'AlternateDomain' ) && AlternateDomain::onAlternateDomain();
		$this->mAds = $ads;
		$this->mDesktopAds = $desktopAds;
		$this->mIsEnglishAnonView = $isEnglishAnonView;
		$this->mIsDocViewer = $isDocViewer;
		$this->mIsLoggedIn = $this->mUser->getID() > 0;
		$this->mShowRCWidget = $showRCWidget;
		$this->mRelatedWikihows = $relatedWikihows;

		$this->mSiteNotice = $siteNotice;
		$this->mCookieNotice = $cookieNotice;
		$this->mSocialProofSidebar = $socialProofSidebar;
		$this->mPageId = 0;
		$this->mPageId = $this->mTitle->getArticleID();
		$this->mShowWikiTextWidget = $showWikiTextWidget;
		$this->mShowStaffStats = $showStaffStats;
		$this->mShowGraphs = $showGraphs;
		$this->mShowPageHelpfulness = $showPageHelpfulness;
		$this->mShowMethodHelpfulness = $showMethodHelpfulness;
		$this->mIsMainNamespace = $this->mTitle->inNamespace( NS_MAIN );
		$isArticlePage = !$this->mIsMainPage && $this->mIsMainNamespace && $this->mAction == 'view';
		$this->mIsAnonView = !$this->mIsLoggedIn && $isArticlePage;
		$this->mLoggedOutClass = "";
		if ( $ads->isActive() && $this->mTitle->getText() != 'UserLogin' && $this->mIsMainNamespace ) {
			$this->mLoggedOutClass = ' logged_out';
		}
		$this->mShowVideoBrowserWidget = $showVideoBrowserWidget;
		$this->mUserCompletedImagesSidebar = $userCompletedImagesSidebar;
		$this->mPressSidebox = $pressSidebox;
		$this->mIsResponsive = $isResponsive;
	}

	public function getRightRailHtml() {
		if ($this->mIsMainPage) {
			$html = !AlternateDomain::onAlternateDomain() ? self::getHomepageRightRailHtml() : '';
		}
		else {
			$html = self::getRightRailHtmlTop();
			$html .= self::getRightRailHtmlBottom();
			$html = $this->mAds->modifyRightRailForAdTest( $html, $this->mRelatedWikihows );
		}

		return $html;
	}

	public function getFCWidgetHtml() {
		if ( !class_exists( 'FeaturedContributor' ) ) {
			return '';
		}

		if ( !$this->mTitle->inNamespaces( NS_MAIN, NS_USER ) ) {
			return '';
		}

		if ( $this->mIsMainPage ) {
			return false;
		}

		if ( $this->mIsDocViewer ) {
			return false;
		}

		if ( $this->mIsEnglishAnonView ) {
			return false;
		}

		$innerHtml = FeaturedContributor::getWidgetHtml();

		$message = wfMessage( 'welcome', $this->mUser->getName(), $this->mUser->getUserPage()->getLocalURL() )->text();

		// TODO this check for is logged in seems redundant with checking for anon english view above
		if ( !$this->mIsLoggedIn ) {
			$linkAttributes = [
				'href' => '/Special:UserLogin',
				'class' => 'button secondary',
				'id' => 'gatFCWidgetBottom',
				'onclick' => 'gatTrack("Browsing","Feat_contrib_cta","Feat_contrib_wgt");'
			];
			$link = $link = Html::element( 'a', $linkAttributes, wfMessage( 'fc_action' )->text() );
			$innerHtml .= Html::rawElement( 'p', ['class' => 'bottom_button'], $link );
		}

		$vars = [
			'id' => 'side_featured_contributor',
			'contents' => $innerHtml
		];

		return $this->makeSidebox($vars);
	}

	public function getRCWidgetHtml() {
		if ( !$this->mShowRCWidget || $this->mAlternateDomain ) return '';

		$innerHtml = RCWidget::getWidgetHtml();

		$message = wfMessage( 'welcome', $this->mUser->getName(), $this->mUser->getUserPage()->getLocalURL() )->text();
		if ( !$this->mIsLoggedIn ) {
			$message = $link = Html::element(
				'a',
				['href' => "/Special:UserLogin", 'id' => "gatWidgetBottom"],
				wfMessage( 'rcwidget_join_in' )->text()
			);
		}

		$linkAttributes = [
			'id' => 'play_pause_button',
			'href' => '#',
			'onclick' => "WH.RCWidget.rcTransport(this); return false;"
		];
		$link = Html::element( 'a', $linkAttributes );

		$innerHtml .= Html::rawElement( 'p', ['class' => 'bottom_link'], $message . $link );

		$vars = [
			'id' => 'side_rc_widget',
			'contents' => $innerHtml
		];

		return $this->makeSidebox($vars);
	}

	public function getTopLinksSidebarWidgetHtml() {
		$randomizerLink = Html::element(
			'a',
			['id' => 'gatRandom', 'href' => '/Special:Randomizer', 'accesskey' => 'x', 'class' => 'button secondary'],
			wfMessage( 'randompage' )->text()
		);

		$createPageLink = Html::element(
			'a',
			['id' => 'gatWriteAnArticle', 'href' => '/Special:CreatePage', 'class' => 'button secondary'],
			wfMessage( 'writearticle' )->text()
		);

		$attr = [
			'id' => 'top_links',
			'class' => ['sidebox'],
		];
		if ( $this->mLoggedOutClass ) {
			$attr['class'][] = $this->mLoggedOutClass;
		}
		$topLinksPadding = wfMessage( 'top_links_padding')->text();
		if ( is_numeric( $topLinksPadding ) ) {
			$attr['style'] = "padding-left:{$topLinksPadding}px;padding-right:{$topLinksPadding}px;";
		}
		$html = Html::rawElement( 'div', $attr, $randomizerLink . $createPageLink );
		return $html;
	}

	public function getSidebarWidgetsHtml() {
		if ( $this->mAlternateDomain ) {
			return false;
		}
		$html = '';

		if ( $this->mSkin->mSidebarWidgets ) {
			foreach ( $this->mSkin->mSidebarWidgets as $sbWidget ) {
				$html .= $sbWidget;
			}
		}
		return $html;
	}

	// checks if this is a typical article view
	// does the very common checks if it is main page
	// action = view, in main namespace, and showCurrentArticle
	public function isNormalArticleView() {
		if ( $this->mIsMainPage ) {
			return false;
		}
		if ( !$this->mShowCurrentArticle ) {
			return false;
		}
		if ( !$this->mTitle->inNamespace( NS_MAIN ) ) {
			return false;
		}
		if ( $this->mAction != 'view' ) {
			return false;
		}
		return true;
	}

	public function getRelatedOutput() {
		if ( !self::isNormalArticleView() ) return '';

		$vars = [
			'id' => 'side_related_articles',
			'class' => 'related_articles',
			'contents' => $this->mRelatedWikihows->getSideData()
		];

		return $this->makeSidebox($vars);
	}

	// initial part of right rail
	// shared by both
	public function getRightRailHtmlTop() {
		// site notice
		$html = $this->mSiteNotice;

		// cookie notice
		$html .= $this->mCookieNotice;

		// JaTrending::getTrendingWidget();
		if ( $this->mAction == 'view' && JaTrending::showTrending() ) {
			$html .= JaTrending::getTrendingWidget();
		}

		// Social Proof Section
		if ( $this->mSocialProofSidebar ) {
			$html .= $this->mSocialProofSidebar;
		}

		if( class_exists( 'UserReview' ) ) {
			if ( $this->mTitle && !( $this->mPageId == 647971 || $this->mPageId == 110310 ) ) {
				$html .= UserReview::getSidebarReviews( $this->mPageId );
			}
		}

		if ( class_exists( 'AmazonAffiliates' ) ) {
			$aa_disclaimer = AmazonAffiliates::getSidebarDisclaimer( $this->mContext );
		}

		// at a glance test
		if ( class_exists('AtAGlance') ) {
			if ( AtAGlance::showSidebar( $this->mTitle ) ) {
				$html .= AtAGlance::getSidebarHtml();
			}
		}

		$isSearchPage = $this->mTitle->isSpecial( 'LSearch' );

		$showTopLinksSidebar = !$this->mIsDocViewer &&
			!$this->mSocialProofSidebar &&
			!$this->mIsEnglishAnonView &&
			!$isSearchPage;

		Hooks::run( 'WikihowTemplateShowTopLinksSidebar', array( &$showTopLinksSidebar ) );
		if ( $showTopLinksSidebar ) {
			$html .= $this->getTopLinksSidebarWidgetHtml();
		}

		if ( $this->mShowStaffStats ) {
			$vars = [
				'id' => 'staff_stats_box',
				'class' => 'short_sidebox'
			];
			$html .= $this->makeSidebox($vars);
		}

		if ( $this->mShowGraphs ) {
			$vars = [
				'id' => 'staff_charts_box',
				'class' => 'short_sidebox'
			];
			$html .= $this->makeSidebox($vars);
		}

		if ( $this->mShowPageHelpfulness ) {
			$classes = 'short_sidebox';
			if ( $this->mShowMethodHelpfulness ) $classes .= ' smhw';

			$vars = [
				'id' => 'page_helpfulness_box',
				'class' => $classes
			];
			$html .= $this->makeSidebox($vars);
		}

		if ( $this->mShowWikiTextWidget ) {
			$link = Html::element( 'a', ['id' => 'wikitext_downloader', 'href' => '#'], 'Download WikiText' );
			$vars = [
				'id' => 'side_wikitext_downloader',
				'contents' => $link
			];
			$html .= $this->makeSidebox($vars);
		}

		// disabling for now
		//if ( class_exists( 'Honeypot' ) && $this->mTitle->inNamespace( NS_MAIN ) && !$this->mIsMainPage && $this->mEnglishSite ) {
		//$html .= Honeypot::getDesktopWidgetHtml( $this->mContext );
		//}

		if ( $this->mAds ) {
			$html .= $this->mAds->getRightRailAdHtml( 0 );
		}

		return $html;
	}

	public function getRightRailHtmlBottom() {
		$html = '';
		$isSearchPage = $this->mTitle->isSpecial( 'LSearch' );

		if ( !$this->mIsDocViewer && $this->mSocialProofSidebar && !$this->mIsEnglishAnonView && !$isSearchPage ) {
			$html .= $this->getTopLinksSidebarWidgetHtml();
		}

		// User Completed Images Section
		if ( $this->mUserCompletedImagesSidebar ) {
			$html .= $this->mUserCompletedImagesSidebar;
		}

		$userLinks = WikihowSkinHelper::getUserLinks();
		if ( $userLinks ) {
			$html .= $this->makeSidebox([ 'contents' => $userLinks ]);
		}

		if ( $this->mPressSidebox ) {
			$html .= $this->mPressSidebox;
		}

		$html .= self::getRelatedOutput();

		if ( $this->mAds ) {
			$html .= $this->mAds->getRightRailAdHtml( 1 );
		}

		$showSocialSharing =
			$this->mTitle &&
			$this->mTitle->exists() &&
			$this->mTitle->inNamespace( NS_MAIN ) &&
			$this->mAction == 'view' &&
			!$this->mUser->isAnon() &&
			class_exists('WikihowShare');

		if ( $showSocialSharing ) {
			$header = Html::rawElement( 'h3', array() , wfMessage( 'social_share' )->text() );

			$vars = [
				'id' => 'sidebar_share',
				'class' => $this->mLoggedOutClass ?: '',
				'contents' => $header . WikihowShare::getTopShareButtons()
			];
			$html .= $this->makeSidebox($vars);

		}

		// commented out sidebox shell for side fb timelnie

		$html .= $this->getSidebarWidgetsHtml();

		// this was commented out in the original
		//if ($isLoggedIn) {
		//$html . = $navMenu;
		//}

		$showFeaturedArticlesSidebar = $this->mAction == 'view'
			&& !$this->mIsDocViewer
			&& !$this->mIsEnglishAnonView
			&& !$this->mIsAnonView
			&& !$this->mTitle->inNamespace( NS_USER )
			&& !$isSearchPage;

		Hooks::run( 'WikihowTemplateShowFeaturedArticlesSidebar', array( &$showFeaturedArticlesSidebar ) );

		if ( $showFeaturedArticlesSidebar ) {
			$vars = [
				'id' => 'side_featured_articles',
				'contents' => FeaturedArticles::getFeaturedArticlesBox(4)
			];
			$html .= $this->makeSidebox($vars);
		}

		$html .= $this->getRCWidgetHtml();
		$html .= $this->getFCWidgetHtml();

		$showFollowWidget =
			class_exists( 'FollowWidget' ) &&
			!$this->mIsDocViewer &&
			!$this->mIsEnglishAnonView &&
			!$this->mIsAnonView &&
			!$isSearchPage &&
			in_array( $this->mLanguageCode, array( 'en', 'de', 'es', 'pt' ) );

		Hooks::run( 'WikihowTemplateShowFollowWidget', array( &$showFollowWidget ) );

		if ( $showFollowWidget ) {
			$vars = [
				'id' => 'side_follow',
				'class' => 'follow_sidebox',
				'contents' => FollowWidget::getWidgetHtml( $this->mIsMainPage, $this->mTitle )
			];
			$html .= $this->makeSidebox($vars);
		}

		if ( $this->mShowVideoBrowserWidget ) {
			$vars = [
				'class' => 'videobrowser_sidebox',
				'contents' => VideoBrowser::getDesktopWidgetHtml( $context )
			];
			$html .= $this->makeSidebox($vars);
		}

		$numberOfRightRailAds = 2;

		if ( $numberOfRightRailAds > 2 && $this->mShowCurrentArticle && $this->mAction == 'view' && $this->mTitle->getNamespace() == NS_MAIN) {
			$html .= RatingArticle::getDesktopSideForm( $this->mPageId, $this->mLoggedOutClass );
		}

		if ( $this->mAds ) {
			$html .= $this->mAds->getRightRailAdHtml( 2 );
		}
		return $html;
	}

	protected function getHomepageRightRailHtml(): String {
		//CATEGORIES
		$vars = [
			'id' => 'homepage_categories',
			'contents' => WikihowHomepage::categoryWidget()
		];
		$html = $this->makeSidebox($vars);

		//TOP LINKS
		$html .= $this->getTopLinksSidebarWidgetHtml();

		//WORLDWIDE
		$langList = wfMessage('wh_in_other_langs')->text();
		$vars = [
			'contents' => wfMessage('main_page_worldwide_2', wfGetPad(), $langList)->text()
		];
		$html .= $this->makeSidebox($vars);

		//RC WIDGET
		$html .= $this->getRCWidgetHtml();

		//FOLLOW WIDGET
		$showFollowWidget =
			class_exists( 'FollowWidget' ) &&
			!$this->mIsEnglishAnonView &&
			!$this->mIsAnonView &&
			in_array( $this->mLanguageCode, array( 'en', 'de', 'es', 'pt' ) );

		Hooks::run( 'WikihowTemplateShowFollowWidget', array( &$showFollowWidget ) );

		if ( $showFollowWidget ) {
			$vars = [
				'id' => 'side_follow',
				'class' => 'follow_sidebox',
				'contents' => FollowWidget::getWidgetHtml( $this->mIsMainPage, $this->mTitle )
			];
			$html .= $this->makeSidebox($vars);
		}

		return $html;
	}

	private function makeSidebox(Array $vars = []): String {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render('right_rail_sidebox.mustache', $vars);
	}
}
