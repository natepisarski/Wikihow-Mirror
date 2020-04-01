<?php

/**
 * Helper class for caching related to Article Questions displayed by the QAWidget
 */
class QAWidgetCache {

	public static function getArticleQuestionsPagingCacheKey($aid, $isEditor, $limit, $offset) {
		return wfMemcKey('qa_answers', $aid, $isEditor, $limit, $offset);
	}

	/**
	 *  Clear the display cache for ArticleQuestions (Answered Questions) for a given article page
	 *
	 * @param $aid - The article id of the page to clear
	 * @param null $questionCount - Pass in the article question count, if possible, to improve the efficiency of
	 * the method. Often times this method is called from code that already has access to the ArticleQuestions
	 * collection for an article
	 */
	public static function clearArticleQuestionsPagingCache($aid, $questionCount = null) {
		if (is_null($questionCount)) {
			$db = QADB::newInstance();
			$questionCount = $db->getArticleQuestionsCount($aid);
		}

		// Clear mobile cache keys
		$numPages = ($questionCount / QAWidget::LIMIT_MOBILE_ANSWERED_QUESTIONS) + 1;
		self::clearArticleQuestionsPagingCacheKeys($aid, $numPages, QAWidget::LIMIT_MOBILE_ANSWERED_QUESTIONS);

		// Clear desktop cache keys
		$numPages = ($questionCount / QAWidget::LIMIT_DESKTOP_ANSWERED_QUESTIONS) + 1;
		self::clearArticleQuestionsPagingCacheKeys($aid, $numPages, QAWidget::LIMIT_DESKTOP_ANSWERED_QUESTIONS);
	}

	protected static function clearArticleQuestionsPagingCacheKeys($aid, $numPages, $limit) {
		global $wgMemc;

		for ($i = 0; $i < $numPages; $i++) {
			$key = self::getArticleQuestionsPagingCacheKey($aid, false, $limit, $i * $limit);
			$wgMemc->delete($key);
		}
	}

	/**
	 * Allow a manual clearing of the ArticleQuestion cache by adding a query string parameter to the url of the
	 * article.  Ex: http://www.wikihow.com/Kiss?qa_purge_cache=1
	 */
	public static function isArticleQuestionsCachePurgeRequest() {
		$ctx = RequestContext::getMain();
		$r = $ctx->getRequest();
		return $r->getBool(QAWidget::PARAM_ARTICLE_QUESTION_CACHE_PURGE, false);
	}

	/**
	 * Clear the QAWidget paging cache as this will be out of date when any Article Questions are inserted/edited
	 * @param $aid
	 * @param $aqid
	 */
	public static function onInsertArticleQuestion($aid, $aqid, $isNew) {
		if (!is_null($aid) && !empty($aid)) {
			QAWidgetCache::clearArticleQuestionsPagingCache($aid);
		}
		return true;
	}

	/**
	 * Clear the QAWidget paging cache as this will be out of date when any Article Questions are deleted
	 * @param $aid
	 * @param $aqid
	 */
	public static function onDeleteArticleQuestion($aid, $aqid) {
		if (!is_null($aid) && !empty($aid)) {
			QAWidgetCache::clearArticleQuestionsPagingCache($aid);
		}
		return true;
	}

	/**
	 * Perform cache clearing for Article Questions
	 *
	 * @param $wikiPage
	 * @return bool
	 */
	public static function onArticlePurge($wikiPage) {
		if ($wikiPage && class_exists('QAWidget')) {
			$t = $wikiPage->getTitle();
			if (QAWidget::isTargetPage($t)) {
				QAWidgetCache::clearArticleQuestionsPagingCache($t->getArticleId());
			}
		}
		return true;
	}
}
