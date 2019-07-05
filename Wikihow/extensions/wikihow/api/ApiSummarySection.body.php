<?php
/**
 * API for querying summary sections
 *
 * @class
 */
class ApiSummarySection extends ApiQueryBase {

	/* Static Members */

	/**
	 * Refresh daily
	 */
	protected static $refreshAfter = 24 * 60 * 60;

	/**
	 * Execute API
	 */
	public function execute() {
		global $wgMemc, $wgCanonicalServer;

		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );

		// Get the parameters
		$request = $this->getRequest();
		$result = $this->getResult();
		$page = $request->getVal( 'ss_page', null );
		if ( $page === null ) {
            $result->addValue( null, $this->getModuleName(), [ 'error' => 'Invalid page ID' ] );
			return;
		}
		$title = Title::newFromId( $page );

		// Caching
		$this->getMain()->setCacheMaxAge( static::$refreshAfter );
		$this->getMain()->setCacheMode( 'public' );
		$key = wfMemcKey( "ApiSummarySection::execute(page:{$page})@{$wgCanonicalServer}" );
		$data = $wgMemc->get( $key );

		if ( !is_array( $data ) ) {
			$data = SummarySection::summaryData( $title->getText() );
			$wgMemc->set( $key, $data, static::$refreshAfter );
		}

		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	/**
	 * Get API description
	 *
	 * @return string API description
	 */
	public function getDescription() {
		return 'Query summary sections';
	}

	/**
	 * Get API description
	 *
	 * @return string API description
	 */
	public function getAllowedParams() {
		return [
			'ss_page' => [ ApiBase::PARAM_TYPE => 'integer' ]
		];
	}

	public function getParamDescription() {
		return [
			'ss_page' => 'Page ID to get summary section for',
		];
	}
}
