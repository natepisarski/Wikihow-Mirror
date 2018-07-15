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
		$services = [
			'en' => ['key' => WH_FASTLY_EN_SERVICE_KEY],
			'intl' => ['key' => WH_FASTLY_INTL_SERVICE_KEY] ];

		if ( !isset( $services[$lang] ) ) {
			return false;
		}

		$url = 'https://' . WH_FASTLY_API_SERVER . '/service/' . $services[$lang]['key'] . '/purge/' . $tag;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-Fastly-Key: ' . WH_FASTLY_API_KEY,
			'Accept: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		$result = (array)json_decode($ret);
		return $result['status'];
	}

	// Use Fastly's API /service/.../purge/key function, described here:
	// http://www.fastly.com/docs/api#service
	public static function purgeURL($url) {
		$services = [
			'en' => ['key' => WH_FASTLY_EN_SERVICE_KEY],
			'intl' => ['key' => WH_FASTLY_INTL_SERVICE_KEY] ];

		$url = trim($url);
		if (!$url) {
			return false;
		}

		if (preg_match('@^https?://([^/]+)(/.*)$@', $url, $m)) {
			$host = $m[1];
			$path = $m[2];
		} else {
			die("Could not parse URL for purging: $url\n");
		}

		if ($host == 'www.wikihow.com' || $host == 'm.wikihow.com') {
			$lang = 'en';
		} else {
			$lang = 'intl';
		}

		$serviceKey = $services[$lang]['key'];
		if ( !isset( $services[$lang] ) ) {
			return false;
		}

		if (strpos($path, '/') !== 0) {
			$path = '/' . $path;
		}

		$apiurl = 'https://' . WH_FASTLY_API_SERVER . $path;
		$ch = curl_init($apiurl);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-Fastly-Key: ' . $serviceKey,
			'Accept: application/json',
			'Host: ' . $host));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		if (!is_string($ret)) {
			return false;
		} else {
			$result = (array)json_decode($ret);
			if (!is_array($result)) {
				return false;
			} else {
				if (!isset($result['status'])) {
					return false;
				} else {
					return $result['status'];
				}
			}
		}
	}
}
