<?php

class Donate extends ContextSource {

	static $images = [
		'np1.jpg',
		'np2.jpg',
		'np3.jpg',
		'np4.jpg',
		'np5.jpg'
	];

	static $wH_images = [
		// 'dahlia_care.jpg',
		// 'dahlia_planting.jpg',
		// 'dahlia_pot.jpg',
		// 'gardening.jpg'
		'flowers.jpg'
	];

	public static function addDonateSectionToArticle() {
		$ctx = RequestContext::getMain();
		$out = $ctx->getOutput();
		$user = $ctx->getUser();
		$action = $ctx->getRequest()->getVal( 'action', 'view' );

		if (!Misc::isAltDomain() &&
			$out->getTitle() &&
			$out->getTitle()->getText() == 'Make Distilled Water' && //[sc] 1/7/2019 - ONLY SHOW FOR WATER.ORG NON-PROFIT
			$out->getTitle()->inNamespace( NS_MAIN ) &&
			$action == 'view' &&
			$user && $user->getOption('showcharitysection'))
		{
			$non_profit_name = self::getNonProfitName($out->getTitle());

			if (GoogleAmp::isAmpMode($out)) {
				$img_num = rand(0, count(self::$images)-1) + 1;
				$html = self::donateSectionHtmlForArticle($non_profit_name, $img_num);
			}
			else {
				$html = self::lateLoadDonateSectionForArticle($out, $non_profit_name);
			}

			if (pq('.steps:last')->length) {
				pq('.steps:last')->after($html);
			}
		}
	}

	private static function lateLoadDonateSectionForArticle($out, string $non_profit_name = '') {
		$image_count = count(self::$images);
		$force_image = $out->getRequest()->getInt('donate_image');

		if ($non_profit_name == Charity::$non_profit_water_org) $force_image = 1;

		$img_num = $force_image > 0 && $force_image <= $image_count ? $force_image : '';

		$html = '<div id="donate_section" data-image_count="'.$image_count.'" '.
						 'data-image_number="'.$img_num.'" data-non_profit_name="'.$non_profit_name.'" ></div>';
		return $html;
	}

	public static function donateSectionHtmlForArticle(string $non_profit = '', int $img_num = 1) {
		$ctx = RequestContext::getMain();
		$out = $ctx->getOutput();

		$isMobile = Misc::isMobileMode();
		$loader = new Mustache_Loader_CascadingLoader([new Mustache_Loader_FilesystemLoader(__DIR__)]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		if (empty($non_profit)) $non_profit = Charity::$non_profit;
		$donate_image = self::donateImage($non_profit, $img_num);

		if ($non_profit == 'wikihow') {
			$donate_headline = wfMessage('donate_headline_default')->text();
			$donate_link = wfMessage('donate_link_default')->text();
			$default_class = 'don_default';
			$donate_quote_opening = wfMessage('donate_quote_opening_wikihow')->parse();
			$donate_quote = wfMessage('donate_quote_wikihow')->parse();
		}
		else {
			$donate_headline = wfMessage('donate_headline')->text();
			$donate_link = wfMessage('donate_link')->text();
			$default_class = '';
			$donate_quote_opening = '';
			$donate_quote = '';
		}
		$src = wfGetPad('/extensions/wikihow/charity/images/'.$donate_image);
		$vars = [
			'platform' => $isMobile ? 'mobile' : 'desktop',
			'default_class' => $default_class,
			'image' => $src,
			'image_tag' => Misc::getMediaScrollLoadHtml(
				'img', [ 'src' => $src, 'width' => 675, 'class' => 'whcdn' ]
			),
			'isAmp' => GoogleAmp::isAmpMode($out) ? 'true' : '',
			'showX' => !$out->getUser()->isAnon() && !$isMobile,
			'showAltBlock' => $non_profit == 'wikihow' && !$isMobile,
			'donate_headline' => $donate_headline,
			'donate_quote_opening' => $donate_quote_opening,
			'donate_quote' => $donate_quote,
			'donate_text' => wfMessage('donate_text_'.$non_profit)->parse(),
			'donate_link' => $donate_link
		];
		$html = $m->render('info', $vars);
		return $html;
	}

	private static function donateImage(string $non_profit, int $img_num) {
		$dir = $non_profit;
		if ($non_profit == 'WaterOrg') return $dir.'/np_1.jpg';

		$images = $non_profit == 'wikihow' ? self::$wH_images : self::$images;

		$num = $img_num - 1;
		if (!isset($images[$num])) $num = 0;

		return $dir.'/'.$images[$num];
	}

	private static function getNonProfitName($title) {
		$non_profit = ''; //default to current

		if ($title->getText() == 'Make Distilled Water') {
			$non_profit = Charity::$non_profit_water_org;
		}

		return $non_profit;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$action = $out->getRequest()->getVal( 'action', 'view' );
		$title = $out->getTitle();

		//[sc] 1/7/2019 - ONLY SHOW FOR WATER.ORG NON-PROFIT
		if ($title && $title->getText() == 'Make Distilled Water' && $title->inNamespace( NS_MAIN ) && $action == 'view') {
			$out->addModules('ext.wikihow.donate');
		}
	}
}
