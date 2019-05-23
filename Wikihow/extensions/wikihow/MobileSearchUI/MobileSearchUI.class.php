<?php

class MobileSearchUI {

	private static $showNewHeaderSearch = null;

	public static function showNewHeaderSearch(): bool {
		if (isset(self::$showNewHeaderSearch)) return self::$showNewHeaderSearch;

		$context = RequestContext::getMain();
		$out = $context->getOutput();
		$title = $context->getTitle();

		self::$showNewHeaderSearch = false;

		if (
			$title &&
			Misc::isMobileMode() &&
			!GoogleAmp::isAmpMode($out) &&
			$out->getLanguage()->getCode() == 'en'
		) {
			self::$showNewHeaderSearch = mt_rand(0,1); //half our page loads
		}

		return self::$showNewHeaderSearch;
	}

	public static function headerSearch(array $data, bool $isSearchPage): string {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$classes = ['hs_new_style'];
		if ($isSearchPage) $classes[] = 'hs_active';
		if ($data['secondaryButton']) $classes[] = 'hs_notif';

		$vars = [
			'classes' => implode($classes, ' '),
			'query' => $isSearchPage ? RequestContext::getMain()->getSkin()->getRequest()->getVal( 'search', '' ) : '',
			'placeholder_text' => wfMessage( 'header-search-placeholder' )->text(),
			'aria_label' => wfMessage('aria_search')->showIfExists(),
			'amp' => $data['amp']
		];

		return $m->render('mobile_search_header.mustache', $vars);
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin) {
		if (self::showNewHeaderSearch()) {
			$out->addModules('mobile.wikihow.search_header');
		}
	}

	public static function onMobileEmbedStyles(array &$css, Title $title) {
		if (self::showNewHeaderSearch()) {
			$css[] =  __DIR__ . '/resources/mobile_search_header.css';
		}
	}
}