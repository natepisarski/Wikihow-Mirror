<?php
/**
 * This script updates the summary_videos table.
 */

require_once __DIR__ . '/../Maintenance.php';
require_once __DIR__ . '/../../LocalKeys.php';

class UpdateSummaryVideosNightlyMaintenance extends Maintenance {

	/* Methods */

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Update the summary_videos table from article_meta_info and others';
	}

	/* sql for this table
       CREATE TABLE `summary_videos` (
         `sv_id` int(10) unsigned NOT NULL,
         `sv_title` varbinary(255) NOT NULL DEFAULT '',
         `sv_updated` varbinary(14) NOT NULL DEFAULT '',
         `sv_video` varbinary(255) NOT NULL DEFAULT '',
         `sv_poster` varbinary(255) NOT NULL DEFAULT '',
         `sv_clip` varbinary(255) NOT NULL DEFAULT '',
         `sv_categories` blob NOT NULL DEFAULT '',
         `sv_breadcrumbs` blob NOT NULL DEFAULT '',
         `sv_popularity` int(10) unsigned NOT NULL DEFAULT 0,
         `sv_featured` boolean DEFAULT 0,
         `sv_plays` int(10) unsigned NOT NULL DEFAULT 0,
         `sv_cached` varbinary(14) NOT NULL DEFAULT '',
         PRIMARY KEY (`sv_id`),
         KEY (`sv_popularity`),
         KEY (`sv_featured`),
         KEY (`sv_plays`),
         KEY (`sv_cached`)
       );
	 */

	public function execute() {
		global $wgLanguageCode;

		// Skip updating if no changes to videos have been made in the last day
		$updated = ArticleMetaInfo::getLatestSummaryVideoUpdate();
		if ( $updated < wfTimestamp( TS_MW, strtotime( '-1 day' ) ) ) {
			echo "Nothing to update.\n";
			return;
		}

		// Get meta info for every article with a summary video
		echo "Selecting...\n";
		$dbr = wfGetDB( DB_REPLICA );
		$rows = $dbr->select(
			[
				'article_meta_info',
				'page',
				'wikivisual_article_status',
				'titus_copy'
			],
			[
				'ami_id',
				'ami_video',
				'ami_summary_video',
				'page_catinfo',
				'vid_processed',
				'processed',
				'ti_featured',
				'ti_30day_views_unique',
				'plays' => '(ti_summary_video_play + ti_summary_video_play_mobile)'
			],
			[ 'ami_summary_video != \'\'' ],
			__METHOD__,
			[ 'ORDER BY' => 'ti_featured DESC, ti_30day_views_unique DESC, plays DESC' ],
			[
				'page' => [ 'INNER JOIN', [ 'ami_id=page_id' ] ],
				'wikivisual_article_status' => [ 'INNER JOIN', [ 'ami_id=article_id' ] ],
				'titus_copy' => [ 'INNER JOIN', [
					'ti_language_code="' . $wgLanguageCode . '"', 'ti_page_id=page_id' ]
				]
			]
		);

		$dbw = wfGetDB( DB_MASTER );
		$cached = wfTimestamp( TS_MW );

		// Insert rows to be inserted into summary_videos
		echo "Replacing...\n";
		foreach ( $rows as $row ) {
			// Filter out alt-domain titles
			if ( !empty( AlternateDomain::getAlternateDomainForPage( $row->ami_id ) ) ) {
				continue;
			}
			$title = Title::newFromId( $row->ami_id );
			$dbw->replace(
				'summary_videos',
				[ 'sv_id' ],
				[
					'sv_id' => $row->ami_id,
					'sv_title' => $title->getText(),
					// Use processed if vid_processed is missing for some reason
					'sv_updated' => ( $row->vid_processed ? $row->vid_processed : $row->processed ),
					'sv_video' => static::getVideoUrlFromVideo( $row->ami_summary_video ),
					'sv_poster' => static::getPosterUrlFromVideo( $row->ami_summary_video, $title ),
					'sv_clip' => static::getVideoUrlFromVideo( $row->ami_video ),
					'sv_categories' => static::getCategoryListFromCatInfo( $row->page_catinfo ),
					'sv_breadcrumbs' => implode( (array)CategoryHelper::getBreadcrumbCategories( $title ), ',' ),
					'sv_popularity' => $row->ti_30day_views_unique,
					'sv_featured' => $row->ti_featured,
					'sv_plays' => $row->plays,
					'sv_cached' => $cached
				],
				__METHOD__
			);
		}

		// Clear previous data
		echo "Pruning...\n";
		$dbw->delete( 'summary_videos', [ "sv_cached < {$cached}" ], __METHOD__ );

		echo "Done.\n";
	}

	/**
	 * Get a video URL from a video name
	 *
	 * @param string $video Video name from ami_summary_video column of article_meta_info table
	 * @return string Absolute URL of video
	 */
	protected static function getVideoUrlFromVideo( $video ) {
		return $video ? str_replace( ' ', '+', $video ) : '';
	}

	/**
	 * Get a poster URL from a video name
	 *
	 * @param string $video Video name from ami_summary_video column of article_meta_info table
	 * @param number $aspect Aspect ratio
	 * @return string Absolute URL of poster
	 */
	protected static function getPosterUrlFromVideo( $video, $title, $aspect = null ) {
		// Hardcoded for now
		$width = 548;
		$height = 360;

		// Translate between video filename and poster image filename by changing the
		//     From: '/{X}/{YZ}/{NAME} Step 0 Version 1.360p.mp4'
		//     To:   '{NAME} Step 0 preview Version 1.jpg'
		$name = str_replace(
			[ 'Step 0', '.360p.mp4' ], // Replace anchor and video extension
			[ 'Step 0 preview', '.jpg' ], // With anchor + 'preview' and image extension
			substr( $video, 6 ) // Remove leading directory hashing
		);
		$image = Title::newFromText( $name, NS_IMAGE );

		// Fallback to trying to generate a poster image from the video name
		if ( !$image || !$image->exists() ) {
			// Try to generate a poster image from the page title
			$image = Title::newFromText( $title->getText() . ' Step 0 preview.jpg', NS_IMAGE );
		}

		if ( $image && $image->exists() ) {
			// Get a file from an image
			$file = RepoGroup::singleton()->findFile( $image );
			if ( $file ) {
				// Get a thumbnail from a file
				$params = [ 'width' => $width, WatermarkSupport::NO_WATERMARK => true ];
				if ( is_numeric( $aspect ) ) {
					$params['crop'] = 1;
					$params['width'] = $height * $aspect;
					$params['height'] = $height;
				}
				$thumb = $file->transform( $params, 0 );
				return $thumb->getUrl();
			}
		}
		return '';
	}

	/**
	 * Get a categories list from a category info bitmask
	 *
	 * @param integer $catInfo Category bitmask from page_catinfo column of page table
	 * @return string Comma delimited category list
	 */
	protected static function getCategoryListFromCatInfo( $catInfo ) {
		global $wgCategoryNames;

		$categories = [];
		$mask = (int)$catInfo;
		foreach ( $wgCategoryNames as $bit => $category ) {
			if ( $bit & $mask ) {
				if ( $category !== "WikiHow" ) {
					$categories[] = $category;
				}
			}
		}
		return join( $categories, ',' );
	}
}

$maintClass = "UpdateSummaryVideosNightlyMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;
