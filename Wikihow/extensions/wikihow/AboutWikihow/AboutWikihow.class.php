<?php

/**
 * class for wikiHow:About-wikiHow
 *
 * Page contents is wikitext, but this class allows us
 * to control things like the sidebar
 *
 * To update the press quotes, go here:
 * /MediaWiki:Press_quotes.json
 */

class AboutWikihow {

	private static $is_about_wikihow_page = null;

	public static function aboutWikihowPage($context = null): bool {
		if (!is_null(self::$is_about_wikihow_page)) return self::$is_about_wikihow_page;

		if (empty($context)) $context = RequestContext::getMain()->getOutput();

		$out = $context->getOutput();
		$out->addModules('ext.wikihow.press_sidebox');

		$action = Action::getActionName($context);
		$diff_num = $out->getRequest()->getVal('diff', '');
		$title = $out->getTitle();
		$wikihow_page = !empty($title) ? $title->inNamespace(NS_PROJECT) : false;

		self::$is_about_wikihow_page =
			$out->getLanguage()->getCode() == 'en' &&
			$action === 'view' &&
			empty($diff_num) &&
			$wikihow_page &&
			$title->getDBKey() == 'About-wikiHow';

		return self::$is_about_wikihow_page;
	}

	public static function pressSidebox(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$press_data = self::pressData();
		if (empty($press_data)) return '';

		$vars = [
			'header' => wfMessage('press_sidebox_header')->text(),
			'press' => $press_data
		];

		$template = Misc::isMobileMode() ? 'press_mobile' : 'press_sidebox';
		return $m->render($template, $vars);
	}

	private static function pressData(): array {
		$json = wfMessage('press_quotes.json')->text();

		$press_data = json_decode($json, true);
		if (empty($press_data)) return [];

		$updated_press_data = [];

		foreach ($press_data as $key => $press_item) {
			$press_item = self::prepPressData($press_item);
			if (!empty($press_item)) $updated_press_data[] = $press_item;
		}

		return $updated_press_data;
	}

	private static function prepPressData(array $press_item): array {
		if (empty($press_item['outlet']) || empty($press_item['quote'])) return [];

		//make an image out of the outlet name
		$image_name = strtolower($press_item['outlet']);
		$image_name = str_replace(' ', '', $image_name);
		$image_name = str_replace('.', '', $image_name);
		$press_item['img'] = '/extensions/wikihow/AboutWikihow/assets/images/'.$image_name.'.png';

		return $press_item;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::aboutWikihowPage()) {
			Misc::setHeaderMobileFriendly();
			$out->setPageTitle(wfMessage('aboutwikihow')->text());
			$out->addModules('ext.wikihow.mobile_about_wikihow');
		}
	}

	public static function onWikihowTemplateShowTopLinksSidebar(bool &$showTopLinksSidebar) {
		if (self::aboutWikihowPage()) $showTopLinksSidebar = false;
	}

	//this uses the phpQuery object
	public static function onMobileProcessArticleHTMLAfter(OutputPage $out) {
		if (!self::aboutWikihowPage()) return;

		foreach (pq('.section.steps') as $key => $step) {
			$section_title = pq($step)->find('h3 span')->text();

			if ($section_title == wfMessage('section_title_before_mobile_pressbox')->text()) {
				//add press box after the designated section
				pq($step)->after(self::pressSidebox());
			}
			elseif ($section_title == wfMessage('section_title_for_slideshow')->text()) {
				//remove the slideshow because it looks bad on mobile
				pq($step)->remove();
			}

		}
	}
}