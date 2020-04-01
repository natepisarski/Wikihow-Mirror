<?php

class UrlUtil {

	/**
	 * Get the base URL (dev or prod). E.g. 'https://th.m.jane.wikidogs.com'
	 */
	public static function getBaseURL(string $lang, bool $mobile=false, string $proto='//'): string {
		global $wgIsDevServer;

		$url = '';
		if ($wgIsDevServer) {
			$server = $_SERVER['SERVER_NAME']; 	// e.g. zh.m.jane.wikidogs.com
			$parts = explode('.', $server); 	// [ 'zh', 'm', 'jane', 'wikidogs', 'com' ]
			$parts = array_splice($parts, -3); 	// [ 'jane', 'wikidogs', 'com' ]
			$url = implode('.', $parts); 		// jane.wikidogs.com
			$url = $mobile ? "m.$url" : $url; 	// 'm.jane.wikidogs.com'
			$url = ($lang != 'en') ? "$lang.$url" : $url; 	// 'th.m.jane.wikidogs.com'
		} else {
			$url = wfCanonicalDomain($lang, $mobile);
		}

		return $proto . $url;
	}

}
