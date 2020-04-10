<?php

/**
CREATE TABLE `youtube_info` (
	`yt_id` varbinary(24) NOT NULL,
	`yt_updated` varbinary(14) NOT NULL DEFAULT '',
	`yt_response` blob NOT NULL,
	PRIMARY KEY (`yt_id`)
);
 */
 
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
	 * 	{stirng} [forceRefresh] Whether or not to force hitting the api
	 *
	 * @return bool
	 */
	public function run() {
		global $wgUseSquid, $wgMemc, $wgCanonicalServer;

		wfDebugLog(
			'youtubeinfo',
			">> YouTubeInfoJob::run\n" . var_export( [
				'id' => $this->params['id'],
				'requestKey' => $this->params['requestKey'],
				'cacheKey' => $this->params['cacheKey'],
				'purgeUrls' => $this->params['purgeUrls'],
				'forceRefresh' => $this->params['forceRefresh'],
			], true ) . "\n"
		);

		// Only hit the API if the data isn't stored or is older than a week
		$response = AsyncHttp::read( $this->params['requestKey'] );
		$lastWeek = wfTimestamp( TS_MW, strtotime( '-1 week' ) );
		if ( !$response || $response['updated'] < $lastWeek || $this->params['forceRefresh'] == 'true' ) {
			// Hit the YouTube API
			WikihowStatsd::increment( 'youtube.YouTubeInfoJob' );
			$body = file_get_contents( wfAppendQuery(
				'https://www.googleapis.com/youtube/v3/videos',
				[
					'part' => 'statistics,snippet',
					'id' => $this->params['id'],
					'key' => WH_YOUTUBE_INFO_API_KEY
				]
			) );

			wfDebugLog(
				'youtubeinfo',
				">> YouTubeInfoJob::run - interpreting response\n" . var_export( [
					'id' => $this->params['id'],
					'requestKey' => $this->params['requestKey'],
					'cacheKey' => $this->params['cacheKey'],
					'purgeUrls' => $this->params['purgeUrls'],
					'forceRefresh' => $this->params['forceRefresh'],

					'status' => $http_response_header[0]
				], true ) . "\n"
			);

			// Parse the status code
			if ( !preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#", $http_response_header[0], $match ) ) {
				return false;
			}
			$status = intval( $match[1] );

			// Store the response
			AsyncHttp::store( $this->params['requestKey'], $status, $body );

			// Purge cache so new data gets used
			if ( array_key_exists( 'cacheKey', $this->params ) && $wgMemc ) {
				$wgMemc->delete( $this->params['cacheKey'] );
			}
			if ( array_key_exists( 'purgeUrl', $this->params ) && $wgUseSquid && count( $this->params['purgeUrls'] ) ) {
				$update = new SquidUpdate( $this->params['purgeUrls'] );
				$update->doUpdate();
			}
		}

		return true;
	}
}
