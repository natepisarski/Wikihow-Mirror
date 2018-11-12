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

	public static function aboutWikihowPage($context = null): bool {
		if (empty($context)) $context = RequestContext::getMain()->getOutput();

		$out = $context->getOutput();
		$out->addModules('ext.wikihow.press_sidebox');

		$action = Action::getActionName($context);
		$diff_num = $out->getRequest()->getVal('diff', '');
		$title = $out->getTitle();
		$wikihow_page = !empty($title) ? $title->inNamespace(NS_PROJECT) : false;

		return $out->getLanguage()->getCode() == 'en' &&
			!Misc::isMobileMode() &&
			$action === 'view' &&
			empty($diff_num) &&
			$wikihow_page &&
			$title->getDBKey() == 'About-wikiHow';
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

		$html = $m->render('press_sidebox', $vars);
		return $html;
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

	public static function onWikihowTemplateShowTopLinksSidebar(bool &$showTopLinksSidebar) {
		if (self::aboutWikihowPage()) $showTopLinksSidebar = false;
	}
}