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
	public static function mobileFriendlyPages(): array {
		return [
			wfMessage( 'gdpr_mobile_menu_bottom_link' )->text(),
			wfMessage( 'gdpr_mobile_menu_top_link' )->text(),
			wfMessage( 'cookie_policy_page' )->text(),
			wfMessage('about-page')->text(),
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
			'Free-Basics'
		];
	}

	public static function mobileWithStyle(): array {
		return [
			wfMessage('about-page')->text(),
			'Jobs',
			'Mission'
		];
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
			Misc::setHeaderMobileFriendly();

			if (self::aboutWikihowPage()) {
				$out->setPageTitle(wfMessage('aboutwikihow')->text());
				$out->addModules('ext.wikihow.press_boxes');
			}

			$title = $out->getTitle();
			if ($title) {
				if (in_array($title->getDBkey(), self::mobileWithStyle())) {
					$out->addModules('mobile.wikihow.wikihow_namespace_styles');
				}
			}
		}
	}

	public static function onWikihowTemplateShowTopLinksSidebar(bool &$showTopLinksSidebar) {
		if (self::aboutWikihowPage()) $showTopLinksSidebar = false;
	}

	//this uses the phpQuery object
	public static function onMobileProcessArticleHTMLAfter(OutputPage $out) {
		if (!self::aboutWikihowPage() || !self::showMobileAboutWikihow()) return;

		foreach (pq('.section.steps') as $key => $step) {
			$section_title = pq($step)->find('h3 span')->text();

			if ($section_title == wfMessage('section_title_before_mobile_pressbox')->text()) {
				//add press box after the designated section
				pq($step)->after(PressBoxes::pressSidebox());
			}
			elseif ($section_title == wfMessage('section_title_for_slideshow')->text()) {
				//remove the slideshow because it looks bad on mobile
				pq($step)->remove();
			}

		}
	}

	public static function onIsEligibleForMobile( &$mobileAllowed ) {
		if (self::wikiHowNamespacePage()) {
			//safe to run w/o checks because the IF already validated it all
			$title = RequestContext::getMain()->getOutput()->getTitle();
			if (in_array($title->getDBkey(), WikihowNamespacePages::mobileFriendlyPages())) $mobileAllowed = true;
		}
	}
}
