<?php

//
// Connect to Fastly's API.
//
// http://www.fastly.com/docs/api
//
class FastlyAction {

	// Use Fastly's API /service/.../purge/key function, described here:
	// http://www.fastly.com/docs/api#service
	public static function resetTag($lang, $tag) {
		global $wgActiveLanguages;

		$services = [
			'en' => ['key' => WH_FASTLY_EN_SERVICE_KEY],
			'intl' => ['key' => WH_FASTLY_INTL_SERVICE_KEY] ];

		if ( isset( $services[$lang] ) ) {
			$apikey = $services[$lang]['key'];
		} elseif ( in_array( $lang, $wgActiveLanguages ) ) {
			$apikey = $services['intl']['key'];
		} else {
			$apikey = '';
		}

		if ( !$apikey ) {
			return false;
		}

		$url = 'https://' . WH_FASTLY_API_SERVER . '/service/' . $apikey . '/purge/' . $tag;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		$headers = [
			'X-Fastly-Key: ' . WH_FASTLY_API_KEY,
			'Accept: application/json' ];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$ret = curl_exec($ch);
		$result = (array)json_decode($ret);

		return $result['status'] ?? false;
	}

	public static function getTag($langCode, $id) {
		$resetTag = "id$langCode$id";
		return $resetTag;
	}

	public static function getTagFromURL($url) {
		$resetTag = '';
		$resetService = '';
		$rows = Misc::getPagesFromURLs( [$url] );
		foreach ($rows as $row) {
			$langCode = $row['lang'];
			$pageid = $row['page_id'];
			$resetTag = self::getTag($langCode, $pageid);
			$resetService = $langCode == 'en' ? 'en' : 'intl';
			break;
		}
		return [$resetTag, $resetService];
	}

	// Use Fastly's API /service/.../purge/key function, described here:
	// http://www.fastly.com/docs/api#service
	public static function purgeURL($url) {
		// NOTE: we validate here that this URL MUST be https. For any
		// future engineer, we CANNOT send our Fastly API key as cleartext.
		// It'd be very dangerous for our service.
		$url = trim($url);
		if (preg_match('@^https://([^/]+)(/.*)$@', $url, $m)) {
			$host = $m[1];
			$path = $m[2];
		} else {
			die("Could not parse URL for purging: $url\n");
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
		$headers = [
			'Fastly-Key: ' . WH_FASTLY_API_KEY,
			'Accept: application/json',
			'Host: ' . $host ];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		if (!is_string($ret)) {
			return false;
		} else {
			$result = (array)json_decode($ret);
			if (!is_array($result)) {
				return false;
			} else {
				if (isset($result['status'])) {
					return $result['status'];
				} elseif (isset($result['msg'])) {
					return $result['msg'];
				} else {
					return false;
				}
			}
		}
	}
}
