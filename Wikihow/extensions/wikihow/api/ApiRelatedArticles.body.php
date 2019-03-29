<?php
/**
 * API for querying related articles
 *
 * @class
 */
class ApiRelatedArticles extends ApiQueryBase {

	/* Static Members */

	/**
	 * CDN base URL
	 */
	protected static $cdn = WH_CDN_VIDEO_ROOT;

	/* Methods */

	public static function query( $params = [] ) {
		global $wgMemc, $wgCanonicalServer;

		// Refresh daily
		$refreshAfter = 24 * 60 * 60;

		if ( isset( $params['page'] ) && $params['page'] !== null ) {
			$page = $params['page'];
			$title = Title::newFromID( $page );
		}

		$key = wfMemcKey(
			"ApiRelatedArticles::query(" .
				implode( [ "page:{$page}" ], ',' ) .
				// Include wgCanonicalServer in key because article prop has full URLs
				"@{$wgCanonicalServer}"
		);
		//$data = $wgMemc->get( $key );

		if ( !$data ) {
			$title = Title::newFromId( $page );
			if ( $title && $title->exists() ) {
				$relatedTitles = Title::newFromIds(
					array_keys( RelatedWikihows::getRelatedArticlesByCat( $title )['articles'] )
				);
				$relatedArticles = [];
				foreach ( $relatedTitles as $relatedTitle ) {
					$thumbImage = ArticleMetaInfo::getRelatedThumb( $relatedTitle, 320, -1 );
					$relatedArticles[] = [
						'id' => $relatedTitle->getArticleId(),
						'title' => $relatedTitle->getText(),
						'image' => $thumbImage ? $thumbImage->getUrl() : '',
						'url' => $relatedTitle->getCanonicalURL()
					];
				}
				$data = [ 'articles' => $relatedArticles ];
				//$wgMemc->set( $key, $data, $refreshAfter );
			}
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
		$page = $request->getVal( 'ra_page', null );
		$data = self::query( compact( 'page' ) );
		$result = $this->getResult();
		$result->setIndexedTagName( $data['articles'], 'article' );
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	/**
	 * Get API description
	 *
	 * @return string API description
	 */
	public function getDescription() {
		return 'Query Related Articles';
	}

	/**
	 * Get API description
	 *
	 * @return string API description
	 */
	public function getAllowedParams() {
		return [
			'ra_page' => [ ApiBase::PARAM_TYPE => 'integer' ]
		];
	}

	public function getParamDescription() {
		return [
			'ra_page' => 'Page ID to get related articles for',
		];
	}
}
