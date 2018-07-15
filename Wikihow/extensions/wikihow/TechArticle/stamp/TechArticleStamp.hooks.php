<?php

namespace TechArticle;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

use OutputPage;
use RequestContext;
use WikiPage;

use Misc;
use RobotPolicy;
use VerifyData;

/**
 * Add the "Community Tested" stamp to the upper right corner of the article intro box
 */
class TechArticleStampHooks {

	// Desktop
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		if (!$out->canUseWikiPage()) {
			return true;
		}
		$pageId = $out->getWikiPage()->getId();
		if (VerifyData::isExpertVerified($pageId)) {
			return true; // Skip if the article has an "Expert Reviewed" stamp
		}

		$html = static::loadTechArticleStamp();
		if ($html) {
			pq('#intro')->find('.editsection')->remove();
			$intro = pq('#intro')->prepend($html);
		}
		return true;
	}

	// Mobile
	public static function onBeforeRenderPageActionsMobile(array &$data) {
		$html = static::loadTechArticleStamp();
		if ($html) {
			$data['tech_stamp'] = $html;
		}
		return true;
	}

	/**
	 * Return the stamp HTML if this is a tech article, or an empty string otherwise
	 */
	private static function loadTechArticleStamp(): string {
		$out = RequestContext::getMain()->getOutput();
		$page = RequestContext::getMain()->getWikiPage();
		$html = '';
		if (static::isStampVisible($page)) {
			$engine = new Mustache_Engine([
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/resources' )
			]);
			$vars = [
				'msg_intro_label' => wfMessage('tas_intro_label')->text(),
			];
			$html = $engine->render('tech_article_stamp.mustache', $vars);

		}
		return $html;
	}

	public static function isStampVisible(WikiPage $page): bool {
		if (!RobotPolicy::isIndexable($page->getTitle())) {
			return false;
		}
		$techArticle = TechArticle::newFromDB($page->getId());
		return $techArticle->isFullyTested();
	}

}
