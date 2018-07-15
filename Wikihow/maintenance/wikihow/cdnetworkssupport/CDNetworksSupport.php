<?php
//
// Remove / refresh an object on the CDN (CDNetworks). Must pass in a username (email
// address of portal user), their password, and an object / URL to clear. Wildcards
// can be used in the object to clear, but should be used with caution because a
// cache clear of a bunch of objects could cause a whole bunch of requests to
// our varnish servers.
//

class CDNetworksSupport {

	public static function doCDnetworksApiCall($params, $locations) {
		$url = 'https://openapi.us.cdnetworks.com/purge/rest/doPurge';

		if (count($locations) == 1 && strpos($locations[0], '*') !== false) {
			$type = 'wildcard';
		} else {
			foreach ($locations as $location) {
				if (strpos($location, '*') !== false) {
					print "error: since multiple locations are specified, none may have a '*' (wildcard)\n";
					exit;
				}
			}
			$type = 'item';
		}
		//$double = strpos($location, '**') !== false;
		//if ($type == 'wildcard' && !$double) {
		// Per justin.rodriguez from CDNetworks <support@cdnetworks.com>
		// in March 20, 2013 email to Reuben
		//	print "Notice: to clear 'recursively' all subdirs, it's necessary to use /path/fo/**\n";
		//}
		$locationParams = array_map(function ($l) { return 'path=' . $l; }, $locations);
		$sendParams = array(
				'pad=pad1.whstatic.com',
				'user=' . $params['user'],
				'pass=' . $params['password'],
				'type=' . $type,
				'output=json',
				);

		$paramStr = join('&', array_merge($locationParams, $sendParams));
		$ch = curl_init($url);
		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $paramStr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$ret = curl_exec($ch);
		curl_close($ch);

		return $ret;
	}
}

