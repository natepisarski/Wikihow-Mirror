<?php

/**
 * class for wikiHow: namespace pages
 */
class WikihowNamespacePages {

	private static $is_wikihow_namespace_page = null;
	private static $custom_collection_page = null;
	private static $custom_right_rail = null;

	/**
	 * our mobile-friendly wikiHow: namespace pages
	 * - auto-redirect to mobile on mobile devices
	 * - no auto-redirect to desktop
	 */
	private static function mobileFriendlyPages(): array {
		return [
			wfMessage( 'gdpr_mobile_menu_bottom_link' )->text(),
			wfMessage( 'gdpr_mobile_menu_top_link' )->text(),
			wfMessage( 'cookie_policy_page' )->text(),
			wfMessage('about-page')->text(),
			wfMessage('trustworthy-page')->text(),
			wfMessage('corona-guide')->text(),
			wfMessage('terms-page')->text(),
			wfMessage('community')->text(),
			'Privacy-Policy',
			"Writer's-Guide",
			'Language-Projects',
			'Hybrid-Organization',
			'History-of-wikiHow',
			'Tour',
			'Mission',
			'Creative-Commons',
			'Attribution',
			'Deletion-Policy',
			'Powered-and-Inspired-by-MediaWiki',
			'Jobs',
			'Free-Basics',
			'About-wikiHow.health',
			'About-wikiHow.legal',
			'About-wikiHow.mom',
			'About-wikiHow.fitness',
			'About-wikiHow.tech',
			'About-wikiHow.pet',
			'About-wikiHow.life',
			'About-wikiHow-fun',
			'Deletion-Policy',
			'Attribution',
			wfMessage('contact-page')->text()
		];
	}

	public static function isAvailableToAnons($title): bool {
		return self::customCollectionPage() ||
			ArticleTagList::hasTag( 'project_pages_anon', $title->getArticleID() );
	}

	public static function anonAvailableTalkPages(): array {
		$list = [
			'Article-Review-Team',
		];
		return $list;
	}

	public static function mobileWithStyle(): array {
		return [
			wfMessage('about-page')->text(),
			wfMessage('trustworthy-page')->text(),
			wfMessage('terms-page')->text(),
			wfMessage('corona-guide')->text(),
			'Jobs',
			'Mission',
			'Privacy-Policy',
			'About-wikiHow.health',
			'About-wikiHow.legal',
			'About-wikiHow.mom',
			'About-wikiHow.fitness',
			'About-wikiHow.tech',
			'About-wikiHow.pet',
			'About-wikiHow.life',
			'About-wikiHow-fun',
			'Deletion-Policy'
		];
	}

	public static function listAnonTalkLinksAvailable(): array {
		return ['Help-Team', 'Article-Review-Team'];
	}

	public static function wikiHowNamespacePage(): bool {
		if (!is_null(self::$is_wikihow_namespace_page)) return self::$is_wikihow_namespace_page;

		$context = RequestContext::getMain();
		$out = $context->getOutput();

		$action = Action::getActionName($context);
		$diff_num = $out->getRequest()->getVal('diff', '');
		$title = $out->getTitle();
		$wikihow_ns_page = $title ? $title->inNamespace(NS_PROJECT) : false;

		self::$is_wikihow_namespace_page =
			$action === 'view' &&
			empty($diff_num) &&
			$wikihow_ns_page;

		return self::$is_wikihow_namespace_page;
	}

	private static function aboutWikihowPage(): bool {
		return self::isWikihowNamespacePage( wfMessage('about-page')->text() );
	}

	private static function isWikihowNamespacePage( string $dbkey = '' ): bool {
		if ($dbkey == '') return false;

		$title = RequestContext::getMain()->getTitle();
		if (!$title) return false;

		return self::wikiHowNamespacePage() && $title->getDBkey()	== $dbkey;
	}

	public static function showMobileAboutWikihow(): bool {
		$mobile_about_languages = ['en', 'es'];
		return in_array(RequestContext::getMain()->getLanguage()->getCode(), $mobile_about_languages) &&
					!Misc::isAltDomain() &&
					wfMessage('about-page')->exists();
	}

	public static function getAboutPagePressSidebox(): string {
		return class_exists('PressBoxes') && self::aboutWikihowPage() ? PressBoxes::pressSidebox() : '';
	}

	public static function removeSideBarCallback( &$showSideBar ) {
		$showSideBar = false;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::wikiHowNamespacePage()) {
			$isResponsive = Misc::doResponsive( RequestContext::getMain() );

			Misc::setHeaderMobileFriendly();

			$title = $out->getTitle();

			if (self::aboutWikihowPage()) {
				$out->setPageTitle(wfMessage('aboutwikihow')->text());
				$module = $isResponsive ? 'ext.wikihow.press_boxes' : 'ext.wikihow.press_boxes_desktop';
				$out->addModuleStyles( $module );
			}
			elseif (self::isWikihowNamespacePage( wfMessage('trustworthy-page')->text() )) {
				$h1 = wfMessage('trustworthy-h1')->text();
				$out->setPageTitle($h1); //fancy h1
				$out->setHTMLTitle(wfMessage('trustworthy-title')->text());
				$out->addModuleStyles('ext.wikihow.trustworthy_styles');

				$out->setRobotPolicy('index,follow');
			}
			elseif (self::isWikihowNamespacePage( wfMessage('corona-guide')->text() )) {
				$out->setPageTitle( wfMessage('corona-guide-title')->text() );
				$out->addModules('ext.wikihow.corona_guide');
			}
			elseif (self::isWikihowNamespacePage( wfMessage('contact-page')->text() )) {
				$out->setPageTitle( wfMessage('contact-title')->text() );
			}
			elseif (self::isWikihowNamespacePage( "WikiHow-Teacher's-Corner" )) {
				$out->addModules('ext.wikihow.teachers_guide');
			}

			if (self::customCollectionPage()) {
				$out->addModuleStyles('ext.wikihow.article_tiles');
				$out->setPageTitle( $title->getText() );

				if (!self::customRightRailPage()) {
					global $wgHooks;
					$wgHooks['UseMobileRightRail'][] = ['WikihowNamespacePages::removeSideBarCallback'];
				}
			}

			if (in_array($title->getDBkey(), self::mobileWithStyle()) || self::customCollectionPage()) {
				$out->addModuleStyles('mobile.wikihow.wikihow_namespace_styles');
			}
		}
	}

	public static function customCollectionPage(): bool {
		if (is_null(self::$custom_collection_page)) {
			self::$custom_collection_page = self::hasMagicWord('custom_collection');
		}

		return self::$custom_collection_page;
	}

	public static function customRightRailPage(): bool {
		if (is_null(self::$custom_right_rail)) {
			self::$custom_right_rail = self::hasMagicWord('custom_right_rail');
		}

		return self::$custom_right_rail;
	}

	private static function hasMagicWord( string $magic_word = '' ): bool {
		if ($magic_word == '') return false;

		$title =  RequestContext::getMain()->getTitle();
		if (!$title || !$title->exists()) return false;

		$revision = Revision::newFromTitle( $title );
		if (!$revision) return false;

		$content = $revision->getContent();

		return $content && $content->matchMagicWord( MagicWord::get( $magic_word ) );
	}

	public static function onWikihowTemplateShowTopLinksSidebar(bool &$showTopLinksSidebar) {
		if (self::aboutWikihowPage()) $showTopLinksSidebar = false;
	}

	//this uses the phpQuery object
	public static function onMobileProcessArticleHTMLAfter(OutputPage $out) {
		$goodAboutPage = self::aboutWikihowPage() && self::showMobileAboutWikihow();

		if ($goodAboutPage) {
			foreach (pq('.section.steps') as $step) {
				$section_title = trim(pq($step)->find('h3 span')->text());

				if ($section_title == trim(wfMessage('section_title_before_mobile_pressbox')->text())) {
					//add press box after the designated section
					pq($step)->after(PressBoxes::pressSidebox());
				}
				elseif ($section_title == trim(wfMessage('section_title_for_slideshow')->text())) {
					//remove the slideshow because it looks bad on mobile
					pq($step)->remove();
				}
			}
		}

		//special table of contents
		if ($goodAboutPage || self::isWikihowNamespacePage('Jobs')) pq('#method_toc')->addClass('whns_toc');

		if (self::customCollectionPage()) pq('.section')->removeClass('sticky');
	}

	public static function onIsEligibleForMobile( &$mobileAllowed ) {
		if (self::wikiHowNamespacePage()) {
			//safe to run w/o checks because the IF already validated it all
			$title = RequestContext::getMain()->getOutput()->getTitle();
			if (in_array($title->getDBkey(), self::mobileFriendlyPages()) || self::customCollectionPage()) {
				$mobileAllowed = true;
			}
		}
	}

	public static function onGetDoubleUnderscoreIDs(&$magic_array) {
		$magic_array[] = 'custom_collection';
		$magic_array[] = 'custom_right_rail';
	}
}
