<?php

/**
CREATE TABLE `async_http` (
	`ah_key` varbinary(32) NOT NULL,
	`ah_created` varbinary(14) NOT NULL DEFAULT '',
	`ah_updated` varbinary(14) NOT NULL DEFAULT '',
	`ah_ttl` int(10) NOT NULL DEFAULT 0,
	`ah_status` int(3) NOT NULL DEFAULT 200,
	`ah_body` blob NOT NULL,
	PRIMARY KEY (`ah_key`)
);

// To upgrade if ah_created doesn't exist
ALTER TABLE `async_http` ADD COLUMN `ah_created` varbinary(14) NOT NULL DEFAULT '' AFTER `ah_key`;
ALTER TABLE `async_http` ADD COLUMN `ah_ttl` int(10) NOT NULL DEFAULT 0 AFTER `ah_updated`;
UPDATE async_http SET ah_created=ah_updated;
 */

class AsyncHttp {
	/**
	 * Store a response.
	 *
	 * @param {string} $key Unique response key
	 * @param {integer} $status Numeric HTTP status code
	 * @param {string} $body Response body
	 * @param {integer} [$ttl=604800] Time in seconds after updated date until response is stale
	 * @return boolean Response was stored successfully
	 */
	public static function store( $key, $status, $body, $ttl = 604800 ) {
		$dbw = wfGetDB( DB_MASTER );
		return $dbw->replace(
			'async_http',
			[ 'ah_key' ],
			[
				'ah_key' => md5( $key ),
				'ah_created' => wfTimestamp( TS_MW ),
				'ah_updated' => wfTimestamp( TS_MW ),
				'ah_ttl' => (int)$ttl,
				'ah_status' => (int)$status,
				'ah_body' => $body,
			],
			__METHOD__
		);
	}

	/**
	 * Get a stored response.
	 *
	 * @param {string} $key Unique response key
	 * @return {array|null} Response data or null if response doesn't exist
	 */
	public static function read( $key ) {
		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow( 'async_http', '*', [ 'ah_key' => md5( $key ) ], __METHOD__ );
		return !$row ? null : [
			'key' => $row->ah_key,
			'created' => $row->ah_created,
			'updated' => $row->ah_updated,
			'ttl' => (int)$row->ah_ttl,
			'status' => (int)$row->ah_status,
			'body' => $row->ah_body
		];
	}

	/**
	 * Check if a response is expired.
	 *
	 * Responses are considered expired when their updated date plus TTL is in the past.
	 *
	 * @param {string|array} $key Unique response key or reponse array returned from `read` method
	 * @return {boolean} Response is expired, key could not be found or array is invalid
	 * @throws {Exception} If given response array is invalid
	 */
	public static function isExpired( $key ) {
		if ( is_array( $key ) ) {
			if ( !array_key_exists( 'updated', $key ) || !array_key_exists( 'ttl', $key ) ) {
				throw new Exception( 'Invalid response array. Expected "updated" and "ttl" keys.' );
			}
			return wfTimestamp( TS_UNIX, $key->updated ) + $key->ttl < wfTimestamp();
		}
		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow(
			'async_http',
			[ 'ah_updated', 'ah_ttl' ],
			[ 'ah_key' => md5( $key ) ],
			__METHOD__
		);
		if ( $row ) {
			return wfTimestamp( TS_UNIX, $row->ah_updated ) + $row->ah_ttl < wfTimestamp();
		}
		return true;
	}

	/**
	 * Extend the life of a response.
	 *
	 * Resets the TTL to begin from now and optionally also changes the TTL value.
	 *
	 * @param {string} $key Unique response key
	 * @param  [type] [$ttl=null] New TTL value, omit to keep existing value
	 * @return boolean Response was renewed successfully
	 */
	public static function renew( $key, $ttl = null ) {
		$dbw = wfGetDB( DB_MASTER );
		$updates = [ 'ah_updated' => wfTimestamp( TS_MW ) ];
		if ( is_numeric( $ttl ) ) {
			$updates['ah_ttl'] = (int)$ttl;
		}
		return $dbw->update( 'async_http', $updates, [ 'ah_key' => md5( $key ) ], __METHOD__ );
	}
}
