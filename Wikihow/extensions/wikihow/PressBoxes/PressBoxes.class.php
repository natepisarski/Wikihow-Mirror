<?php

/**
 * class for our press boxes
 *
 * To update the press quotes, go here:
 * /MediaWiki:Press_quotes.json
 */

class PressBoxes {

	const PRESS_IMAGE_PATH = '/extensions/wikihow/PressBoxes/assets/images/';

	public static function pressSidebox(): string {
		return '';

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
		$press_item['img'] = self::PRESS_IMAGE_PATH.$image_name.'.png';

		return $press_item;
	}
}
