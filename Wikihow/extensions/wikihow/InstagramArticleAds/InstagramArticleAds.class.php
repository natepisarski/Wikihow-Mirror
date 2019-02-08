<?php

/**
 * to display Instagram Ads for a specific IG account / device
 */
class InstagramArticleAds {

	var $ad_type = '';
	var $version = 0;

	private static $iphone_tips_article = null;

	const TAG_IPHONE_TIPS_ARTICLES = 'iphonetips_ig_ad_articles';
	const LINK_IG_IPHONE = 'https://www.instagram.com/wikihowiphonetips/';

	function __construct(string $ad_type, int $version = 0) {
		$this->ad_type = strtolower($ad_type);
		$this->version = $version > 0 ? intval($version) : $this->getRandomizedVersion();
		$this->amp = GoogleAmp::isAmpMode(RequestContext::getMain()->getOutput());
	}

	public function getAd(): string {
		if (empty($this->ad_type) || empty($this->version)) return '';

		$vars = $this->getVarsForAd();
		if (empty($vars)) return '';

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render($vars['template'], $vars);
	}

	private function getRandomizedVersion(): int {
		return mt_rand(1,2);
	}

	private function getVarsForAd(): array {

		if ($this->ad_type == 'iphone') {
			switch ($this->version) {
				case 1: return $this->iPhoneAd1();
				case 2: return $this->iPhoneAd2();
			}
		}

		return []; //found nothing
	}

	private function iPhoneAd1(): array {
		$image = Html::rawElement(
			$this->amp ? 'amp-img' : 'img',
			[
				'src' => wfGetPad('/extensions/wikihow/InstagramArticleAds/resources/assets/iphone_phone.png'),
				'class' => 'iab_image',
				'layout' => 'responsive',
				'width' => 102,
				'height' => 165
			]
		);

		$wH_IG_handle = wfMessage('wH_IG_iphone_handle')->text().'<span></span>';
		$wH_IG = Html::rawElement('span', [], $wH_IG_handle);

		return [
			'template' => 'iphonetips_ig_ad_1',
			'image' => $image,
			'text' => wfMessage('ad_iphone_text_1')->parse(),
			'cta' => wfMessage('ad_iphone_cta', $wH_IG)->text(),
			'link' => self::LINK_IG_IPHONE
		];
	}

	private function iPhoneAd2(): array {
		$wH_logo = Html::rawElement(
			$this->amp ? 'amp-img' : 'img',
			[
				'src' => wfGetPad('/extensions/wikihow/InstagramArticleAds/resources/assets/wikiHow_logo.png'),
				'class' => 'iab_wH_logo',
				'alt' => 'wikiHow',
				'layout' => 'responsive',
				'width' => 80,
				'height' => 13
			]
		);
		$text = wfMessage('ad_iphone_text_2', $wH_logo)->text();
		$wH_IG = Html::rawElement('div', [], wfMessage('wH_IG_iphone_handle')->text());

		return [
			'template' => 'iphonetips_ig_ad_2',
			'text' => $text,
			'cta' => wfMessage('ad_iphone_cta', $wH_IG)->text(),
			'link' => self::LINK_IG_IPHONE
		];
	}

	public static function iPhoneTipsArticle(OutputPage $out): bool {
		if (!is_null(self::$iphone_tips_article)) return self::$iphone_tips_article;

		self::$iphone_tips_article = false;

		$title = $out->getTitle();

		if (self::validMobileArticle($title))
			self::$iphone_tips_article = ArticleTagList::hasTag( self::TAG_IPHONE_TIPS_ARTICLES, $title->getArticleID() );

		return self::$iphone_tips_article;
	}

	private static function validMobileArticle(Title $title): bool {
		$android_app = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest();

		return Misc::isMobileMode() &&
			$title->inNamespace( NS_MAIN ) &&
			!$android_app;
	}

	//for late-load JS insertion (not AMP)
	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::iPhoneTipsArticle($out)) {
			$out->addModules(['mobile.wikihow.iphonetips_ig_ad']);
		}
	}

	//add CSS this way so AMP can get it too
	public static function onMobileEmbedStyles(array &$stylePaths, Title $title) {
		$out = RequestContext::getMain()->getOutput();
		if (self::iPhoneTipsArticle($out)) {
			$stylePaths[] = __DIR__ . '/resources/iphonetips_ig_ads.css';
		}
	}

	//for AMP insertion
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		if (GoogleAmp::isAmpMode($out)) {
			$type = '';

			if (self::iPhoneTipsArticle($out)) {
				$type = 'iphone';
			}

			if (!empty($type)) {
				$insta_ad = new InstagramArticleAds($type);
				$html = $insta_ad->getAd();
				pq('#article_rating_mobile')->before($html);
			}
		}
	}

}