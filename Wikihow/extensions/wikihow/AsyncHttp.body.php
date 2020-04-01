<?php

/**
CREATE TABLE `async_http` (
	`ah_key` varbinary(32) NOT NULL,
	`ah_updated` varbinary(14) NOT NULL DEFAULT '',
	`ah_status` int(3) NOT NULL DEFAULT 200,
	`ah_body` blob NOT NULL,
	PRIMARY KEY (`ah_key`)
);
 */

class AsyncHttp {
	public static function store( $key, $status, $body ) {
		$dbw = wfGetDB( DB_MASTER );
		return $dbw->replace(
			'async_http',
			[ 'ah_key' ],
			[
				'ah_key' => md5( $key ),
				'ah_updated' => wfTimestamp( TS_MW ),
				'ah_status' => (int)$status,
				'ah_body' => $body,
			],
			__METHOD__
		);
	}

	public static function read( $key ) {
		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow( 'async_http', '*', [ 'ah_key' => md5( $key ) ], __METHOD__ );
		return !$row ? null : [
			'key' => $row->ah_key,
			'updated' => $row->ah_updated,
			'status' => (int)$row->ah_status,
			'body' => $row->ah_body
		];
	}
}