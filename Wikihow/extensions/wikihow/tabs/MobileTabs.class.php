<?php

class MobileTabs {

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
					'text' => strtoupper(wfMessage('article')->text()),
					'anchor' => 'steps_1',
					'selected' => true
				],
				[
					'text' => strtoupper(wfMessage('video')->text()),
					'anchor' => self::getSummarySectionAnchorName()
				]
			]
		];

		$data['prebodytext'] .= $m->render('mobile_tabs.mustache', $vars);
	}

	public static function getSummarySectionAnchorName() {
		return "quick_summary_section";
	}

	public static function isTabArticle($title) {
		return $title ? WHVid::hasSummaryVideo($title) : false;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$title = $skin->getTitle();

		if (Misc::isMobileMode() && self::isTabArticle($title)) {
			$out->addModules('ext.wikihow.mobile_tabs');
		}
	}
}
