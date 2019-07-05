<?php

/**
 * API for querying summary videos
 *
 * @class
 */
class ApiSummaryVideos extends ApiQueryBase {

	/* Static Members */

	/**
	 * CDN base URL
	 */
	protected static $cdn = WH_CDN_VIDEO_ROOT;

	/**
	 * Refresh daily
	 */
	protected static $refreshAfter = 24 * 60 * 60;

	/* Methods */

	public static function query( $params = [] ) {
		global $wgLanguageCode, $wgMemc, $wgCanonicalServer;

		if ( isset( $params['page'] ) && $params['page'] !== null ) {
			$page = $params['page'];
			$title = Title::newFromID( $page );
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

		$updated = ArticleMetaInfo::getLatestSummaryVideoUpdate();
		$key = wfMemcKey(
			"ApiSummaryVideos::query(" .
				implode( [
					"version:{$updated}",
					"page:{$page}",
					"shuffle:{$shuffle}",
					"featured:{$featured}",
					"limit{$limit})"
				], ',' ) .
				// Include wgCanonicalServer in key because article prop has full URLs
				"@{$wgCanonicalServer}"
		);
		$data = $wgMemc->get( $key );

		if ( !is_array( $data ) ) {
			$dbr = wfGetDB( DB_REPLICA );
			$where = [];

			// Optionally limit to specified page
			if ( isset( $page ) ) {
				$where['sv_id'] = $page;
			}
			// Optionally restrict to featured articles
			if ( $featured ) {
				$where['sv_featured'] = 1;
			}

			$options = [ 'ORDER BY' => 'sv_featured DESC, sv_popularity DESC, sv_plays DESC' ];

			// Optionally apply limit
			if ( isset( $limit ) ) {
				// If shuffling, make sure we have 3x the limit
				if ( $shuffle ) {
					$options[ 'LIMIT' ] = $limit * 3;
				} else {
					$options[ 'LIMIT' ] = $limit;
				}
			}

			// Get meta info for every article with a summary video
			$rows = $dbr->select( 'summary_videos', '*', $where, __METHOD__, $options );

			// Optionally shuffle
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
				$videos[] = [
					'id' => $row->sv_id,
					'title' => $row->sv_title,
					'article' => "{$wgCanonicalServer}/" . str_replace( ' ', '-', $row->sv_title ),
					'updated' => wfTimestamp( TS_ISO_8601, $row->sv_updated ),
					'video' => static::$cdn . $row->sv_video,
					'poster' => $wgCanonicalServer . $row->sv_poster,
					'clip' => $row->sv_clip ? ( static::$cdn . $row->sv_clip ) : '',
					'categories' => $row->sv_categories,
					'breadcrumbs' => $row->sv_breadcrumbs,
					'popularity' => $row->sv_popularity,
					'featured' => $row->sv_featured,
					'plays' => $row->sv_plays
				];

				if ( isset( $limit ) && count( $videos ) >= $limit ) {
					break;
				}
			}

			$data = [ 'videos' => $videos ];
			$wgMemc->set( $key, $data, static::$refreshAfter );
		}

		return $data;
	}

	/**
	 * Execute API
	 */
	public function execute() {
		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );

		// Purge from varnish daily
		$this->getMain()->setCacheMaxAge( static::$refreshAfter );
		$this->getMain()->setCacheMode( 'public' );

		// Get the parameters
		$request = $this->getRequest();
		$page = $request->getVal( 'sv_page', null );
		$shuffle = $request->getBool( 'sv_shuffle', null );
		$featured = $request->getBool( 'sv_featured', null );
		$limit = $request->getVal( 'sv_limit', null );
		$data = self::query( compact( 'page', 'shuffle', 'featured', 'limit' ) );
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
	 * Get allowed parameters
	 *
	 * @return array Allowed parameter options, keyed by parameter name
	 */
	public function getAllowedParams() {
		return [
			'sv_page' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'sv_shuffle' => [ ApiBase::PARAM_TYPE => 'boolean' ],
			'sv_featured' => [ ApiBase::PARAM_TYPE => 'boolean' ],
			'sv_limit' => [ ApiBase::PARAM_TYPE => 'integer' ]
		];
	}

	/**
	 * Get parameter descriptions
	 *
	 * @return array Parameter descriptions
	 */
	public function getParamDescription() {
		return [
			'sv_page' => 'Page ID to get video for (omit to get a list)',
			'sv_shuffle' => 'Shuffle results',
			'sv_featured' => 'Limit results to videos on featured articles',
			'sv_limit' => 'Maximum number of videos to list',
		];
	}
}
