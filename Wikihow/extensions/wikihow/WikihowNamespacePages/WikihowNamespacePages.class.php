<?php

/**
 * class for wikiHow: namespace pages
 */
class WikihowNamespacePages {

	private static $is_wikihow_namespace_page = null;

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
			'Attribution'
		];
	}

	public static function isAvailableToAnons($title): bool {
		$isAvailable = ArticleTagList::hasTag( 'project_pages_anon', $title->getArticleID() );
		return $isAvailable;
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
		$title = RequestContext::getMain()->getTitle();
		if (!$title) return false;

		return self::wikiHowNamespacePage() && $title->getDBkey() == wfMessage('about-page')->text();
	}

	private static function trustworthyPage(): bool {
		$title = RequestContext::getMain()->getTitle();
		if (!$title) return false;
		return self::wikiHowNamespacePage() && $title->getDBkey() == wfMessage('trustworthy-page')->text();
	}

	private static function jobsPage(): bool {
		$title = RequestContext::getMain()->getTitle();
		if (!$title) return false;
		return self::wikiHowNamespacePage() && $title->getDBkey() == 'Jobs';
	}

	private static function coronaGuidePage(): bool {
		$title = RequestContext::getMain()->getTitle();
		if (!$title) return false;
		return self::wikiHowNamespacePage() && $title->getDBkey() == wfMessage('corona-guide')->text();
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

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::wikiHowNamespacePage()) {
			$isResponsive = Misc::doResponsive( RequestContext::getMain() );

			Misc::setHeaderMobileFriendly();

			if (self::aboutWikihowPage()) {
				$out->setPageTitle(wfMessage('aboutwikihow')->text());
				$module = $isResponsive ? 'ext.wikihow.press_boxes' : 'ext.wikihow.press_boxes_desktop';
				$out->addModuleStyles( $module );
			}
			elseif (self::trustworthyPage()) {
				$h1 = wfMessage('trustworthy-h1')->text();
				$out->setPageTitle($h1); //fancy h1
				$out->setHTMLTitle(wfMessage('trustworthy-title')->text());
				$out->addModuleStyles('ext.wikihow.trustworthy_styles');

				$out->setRobotPolicy('index,follow');
			}
			elseif (self::coronaGuidePage()) {
				$out->setPageTitle( wfMessage('corona-guide-title')->text() );
				$out->addModuleStyles('ext.wikihow.corona_guide_styles');
			}

			$title = $out->getTitle();
			if ($title) {
				if (in_array($title->getDBkey(), self::mobileWithStyle())) {
					$out->addModuleStyles('mobile.wikihow.wikihow_namespace_styles');
				}
			}
		}
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
		if ($goodAboutPage || self::jobsPage()) pq('#method_toc')->addClass('whns_toc');
	}

	public static function onIsEligibleForMobile( &$mobileAllowed ) {
		if (self::wikiHowNamespacePage()) {
			//safe to run w/o checks because the IF already validated it all
			$title = RequestContext::getMain()->getOutput()->getTitle();
			if (in_array($title->getDBkey(), self::mobileFriendlyPages())) $mobileAllowed = true;
		}
	}
}
