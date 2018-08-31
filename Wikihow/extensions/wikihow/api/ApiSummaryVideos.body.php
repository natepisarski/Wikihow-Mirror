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

	public static function query( $params = [] ) {
		global $wgCategoryNames, $wgLanguageCode, $wgMemc, $wgCanonicalServer;

		// Refresh daily
		$refreshAfter = 24 * 60 * 60;

		if ( isset( $params['page'] ) && $params['page'] !== null ) {
			$page = $params['page'];
			$title = Title::newFromID( $page );
		}
		if ( isset( $params['related'] ) && $params['related'] !== null ) {
			$related = (bool)$params['related'];
		} else {
			$related = false;
		}
		if ( isset( $params['shuffle'] ) && $params['shuffle'] !== null ) {
			$shuffle = (bool)$params['shuffle'];
		} else {
			$shuffle = false;
		}
		if ( isset( $params['featured'] ) && $params['featured'] !== null ) {
			$featured = (bool)$params['shuffle'];
		} else {
			$featured = false;
		}
		if ( isset( $params['limit'] ) && $params['limit'] !== null ) {
			if ( !is_numeric( $params['limit'] ) ) {
				$limit = 1;
			} else {
				$limit = intval( $params['limit'] );
			}
		}

		$key = wfMemcKey(
			"ApiSummaryVideos::query(" .
				implode( [
					"page:{$page}",
					"related:{$related}",
					"shuffle:{$shuffle}",
					"featured:{$featured}",
					"limit{$limit})"
				], ',' ) .
				// Include wgCanonicalServer in key because article prop has full URLs
				"@{$wgCanonicalServer}"
		);
		$data = $wgMemc->get( $key );

		if ( !is_array( $data ) ) {
			$dbr = wfGetDB( DB_SLAVE );

			$tables = [ 'article_meta_info', 'page', 'wikivisual_article_status', 'titus_copy' ];
			$fields = [ 'ami_id', 'ami_video', 'ami_summary_video',
				'page_catinfo', 'vid_processed', 'ti_featured', 'ti_30day_views_unique',
				'ti_summary_video_play'
			];
			$where = [ 'ami_summary_video != \'\'' ];
			$options = [ 'ORDER BY' =>
				'ti_featured DESC, ti_30day_views_unique DESC, ti_summary_video_play DESC'
			];
			$joins = [
				'page' => [ 'INNER JOIN', [ 'ami_id=page_id' ] ],
				'wikivisual_article_status' => [ 'INNER JOIN', [ 'ami_id=article_id' ] ],
				'titus_copy' => [ 'INNER JOIN', [
					'ti_language_code="' . $wgLanguageCode . '"', 'ti_page_id=page_id' ]
				]
			];

			// If title was given, the state of related makes it either the only one we get, or the
			// only one we don't get
			if ( isset( $page ) ) {
				if ( $related ) {
					$where[] = "page_id != {$dbr->addQuotes( $page )}";
				} else {
					$where['page_id'] = $page;
				}
			}

			if ( $featured ) {
				// Restrict to featured articles
				$where['ti_featured'] = 1;
			}

			// Get meta info for every article with a summary video
			$rows = $dbr->select( $tables, $fields, $where, __METHOD__, $options, $joins );

			// Filter by category intersection
			if ( $title && $related ) {
				$categories = Categoryhelper::getTitleTopLevelCategories( $title );
				$filterCategories = [];
				foreach ( $categories as $category ) {
					$filterCategories[] = $category->getText();
				}
			}

			// Shuffle if requested
			if ( $shuffle ) {
				$items = [];
				foreach ( $rows as $row ) {
					$items[] = $row;
				}
				shuffle( $items );
				$rows = $items;
			}

			// Build results
			$videos = [];
			foreach ( $rows as $row ) {
				$rowCategories = static::getCategoryListFromCatInfo( $row->page_catinfo );
				// Detect category intersection
				if ( isset( $filterCategories ) ) {
					$intersection = array_intersect(
						$filterCategories, explode( ',', $rowCategories )
					);
					if ( count( $intersection ) === 0 ) {
						continue;
					}
				}
				// Filter out alt-domain titles
				if ( !empty( AlternateDomain::getAlternateDomainForPage( $row->ami_id ) ) ) {
					continue;
				}
				$title = Title::newFromId( $row->ami_id );
				$videos[] = [
					'id' => $row->ami_id,
					'title' => $title->getText(),
					'article' => $title->getCanonicalURL(),
					//'article_test' => 'https:' . $title->getFullURL(),
					'updated' => wfTimestamp(  TS_ISO_8601, $row->vid_processed ),
					'video' => static::getVideoUrlFromVideo( $row->ami_summary_video ),
					'poster' => static::getPosterUrlFromVideo( $row->ami_summary_video ),
					'poster@1:1' => static::getPosterUrlFromVideo( $row->ami_summary_video, 1 / 1 ),
					'poster@4:3' => static::getPosterUrlFromVideo( $row->ami_summary_video, 4 / 3 ),
					'clip' => static::getVideoUrlFromVideo( $row->ami_video ),
					'categories' => $rowCategories,
					'popularity' => $row->ti_30day_views_unique,
					'featured' => $row->ti_featured,
					'plays' => $row->ti_summary_video_play
				];

				if ( isset( $limit ) && count( $videos ) >= $limit ) {
					break;
				}
			}

			$data = [ 'videos' => $videos ];
			$wgMemc->set( $key, $data, $refreshAfter );
		}

		return $data;
	}

	/**
	 * Execute API
	 */
	public function execute() {
		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );
		// Get the parameters
		$request = $this->getRequest();
		$page = $request->getVal( 'sv_page', null );
		$related = $request->getBool( 'sv_related' );
		$shuffle = $request->getBool( 'sv_shuffle', null );
		$featured = $request->getBool( 'sv_featured', null );
		$limit = $request->getVal( 'sv_limit', null );
		$data = self::query( compact( 'page', 'related', 'shuffle', 'featured', 'limit' ) );
		$result = $this->getResult();
		$result->setIndexedTagName( $data['videos'], 'video' );
		$result->addValue( 'query', $this->getModuleName(), $data );
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
	 * Get API description
	 *
	 * @return string API description
	 */
	public function getAllowedParams() {
		return [
			'sv_page' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'sv_related' => [ ApiBase::PARAM_TYPE => 'boolean' ],
			'sv_shuffle' => [ ApiBase::PARAM_TYPE => 'boolean' ],
			'sv_featured' => [ ApiBase::PARAM_TYPE => 'boolean' ],
			'sv_limit' => [ ApiBase::PARAM_TYPE => 'integer' ]
		];
	}

	public function getParamDescription() {
		return [
			'sv_page' => 'Page ID to get video (or related videos) for',
			'sv_related' => 'Get related videos (except the video for the given page if it exists)',
			'sv_shuffle' => 'Shuffle results',
			'sv_featured' => 'Limit results to videos related to featured articles',
			'sv_limit' => 'Maximum number of videos to list',
		];
	}

	/**
	 * Get a video URL from a video name
	 *
	 * @param string $video Video name from ami_summary_video column of article_meta_info table
	 * @return string Absolute URL of video
	 */
	protected static function getVideoUrlFromVideo( $video ) {
		return $video ? static::$cdn . str_replace( ' ', '+', $video ) : '';
	}

	/**
	 * Get a poster URL from a video name
	 *
	 * @param string $video Video name from ami_summary_video column of article_meta_info table
	 * @param number $aspect Aspect ratio
	 * @return string Absolute URL of poster
	 */
	protected static function getPosterUrlFromVideo( $video, $aspect = null ) {
		global $wgCanonicalServer;

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

		// Get an image from a name
		$image = Title::newFromText( $name, NS_IMAGE );
		if ( $image ) {
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
