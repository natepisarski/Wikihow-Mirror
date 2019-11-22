<?php
/**
 * This script updates the summary_videos table.
 */

require_once __DIR__ . '/../Maintenance.php';
require_once __DIR__ . '/../../LocalKeys.php';

class VerifyVideoCatalogNightlyMaintenance extends Maintenance {

	/* Methods */

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Verify the video_catalog_* tables contain accurate information';
	}

	public function execute() {
		global $wgLanguageCode;

		$dbr = wfGetDB( DB_REPLICA );

		echo "Selecting from video_catalog_link...\t";
		$rows = $dbr->select(
			[ 'video_catalog_link', 'page', 'titus_copy' ],
			[ 'vcl_article_id' ],
			[ 'ti_domain' => 'wikihow.com' ],
			__METHOD__,
			[],
			[
				'page' => [ 'INNER JOIN',
					[ 'page_id=vcl_article_id' ]
				],
				'titus_copy' => [ 'INNER JOIN',
					[
						'ti_language_code="' . $wgLanguageCode . '"',
						'ti_page_id=vcl_article_id'
					]
				]
			]
		);
		$links = [];
		foreach ( $rows as $row ) {
			$links[] = $row->vcl_article_id;
		}
		$selectedCount = $rows->numRows();
		echo "{$selectedCount} selected\n";

		echo "Selecting from article_meta_info...\t";
		// Get meta info for every article with a summary video
		$rows = $dbr->select(
			[ 'article_meta_info', 'page', 'titus_copy' ],
			[ 'ami_id' ],
			[ 'ami_summary_video != \'\'', 'ti_domain' => 'wikihow.com' ],
			__METHOD__,
			[],
			[
				'page' => [ 'INNER JOIN',
					[ 'page_id=ami_id' ]
				],
				'titus_copy' => [ 'INNER JOIN',
					[
						'ti_language_code="' . $wgLanguageCode . '"',
						'ti_page_id=ami_id'
					]
				]
			]
		);

		$selectedCount = $rows->numRows();
		echo "{$selectedCount} selected\n";

		// Insert rows to be inserted into summary_videos
		echo "Verifying links...\n";
		$metas = [];
		foreach ( $rows as $row ) {
			$metas[] = $row->ami_id;
		}

		$missingLinks = array_diff( $metas, $links );
		$missingMetas = array_diff( $links, $metas );

		$missingLinksCount = count( $missingLinks );
		if ( $missingLinksCount ) {
			echo "  ✗ {$missingLinksCount} article_meta_info rows without matches in video_catalog_link\n";
			$titleList = implode( ', ', self::getTitleNames( $missingLinks ) );
			echo "    {$titleList}\n";
		} else {
			echo "  ✓ All article_meta_info rows have matches in video_catalog_link\n";
		}

		$missingMetasCount = count( $missingMetas );
		if ( $missingMetasCount ) {
			echo "  ✗ {$missingMetasCount} video_catalog_link rows without matches in article_meta_info\n";
			$titleList = implode( ', ', self::getTitleNames( $missingMetas ) );
			echo "    {$titleList}\n";
		} else {
			echo "  ✓ All video_catalog_link rows have matches in article_meta_info\n";
		}

		echo "Done.\n";
	}

	private static function getTitleNames( $ids ) {
		$names = [];
		foreach ( $ids as $id ) {
			$title = Title::newFromId( $id );
			$names[] = $title ? $title->getDbKey() : "(article {$id} not found)";
		}
		return $names;
	}
}

$maintClass = "VerifyVideoCatalogNightlyMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;
