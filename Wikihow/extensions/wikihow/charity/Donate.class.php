<?php

class Donate extends ContextSource {

	static $images = [
			'np1.jpg',
			'np2.jpg',
			'np3.jpg',
			'np4.jpg',
			'np5.jpg'
		];

	public static function addDonateSectionToArticle() {
		$ctx = RequestContext::getMain();
		$out = $ctx->getOutput();
		$user = $ctx->getUser();
		$action = $ctx->getRequest()->getVal( 'action', 'view' );

		if (!Misc::isAltDomain() &&
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
		$loader = new Mustache_Loader_CascadingLoader([new Mustache_Loader_FilesystemLoader(dirname(__FILE__))]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		if (empty($non_profit)) $non_profit = Charity::$non_profit;
		$donate_image = self::donateImage($non_profit, $img_num);

		$vars = [
			'platform' => $isMobile ? 'mobile' : 'desktop',
			'image' => wfGetPad('/extensions/wikihow/charity/images/'.$donate_image),
			'isAmp' => GoogleAmp::isAmpMode($out) ? 'true' : '',
			'showX' => !$out->getUser()->isAnon() && !$isMobile,
			'donate_headline' => wfMessage('donate_headline')->text(),
			'donate_text' => wfMessage('donate_text_'.$non_profit)->parse(),
			'donate_link' => wfMessage('donate_link')->text()
		];
		$html = $m->render('info', $vars);
		return $html;
	}

	private static function donateImage(string $non_profit, int $img_num) {
		$dir = $non_profit;
		$images = self::$images;

		if ($non_profit == 'WaterOrg') return $dir.'/np_1.jpg';

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

		if ($title && $title->inNamespace( NS_MAIN ) && $action == 'view') {
			$out->addModules('ext.wikihow.donate');
		}
	}
}