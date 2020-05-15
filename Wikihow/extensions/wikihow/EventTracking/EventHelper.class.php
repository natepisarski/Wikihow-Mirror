<?php

class EventHelper
{
	/**
	 * Occasionally we want to write an event directly from the server-side.
	 * This method reads the request information that would normally be logged
	 * into a file by Fastly, and writes the event directly to the DB (if valid).
	 *
	 * @return string   An empty string if successful, or an error code.
	 */
	public static function createEventFromRequest(string $eventName, $extraParams=[]): string {
		global $wgDomainName;

		// Validate the event origin and referrer URLs
		$eventUrl = 'https://' .  $wgDomainName . '/';
		$referUrl = $_SERVER['HTTP_REFERER'];
		$urlParts = self::validateEventUrls($eventUrl, $referUrl);
		if ( is_string($urlParts) ) {
			wfDebugLog('eventtracker', "error ($urlParts) - $eventName, $eventUrl, $referUrl");
			return $urlParts;
		}

		// Validate the event parameters
		$browser = $_SERVER['HTTP_X_BROWSER'];
		if     ($browser == 'dt')  $screen = 'large';
		elseif ($browser == 'tb')  $screen = 'medium';
		elseif ($browser == 'mb')  $screen = 'small';
		else                       $screen = '';
		$eventParams = array_merge($extraParams, [
			'page' => 0, // TODO: add this data
			'screen' => $screen,
			'action' => $eventName,
		]);
		$eventConf = self::validateEventConfig($eventParams);
		if ( is_string($eventConf) ) {
			wfDebugLog('eventtracker', "error ($eventConf) - $eventName, " . json_encode($eventParams));
			return $eventConf;
		}

		// Get the current date
		$tz = new DateTimeZone('America/Los_Angeles');
		$nowStr = ( new DateTime() )->setTimezone($tz)->format('Y-m-d H:i:s');

		// Write event to DB
		$event = self::makeDBRow($nowStr, $urlParts, $eventConf);
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(Misc::getLangDB('en') . '.event_log', $event);

		return '';
	}

	/**
	 * Validate an event ping by looking at the origin and referrer URLs.
	 * @return string|array  An error string, or an array if successful.
	 */
	public static function validateEventUrls(string $origUrl, string $referUrl) {
		// Parse and validate the event URL
		$origUrl = parse_url( urldecode($origUrl) );
		if ( !$origUrl || !isset($origUrl['path']) || !self::isValidDomain($origUrl['host'] ?? null) ) {
			return 'event_url';
		}

		// Parse and validate the referrer URL
		$referUrl = parse_url( urldecode($referUrl) );
		if ( !$referUrl || !isset($referUrl['path']) || !self::isValidDomain($referUrl['host'] ?? null) ) {
			return 'referrer_url';
		}

		if ( $origUrl['host'] != $referUrl['host'] ) {
			return 'domain_mismatch';
		}

		return [ // $urlParts
			'host' => $referUrl['host'],
			'path' => $referUrl['path'],
			'query' => $origUrl['query'],
		];
	}

	/**
	 * Validate an event configuration (name and parameters).
	 * @return string|array  An error string, or an array if successful.
	 */
	public static function validateEventConfig(array $params) {
		// Check if mandatory params exist
		if ( !isset($params['page']) || !isset($params['action']) || !isset($params['screen']) ) {
			return 'params';
		}

		// Validate page param
		$pageId = self::parseInt($params['page']);
		if ( $pageId == -1 ) {
			return 'page';
		}

		// Validate action param
		$eventName = $params['action'];
		$config = EventConfig::EVENTS[$eventName] ?? null;
		if ( !$config ) {
			return 'action';
		}

		// Validate screen param
		$screenSize = $params['screen'];
		if ( !in_array($screenSize, ['small','medium','large']) ) {
			return 'screen';
		}

		// Parse event-specific (optional) params
		$extraParams = $config[1];
		$cleanParams = [];
		foreach ($extraParams as $paramName) {
			$cleanParams[$paramName] = isset($params[$paramName])
				? substr($params[$paramName], 0, 500) // limit length to prevent abuse
				: null;
		}

		return [ // $eventConf
			'pageId' => $pageId,
			'eventName' => $eventName,
			'screenSize' => $screenSize,
			'params' => $cleanParams,
		];
	}

	public static function makeDBRow(string $date, array $urlParts, array $eventConf): array {
		return [
			'el_date'    => $date,
			'el_count'   => 1,
			'el_domain'  => $urlParts['host'],
			'el_path'    => urldecode( $urlParts['path'] ),
			'el_page_id' => $eventConf['pageId'],
			'el_screen'  => $eventConf['screenSize'],
			'el_action'  => $eventConf['eventName'],
			'el_params'  => json_encode( $eventConf['params'] ),
		];
	}

	private static function isValidDomain(?string $domain): bool {
		global $wgIsDevServer;
		return ( $domain && isset( EventConfig::DOMAINS[$domain] ) )
			|| ( $wgIsDevServer && strpos($domain, 'wikidogs.com') !== FALSE );
	}

	private static function parseInt(string $v): int {
		if ( $v !== (string)(int)$v ) {
			return -1; // indicates that the string didn't contain an integer
		}
		return (int)$v;
	}

}
