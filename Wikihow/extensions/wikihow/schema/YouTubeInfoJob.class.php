<?php

class YouTubeInfoJob extends Job {

	public function __construct( Title $title, array $params, $id = 0 ) {
		parent::__construct( 'YouTubeInfoJob', $title, $params, $id );
		//default for jobs class is to allow duplicates, but we don't want to in this case.
		$this->removeDuplicates = true;
	}

	/**
	 * Execute this job to get info about a YouTube video.
	 *
	 * Params include:
	 * 	{string} id YouTube video ID to get info for
	 * 	{string} requestKey Unique key for request
	 * 	{string} [cacheKey] Memcache key to purge after running
	 * 	{string} [purgeUrls] URLs to purge after running
	 * 	{boolean} [forceRefresh] Whether or not to force hitting the api
	 *
	 * @return bool
	 */
	public function run() {
		global $wgUseSquid, $wgMemc, $wgCanonicalServer;

		wfDebugLog(
			'youtubeinfo',
			">> YouTubeInfoJob::run\n" . var_export( [
				'title' => $this->getTitle()->getText(),
				'id' => $this->params['id'],
				'requestKey' => $this->params['requestKey'],
				'cacheKey' => $this->params['cacheKey'],
				'purgeUrls' => $this->params['purgeUrls'],
				'forceRefresh' => $this->params['forceRefresh'],
				'status' => 'fetching'
			], true ) . "\n"
		);

		// Only hit the API if the data isn't stored or is older than a week
		$response = AsyncHttp::read( $this->params['requestKey'] );
		$forceRefresh = !!$this->params['forceRefresh'];
		if ( !$response || AsyncHttp::isExpired( $this->params['requestKey'] ) || $forceRefresh ) {
			// Hit the YouTube API
			WikihowStatsd::increment( 'youtube.YouTubeInfoJob' );
			// TODO: Replace with CURL
			$body = @file_get_contents( wfAppendQuery(
				'https://www.googleapis.com/youtube/v3/videos',
				[
					'part' => 'statistics,snippet',
					'id' => $this->params['id'],
					'key' => WH_YOUTUBE_INFO_API_KEY
				]
			) );

			wfDebugLog(
				'youtubeinfo',
				">> YouTubeInfoJob::run\n" . var_export( [
					'title' => $this->getTitle()->getText(),
					'id' => $this->params['id'],
					'requestKey' => $this->params['requestKey'],
					'cacheKey' => $this->params['cacheKey'],
					'purgeUrls' => $this->params['purgeUrls'],
					'forceRefresh' => $this->params['forceRefresh'],
					'status' => $http_response_header[0],
				], true ) . "\n"
			);

			// Parse the status code
			$status = 0;
			$bodyIsInvalid = false;
			if ( $body ) {
				if ( !preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#", $http_response_header[0], $match ) ) {
					return false;
				}
				$status = intval( $match[1] );

				$data = @json_decode( $body );
				if ( !$data || !is_array( $data->items ) || !count( $data->items ) ) {
					$bodyIsInvalid = true;
				}
			}

			if ( $status === 200 && !$bodyIsInvalid ) {
				// Store the successful response
				AsyncHttp::store( $this->params['requestKey'], $status, $body );
				// Purge cache so new data gets used
				if ( array_key_exists( 'cacheKey', $this->params ) && $wgMemc ) {
					$wgMemc->delete( $this->params['cacheKey'] );
				}
				if (
					$wgUseSquid &&
					array_key_exists( 'purgeUrls', $this->params ) &&
					count( $this->params['purgeUrls'] )
				) {
					$update = new SquidUpdate( $this->params['purgeUrls'] );
					$update->doUpdate();
				}
			} else {
				// Renew the response so we can try again later
				if ( $status === 403 || $bodyIsInvalid ) {
					// Store an initial response, even if it is invalid, so we have something
					// to renew tomorrow
					if ( !$response ) {
						AsyncHttp::store( $this->params['requestKey'], $status, $body );
					}
					$ttl = 60 * 60 * 24;
				} else {
					$ttl = 60 * 60 * 24 * 7;
				}
				AsyncHttp::renew( $this->params['requestKey'], $ttl );
			}
		}

		return true;
	}
}
