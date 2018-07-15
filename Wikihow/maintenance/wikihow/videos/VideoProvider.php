<?php
/**
 * VideoProvider.php
 *
 * Management of video provider services
 * Ex: YouTube, Wonderhowto, 5min..
 * Currently the only supported system is YouTube
 */

interface VideoProvider {

	public function getCode();
	public function videoExists( $videoURL );
	public function getURL( $videoURL );
}

class YouTube implements VideoProvider {

	public function getCode() {
		return 'youtube';
	}

	public function videoExists( $videoURL ) {

		$options = array( 'method' => 'GET' );
		$http = MWHttpRequest::factory( $this->getURL( $videoURL ), $options );
		$http->execute();

		$json = json_decode($http->getContent());

		// Safety: In case the page is blank or no data was retrieved
		if ( !is_object($json) ) {
			return true;
		}

		// If the number of total results is 0, then we know the video doesn't exist
		if ( $json->pageInfo->totalResults === 0 ) {
			return false;
		}

		// Video can't be embedded
		if ( $json->items[0]->status->embeddable === 0 ) {
			return false;
		}

		// Video is marked private in which case we shouldn't have access to it
		if ( !in_array($json->items[0]->status->privacyStatus, array( 'public', 'unlisted' ) ) ) {
			return false;
		}

		return true;
	}

	public function getURL( $videoURL ) {
		return 'https://www.googleapis.com/youtube/v3/videos?part=id,status&id=' . $videoURL . '&key=' . WH_YOUTUBE_API_KEY;
	}

}

class VideoJug implements VideoProvider {

	public function getCode() {
		return 'videojug';
	}

	public function videoExists($videoURL) {
		return false;
	}

	public function getURL($videoURL) {
		return '(videojug)';
	}
}

class Howcast implements VideoProvider {

	public function getCode() {
		return 'howcast';
	}

	public function videoExists($videoURL) {
		return false;
	}

	public function getURL($videoURL) {
		return '(howcast)';
	}
}

// This section basically makes a dictionary
// so that we can easily get a VideoProvider object
// from a parameter code (1st param to Curatevideo)

$supportedProviders = array( 'YouTube', 'VideoJug', 'Howcast' );
$providerCodes = array();

foreach ( $supportedProviders as $supportedProvider ) {
	$provider = new $supportedProvider();
	$providerCodes[$provider->getCode()] = $provider;
}

function wfVideoProviders() {
	global $providerCodes;
	return array_keys( $providerCodes );
}

function wfGetVideoProvider( $code ) {
	global $providerCodes;

	if ( array_key_exists( $code, $providerCodes ) ) {
		return $providerCodes[$code];
	} else {
		return false;
	}
}

