<?php

class MobileTabs {

	public static $hasYTVideo = null;
	public static $isYTListVideo = null;
	public static $hasSummaryVideo = null;
	public static $isTabArticle = null;

	public static function addTabsToArticle(&$data) {
		$title = RequestContext::getMain()->getTitle();
		if(!self::isTabArticle($title)) return;

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$vars = [
			'tabs' => [
				[
					'text' => mb_strtoupper(wfMessage('article')->text()),
					'anchor' => 'steps_1',
					'selected' => true
				],
				[
					'text' => mb_strtoupper(wfMessage('video')->text()),
					'anchor' => self::getSummarySectionAnchorName()
				]
			]
		];

		$data['prebodytext'] .= $m->render('mobile_tabs.mustache', $vars);
	}

	public static function getSummarySectionAnchorName() {
		return self::$hasYTVideo && self::$isYTListVideo ? 'Video' : 'quick_summary_video_section';
	}

	public static function isTabArticle($title) {
		if(!is_null(self::$isTabArticle)) {
			return self::$isTabArticle;
		}

		if(!$title) {
			self::$isTabArticle = false;
			return self::$isTabArticle;
		}

		self::$hasSummaryVideo = WHVid::hasSummaryVideo($title);
		self::$isYTListVideo = WHVid::isYtSummaryArticle($title);
		if(!self::$hasSummaryVideo && !self::$isYTListVideo) {
			self::$isTabArticle = false;
			return self::$isTabArticle;
		}

		if(self::$isYTListVideo) {
			self::$hasYTVideo = WHVid::hasYTVideo($title);
		}

		if(self::$hasSummaryVideo) {
			self::$isTabArticle = true;
			return self::$isTabArticle;
		}

		if(self::$isYTListVideo && self::$hasYTVideo) {
			self::$isTabArticle = true;
			return self::$isTabArticle;
		}

		self::$isTabArticle = false;
		return self::$isTabArticle;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$title = $skin->getTitle();

		if (Misc::isMobileMode() && self::isTabArticle($title)) {
			$out->addModules('ext.wikihow.mobile_tabs');
		}
	}
}
