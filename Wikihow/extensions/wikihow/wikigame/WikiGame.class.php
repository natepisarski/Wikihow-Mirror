<?php

class WikiGame {
	const TAG_NAME = "wikigame_list";

	public static function addGame() {
		global $wgTitle;

		if(self::isTargetPage($wgTitle)) {
			$options = array(
				'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__)),
			);
			$m = new Mustache_Engine($options);

			$isMobile = Misc::isMobileMode();
			$vars = ['platform' => $isMobile ? 'mobile' : 'desktop'];

			if ( $isMobile ) {
				pq("#article_rating_mobile")->after($m->render('game', $vars));
			} else {
				pq('#bodycontents')->after($m->render('game', $vars));
			}
		}
	}

	public static function onBeforePageDisplay(OutputPage &$out) {
		if (self::isTargetPage($out->getTitle())) {
			$out->addModules(['ext.wikihow.wikigame.less', 'ext.wikihow.wikigame.js']);
		}
	}

	public static function isTargetPage($title) {
		if(!$title) {
			return false;
		}
		//for now only on desktop
		return (!Misc::isMobileMode() && ArticleTagList::hasTag(self::TAG_NAME, $title->getArticleID()));
	}
}