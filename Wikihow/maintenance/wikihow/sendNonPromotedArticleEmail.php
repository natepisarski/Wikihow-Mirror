<?php
// this script will update the db with data from the expert verification spreadsheet
// which can also be run from the page /Special:AdminSocialProof

require_once __DIR__ . '/../Maintenance.php';

class SendDemotedArticleEmail extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Find demoted article with big edits";
    }

	private function getDemotedArticles() {
		$dbr = wfGetDB(DB_REPLICA);
		$pageIds = array();
		//$res = $dbr->select( 'newarticlepatrol', array( 'nap_page' ), array( 'nap_patrolled' => 0 ), __METHOD__ );
		$sql = "select nap_page FROM newarticlepatrol JOIN page on page_id = nap_page WHERE page_is_redirect = 0 AND nap_patrolled = 0";
		$res = $dbr->query($sql, __METHOD__);
		foreach ( $res as $row ) {
			$pageIds[] = $row->nap_page;
		}
		return $pageIds;
	}

	// get list of revision id and length for revisions since the cutoff time for a given pageid
	public static function filterRevision( $db, $pageId, $cutoffTime, $cutoffSizeBytes ) {
		$result = null;
		$res = $db->select( 'revision',  array( 'rev_id', 'rev_len', 'rev_parent_id' ), array( "rev_page = $pageId", "rev_timestamp > $cutoffTime"  ), __METHOD__ );

		$revisions = 0;
		foreach ( $res as $row ) {
			$revisions++;
			$revId = $row->rev_id;
			$revLen = $row->rev_len;
			$parentId = $row->rev_parent_id;
			if ( $parentId > 0 ) {
				$parentLen = $db->selectField( 'revision', 'rev_len', array( 'rev_id' => $parentId ), __METHOD__ );
				$szdiff = $revLen - $parentLen;

				if ( $szdiff > $cutoffSizeBytes ) {
					$result = new stdClass();
					$result->revId = $revId;
					$result->szdiff = $szdiff;
					$result->pageId = $pageId;
					break;
				}
			}

		}

		// also return results that were edited in the last week
		if ( !$result && $revisions > 1) {
			$result = new stdClass();
			$result->pageId = $pageId;
		}
		return $result;
	}

	private function filterEdits( $pageIds, $cutoffTime, $cutoffSize ) {
		$dbr = wfGetDB(DB_REPLICA);

		$result = array();

		$cutoffTime = $dbr->timestamp($cutoffTime);
		foreach ( $pageIds as $pageId ) {
			$filtered = self::filterRevision( $dbr, $pageId, $cutoffTime, $cutoffSize );
			if ( $filtered ) {
				$result[] = $filtered;
			}
		}

		return $result;
	}

	private function formatResults( $pages, &$allPages, &$largeEdits ) {
		foreach ( $pages as $page ) {
			$pageId = $page->pageId;
			$title =  Title::newFromID( $pageId );
			if ( !$title ) {
				continue;
			}
			$link = "http://www.wikihow.com" . $title->getLinkURL();
			$allPages[] = $link;
			if ( $page->szdiff ) {
				$largeEdits[] = $link;
			}
		}
	}

	public function execute() {
		$cutoffTime = strtotime('now - 1 week');
		$cutoffSize = 500;

		$pageIds = $this->getDemotedArticles();
		$pageIds = $this->filterEdits( $pageIds, $cutoffTime, $cutoffSize );
		$all = array();
		$large = array();
		$this->formatResults( $pageIds, $all, $large );

		echo "nap not promoted articles with edits of any size in the last week\n";
		foreach( $all as $entry ) {
			echo $entry . "\n";
		}
		echo "\n";
		echo "nap not promoted articles with edits greater than 500 bytes in the last week\n";
		foreach( $large as $entry ) {
			echo $entry . "\n";
		}

		/*
		* $msg = "Here are the demoted articles with big edits from the last week\n";
		* $subject = "Demoted articles with big edits report";
		* $from = new MailAddress("reports@wikihow.com");
		* global $wgIsDevServer;
		* if ( $wgIsDevServer ) {
		* 	$to = new MailAddress( "aaron@wikihow.com" );
		* } else {
		* 	$to = new MailAddress( "aaron@wikihow.com, elizabeth@wikihow.com" );
		* }
		* UserMailer::send( $to, $from, $subject, $msg );
		*/
	}
}

$maintClass = "SendDemotedArticleEmail";
require_once RUN_MAINTENANCE_IF_MAIN;

