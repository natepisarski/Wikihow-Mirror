<?php

class SocialFooter {

	//the icons we show in the order we want to show them
	private static $icons = [
		'instagram',
		'facebook',
		'twitter',
		'youtube'
	];

	public static function getSocialFooter(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'mobile' => Misc::isMobileMode(),
			'icons' => self::iconData()
		];

		return $m->render('social_footer', $vars);
	}

	private static function iconData(): array {
		$icon_data = [];

		foreach (self::$icons as $name) {
			$icon_data[] = [
				'id' => 'sf_'.$name,
				'name' => self::mwMsgDefaultEn($name),
				'link' => self::mwMsgDefaultEn($name.'_url')
			];
		}

		return $icon_data;
	}

	//messages are in Misc.i18n.php so we can use them in more places
	private static function mwMsgDefaultEn(string $msg): string {
		$mw_msg = wfMessage($msg)->exists() ? wfMessage($msg)->text() : wfMessage($msg)->inLanguage('en')->text();
		return $mw_msg ?: '';
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$out->addModules('ext.wikihow.social_footer');
	}
}