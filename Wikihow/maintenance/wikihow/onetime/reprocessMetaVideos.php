<?php
/*
 * Update all meta video info.
 */

require_once __DIR__ . '/../../Maintenance.php';

define( 'MEDIAWIKI', 1 );

require_once __DIR__ . '/../../../extensions/wikihow/hooks/ArticleHooks.php';
require_once __DIR__ . '/../../../extensions/wikihow/DatabaseHelper.class.php';

class ReprocessAMIVideos extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->mDescription = 'Update the article_meta_info columns for videos';
		$this->addOption( 'id', 'id of page to be updated', false );
    }

	public function execute() {
		global $wgOut, $wgTitle;

		$times = [];

		// Pull all pages from DB
		$dbw = wfGetDB( DB_MASTER );
		$where = [
			'ti_summary_video' => 1,
			'ti_language_code' => 'en',
			'ti_domain' => 'wikihow.com'
		];
		$id = $this->getOption( 'id' );
		if ( $id != null ) {
			$where['ti_page_id'] = $id;
		}
		$rows = $dbw->select(
			'titus_copy',
			[ 'ti_page_id', 'ti_page_title' ],
			$where,
			__METHOD__
		);
		$pages = array();
		foreach ( $rows as $obj ) {
			$start = microtime( true );
			$title = Title::newFromID( $obj->ti_page_id );
			if ( $title ) {
				$nameAndId = str_pad( "{$obj->ti_page_title} ({$obj->ti_page_id})", 80 );
				echo "Processing started: {$nameAndId}";
				$before = self::hasSummaryVideo( $obj->ti_page_id );
				$page = WikiPage::factory( $title );
				ArticleHooks::updateArticleMetaInfo( $page, null, $page->getContent() );
				$diff = microtime( true ) - $start;
				array_push( $times, $diff );
				if ( count( $times ) > 10 ) {
					array_shift( $times );
				}
				$avg = round( array_sum( $times ) / count( $times ), 2 );
				$diff = round( $diff, 2 );
				$after = self::hasSummaryVideo( $obj->ti_page_id );
				echo ( $before ? '✓' : '✗' ) . ( $after ? '✓' : '✗' ) . ' ';
				echo "{$diff} sec ({$avg} sec avg)\n";
			} else {
				echo "Processing failed, title not found: {$obj->ti_page_title}\n";
			}
		}
	}

	static function hasSummaryVideo( $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$row = $dbw->selectRow(
			'article_meta_info', 'ami_summary_video', [ 'ami_id' => $id ], __METHOD__
		);
		return $row && $row->ami_summary_video !== '';
	}
}

$maintClass = 'ReprocessAMIVideos';
require_once RUN_MAINTENANCE_IF_MAIN;
