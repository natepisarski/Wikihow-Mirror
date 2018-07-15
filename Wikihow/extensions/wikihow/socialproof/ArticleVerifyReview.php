<?php

if (!defined('MEDIAWIKI')) {
    die();
}
/*
* This class holds data about expert verified articles and revisions where they were edited
* It is used to keep track of edits to verified articles
 */

class ArticleVerifyReview {

	const CLEAR_ACTION = 1;
	const EMAIL_ACTION = 2;
	const REVERT_ACTION = 3;

	// remove a page from the db if it is no longer verified
	public static function removePage( $pageId ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'article_verify_review',
			array( 'avr_page_id'=>$pageId ),
			__METHOD__
		);
	}

	public static function inIgnoredSheet( $worksheetName ) {
		$ignoredSheets = array( 'community', 'chefverified' );
		if ( in_array( $worksheetName, $ignoredSheets ) ) {
			return true;
		}
		return false;
	}

	// add a new page/revision combo to the db
	public static function addItem( $pageId, $revId ) {
		$okToInsert = false;

		// first check to see if this item is on the allowed sheets for verify review
		$data = VerifyData::getByPageId( $pageId );

		// only ok to insert if some verify data exists that
		// is not on the ignored sheets

		foreach ( $data as $verifyData ) {
			if ( !self::inIgnoredSheet( $verifyData->worksheetName ) ) {
				$okToInsert = true;
				break;
			}
		}

		if ( $okToInsert ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert(
				'article_verify_review',
				array( 'avr_page_id'=>$pageId, 'avr_rev_id'=>$revId ),
				__METHOD__,
				array( "IGNORE" )
			);
		}
	}

	public static function fixRedirect( $title, $pageId ) {
		$wp = WikiPage::newFromID( $pageId );
		$content = $wp->getContent();
		$target = $content->getUltimateRedirectTarget();
		if ( !$target ) {
			return $title;
		}

		$dbw = wfGetDB( DB_MASTER );
		$count = $dbw->update(
			'article_verify_review',
			array( 'avr_page_id' => $target->getArticleID() ),
			array( 'avr_page_id' => $pageId ),
			__METHOD__ );

		return $target;
	}

	// get the latest cleared revision that has no uncleared revisions after it
	public static function getLatestClearedRevision( $pageId ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'article_verify_review',
			array( 'avr_rev_id', 'avr_cleared' ),
			array( 'avr_page_id' => $pageId ),
			__METHOD__,
			array( 'ORDER BY' => 'avr_rev_id ASC' )
		);

		$latestRev = null;
		foreach ( $res as $row ) {
			$rev = $row->avr_rev_id;
			$cleared = $row->avr_cleared;
			if ( $cleared == 0 ) {
				break;
			}
			if ( $cleared == 1 ) {
				$latestRev = $rev;
			}
		}
		return $latestRev;
	}

	public static function getUnclearedItemsCount() {
		$dbr = wfGetDB( DB_SLAVE );
		$count = $dbr->selectField(
			'article_verify_review',
			'count(distinct avr_page_id)',
			array( 'avr_cleared = 0'),
			__METHOD__ );
		return $count;
	}

	// limit is a limit on the number of distinct page ids results
	// offset is also to the number of distinct page id results
	public static function getUnclearedItemsDB( $limit = null, $offset = null ) {
		$results = array();
		$dbr = wfGetDB( DB_SLAVE );
		/*
		$options = array( "DISTINCT", "ORDER BY" => "avr_rev_id ASC" );
		if ( $limit ) {
			$options['LIMIT'] = $limit;
		}
		if ( $offset ) {
			$options['OFFSET'] = $offset;
		}

		$res = $dbr->select( 'article_verify_review',
			array( 'avr_page_id' ),
			array( 'avr_cleared = 0'),
			__METHOD__,
			$options
		);
		 */

		$sql = "select avr.avr_page_id, avr.avr_rev_id from article_verify_review avr inner join (select avr_page_id, MAX(avr_rev_id) as max_rev_id from article_verify_review where avr_cleared = 0 group by avr_page_id) groupedavr on avr.avr_page_id = groupedavr.avr_page_id and avr.avr_rev_id = groupedavr.max_rev_id order by groupedavr.max_rev_id ASC";
		if ( $limit ) {
			$sql .= " LIMIT $limit";
		}
		if ( $offset ) {
			$sql .= " OFFSET $offset";
		}
		$res = $dbr->query( $sql, __METHOD__ );
		//decho('last', $dbr->lastQuery());exit();

		foreach ( $res as $row ) {
			$pageId = $row->avr_page_id;
			if ( !$results[$pageId] ) {
				$results[$pageId] = array();
			}
			$results[$pageId][] = $row->avr_rev_id;
		}

		return $results;
	}


	// sets the cleared flag to true for the revid range including the beg and end
	public static function clearInRangeInclusive( $pageId, $revBegin, $revEnd, $actionLabel ) {
		$action = self::CLEAR_ACTION;
		if ( $actionLabel == "email" ) {
			$action = self::EMAIL_ACTION;
		}
		if ( $actionLabel == "revert" ) {
			$action = self::EMAIL_ACTION;
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'article_verify_review',
			array( 'avr_cleared' => $action ),
			array( 'avr_page_id' => $pageId, "avr_rev_id between $revBegin and $revEnd" ),
			__METHOD__
		);
	}

	public static function watchInRangeInclusive( $pageId, $revBegin, $revEnd ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'article_verify_review',
			array( 'avr_cleared' => 2 ),
			array( 'avr_page_id' => $pageId, "avr_rev_id between $revBegin and $revEnd" ),
			__METHOD__
		);
	}

}
