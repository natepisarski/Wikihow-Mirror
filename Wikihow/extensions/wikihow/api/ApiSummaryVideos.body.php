<?php
/**
 * API for querying Hypothesis tests
 *
 * @class
 */
class ApiSummaryVideos extends ApiQueryBase {

	/* Static Members */

	/**
	 * CDN base URL
	 */
	protected static $cdn = WH_CDN_VIDEO_ROOT;

	/* Methods */

	/**
	 * Execute API
	 */
	public function execute() {
		global $wgCategoryNames;
		$dbr = wfGetDB( DB_SLAVE );

		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );

		// Database access - get meta info for every article with a summary video
		$res = $dbr->select(
			[ 'article_meta_info', 'page', 'wikivisual_article_status' ],
			[ 'ami_id', 'ami_title', 'ami_summary_video', 'page_catinfo', 'vid_processed' ],
			[ 'ami_summary_video != \'\'' ],
			__METHOD__,
			[ 'ORDER BY' => 'vid_processed DESC' ],
			[
				'page' => [ 'INNER JOIN', [ 'ami_id=page_id' ] ],
				'wikivisual_article_status' => [ 'INNER JOIN', [ 'ami_id=article_id' ] ]
			]
		);

		// Build results
		$videos = [];
		foreach ( $res as $row ) {
			$videos[] = [
				'id' => $row->ami_id,
				'title' => $row->ami_title,
				'updated' => $row->vid_processed,
				'video' => $this->getVideoUrlFromVideo( $row->ami_summary_video ),
				'poster' => $this->getPosterUrlFromVideo( $row->ami_summary_video ),
				'categories' => $this->getCategoryListFromCatInfo( $row->page_catinfo )
			];
		}
		$result = [ 'videos' => $videos ];
		$this->getResult()->setIndexedTagName( $result['videos'], 'video' );
		$this->getResult()->addValue( 'query', $this->getModuleName(), $result );
	}

	/**
	 * Get API description
	 *
	 * @return string API description
	 */
	public function getDescription() {
		return 'Query Summary videos';
	}

	/**
	 * Get a video URL from a video name
	 *
	 * @param string $video Video name from ami_summary_video column of article_meta_info table
	 * @return string Absolute URL of video
	 */
	protected function getVideoUrlFromVideo( $video ) {
		return static::$cdn . str_replace( ' ', '+', $video );
	}

	/**
	 * Get a poster URL from a video name
	 *
	 * @param string $video Video name from ami_summary_video column of article_meta_info table
	 * @return string Absolute URL of poster
	 */
	protected function getPosterUrlFromVideo( $video ) {
		global $wgCanonicalServer;

		// Translate between video filename and poster image filename by changing the 
		//     From: '/{X}/{YZ}/{NAME} Step 0 Version 1.360p.mp4'
		//     To:   '{NAME} Step 0 preview Version 1.jpg'
		$name = str_replace(
			[ 'Step 0', '.360p.mp4' ], // Replace anchor and video extension
			[ 'Step 0 preview', '.jpg' ], // With anchor + 'preview' and image extension
			substr( $video, 6 ) // Remove leading directory hashing
		);

		// Get an image from a name
		$image = Title::newFromText( $name, NS_IMAGE );
		if ( $image ) {
			// Get a file from an image
			$file = RepoGroup::singleton()->findFile( $image );
			if ( $file ) {
				// Get a thumbnail from a file
				$thumb = $file->transform(
					[ 'width' => 548, WatermarkSupport::NO_WATERMARK => true ], 0
				);
				return $wgCanonicalServer . $thumb->getUrl();
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
	protected function getCategoryListFromCatInfo( $catInfo ) {
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
