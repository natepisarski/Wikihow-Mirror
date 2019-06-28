<?php
/**
 * API for querying schema markup
 *
 * @class
 */
class ApiSchemaMarkup extends ApiQueryBase {

	/* Methods */

	/**
	 * Execute API
	 */
	public function execute() {
		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );
		// Get the parameters
		$request = $this->getRequest();
		$type = $request->getVal( 'sm_type', null );
		switch ( $type ) {
			case 'video/wikihow':
				$id = $request->getVal( 'sm_video_wikihow_id', null );
				if ( $id === null ) {
					$data = [
						'status' => 'error',
						'error' => 'Missing sm_wikihow_video_id parameter'
					];
					break;
				}
				$title = Title::newFromId( $id );
				if ( $title === null ) {
					$data = [
						'status' => 'error',
						'error' => 'Invalid video ID'
					];
					break;
				}
				$data = [
					'status' => 'ok',
					'schema' => SchemaMarkup::getVideo( $title )
				];
			break;
			case 'video/youtube':
				$id = $request->getVal( 'sm_video_youtube_id', null );
				if ( $id === null ) {
					$data = [
						'status' => 'error',
						'error' => 'Missing sm_wikihow_video_id parameter'
					];
				}
				$title = Title::newFromText( 'Special:VideoBrowser' );
				$schema = SchemaMarkup::getYouTubeVideo( $title, $id );
				$data = [
					'status' => ( $schema ? 'ok' : 'pending' ),
					'schema' => $schema
				];
			break;
			default:
				$data = [
					'status' => 'error',
					'error' => 'Unsupported schema type'
				];
			break;
		}
		$result = $this->getResult();
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	/**
	 * Get API description
	 *
	 * @return string API description
	 */
	public function getDescription() {
		return 'Query schema markup';
	}

	/**
	 * Get allowed parameters
	 *
	 * @return array Allowed parameter options, keyed by parameter name
	 */
	public function getAllowedParams() {
		return [
			'sm_type' => [ ApiBase::PARAM_TYPE => 'string' ],
			'sm_video_wikihow_id' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'sm_video_youtube_id' => [ ApiBase::PARAM_TYPE => 'string' ]
		];
	}

	/**
	 * Get parameter descriptions
	 *
	 * @return array Parameter descriptions
	 */
	public function getParamDescription() {
		return [
			'sm_type' => 'Type of schema to get (video/wikihow or video/youtube)',
			'sm_video_wikihow_id' => 'Article ID of wikiHow summary video',
			'sm_video_youtube_id' => 'Article ID of YouTube summary video',
		];
	}
}
