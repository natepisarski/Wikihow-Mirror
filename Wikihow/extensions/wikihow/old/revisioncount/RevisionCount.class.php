<?php

class RevisionCount {

	public static function getArticleRevisionCountCacheKey($articleID) {
		return wfMemcKey('revisioncount', $articleID);
	}

	public static function getArticleRevisionCount($aid) {
		global $wgMemc;

		$cachekey = self::getArticleRevisionCountCacheKey($aid);
		$revisionCount = (int)$wgMemc->get($cachekey);
		if ($revisionCount) return $revisionCount;

		$dbr = wfGetDB(DB_REPLICA);
		$revisionCount = (int)$dbr->selectField("revision",array('count(*)'),array('rev_page'=>$aid),__METHOD__);
		$wgMemc->set($cachekey, $revisionCount);
		return $revisionCount;
	}

	public function onRevisionInsertComplete(&$revision, $data, $flags) {
		global $wgMemc;

		$cachekey = self::getArticleRevisionCountCacheKey($aid);
		$wgMemc->incr($cachekey,1);
	}

	public static function onPageContentSaveComplete(&$wikiPage, &$user, $content, $summary,
		$minoredit, $watchthis, $sectionanchor, &$flags, $revision)
	{
		global $wgMemc;

		$cachekey = self::getArticleRevisionCountCacheKey($wikiPage->getId());
		$wgMemc->incr($cachekey,1);
		return true;
	}
}
