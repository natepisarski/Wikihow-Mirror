<?php

/*
CREATE TABLE `pagelist` (
  `pl_page` int(8) unsigned NOT NULL,
  `pl_list` varchar(14) DEFAULT ''
);
 */

class RisingStar {

	public static function getRS() {
		global $wgMemc;

		$cachekey = wfMemcKey( 'risingstar-feed4', date('YmdG'), number_format( date('i') / 10, 0, '', '' ) );

		$rsOut = $wgMemc->get($cachekey);
		if (is_array($rsOut)) {
			return $rsOut;
		}

		$t = Title::newFromText('wikiHow:Rising-star-feed');
		if ($t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$text = ContentHandler::getContentText( $r->getContent() );
		} else {
			return false;
		}

		// NOTE: temporary patch to handle archives. The authoritative source for RS needs to be
		// moved to the DB versus the feed article. add archive to array.
		$archives = array('wikiHow:Rising-star-feed/archive1');
		foreach ($archives as $archive) {
			$tarch = Title::newFromText($archive);
			if ($tarch->getArticleId() > 0) {
				$r = Revision::newFromTitle($tarch);
				$text = ContentHandler::getContentText( $r->getContent() ) ."\n". $text;
			}
		}

		$rsOut = array();
		$rs = $text;
		$rs = preg_replace("/==\n/", ',', $rs);
		$rs = preg_replace('/^==/', '', $rs);
		$lines = preg_split("/\r|\n/", $rs, null, PREG_SPLIT_NO_EMPTY);
		$count = 0;
		foreach ($lines as $line) {
			if (preg_match('/^==(.*?),(.*?)$/', $line, $matches)) {

				$dt = $matches[1];
				$title = preg_replace('@^(https?:)?//www\.wikihow\.com/@', '', $matches[2]);
				$title = preg_replace('@^(https?:)?//[^/]*\.com/@', '', $matches[2]);

				$t = Title::newFromText($title);
				if (!$t) continue;

				if ($t->isRedirect()) {
					$wikiPage = WikiPage::factory($t);
					$t = $wikiPage->getRedirectTarget();
				}

				if ($t) {
					$rsOut[$t->getPartialURL()] = $dt;
				}
			}
		}
		// sort by most recent first
		$rsOut = array_reverse($rsOut);

		$wgMemc->set($cachekey, $rsOut);
		return $rsOut;
	}

	public static function isRisingStar($pageid, $dbr=null) {
		if (!$dbr) $dbr = wfGetDB(DB_REPLICA);
		$result = $dbr->selectField('pagelist', 'count(*)', array('pl_page'=>$pageid, 'pl_list'=>'risingstar'), __METHOD__) > 0;
		return $result;
	}

	public static function getRisingStarList($limit, $dbr=null) {
		if (!$dbr) $dbr = wfGetDB(DB_REPLICA);
		$ids = array();
		$res = $dbr->select('pagelist',
			'pl_page',
			array('pl_list'=>'risingstar'),
			__METHOD__,
			array('ORDER BY' => 'pl_page desc', 'LIMIT' => $limit));
		foreach ($res as $row) {
			$ids[] = $row->pl_page;
		}
		return $ids;
	}

	public static function onMarkRisingStar($t) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('pagelist',
			array('pl_page' => $t->getArticleID(), 'pl_list' => 'risingstar'),
			__METHOD__);
		return true;
	}
}
