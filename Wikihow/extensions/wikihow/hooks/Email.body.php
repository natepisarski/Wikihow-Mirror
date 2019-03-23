<?php

class EmailBounceHooks {
	const IN_CLAUSE_SIZE = 20;

	/**
	 * Returns email from strings.
	 * e.g. this will return "john@domain.com" from string below
	 * "john@domain.com", "john doe <john@domain.com>"
	 *
	 * @param unknown $str
	 * @return string
	 */
	public static function getOnlyEmailAddr( $str ) {
		$start = strpos( $str, '<' );
		if ( $start !== false ) {
			$end = strpos( $str, '>' );
			if ( $end !== false ) {
				return trim( substr( $str, $start + 1, $end - $start - 1 ) );
			} else {
				return trim( substr( $str, $start + 1 ) );
			}
		}
		return trim( $str );
	}

	private static function getDBBouncingEmails( $emailAddrs ) {
		if ( empty( $emailAddrs ) || ! is_array( $emailAddrs ) )
			return null;

		$oemailEmailMap = array();

		foreach ( $emailAddrs as $emailAddr ) {
			$emailAddr = strtolower( $emailAddr );
			$oEmailAddr = self::getOnlyEmailAddr( $emailAddr );
			$oemailEmailMap[$oEmailAddr] = $emailAddr;
		}

		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select( 'suppress_emails', array(
			'email'
		), array(
			'email' => array_keys( $oemailEmailMap )
		), __METHOD__ );

		if ( $res === false )
			return null;

		$bEmailAddrs = array();
		foreach ( $res as $row ) {
			$bEmailAddrs[] = $oemailEmailMap[$row->email];
		}

		return $bEmailAddrs;
	}

	/*
	 * private static function splitAddrs( &$to ) { $toRem = array(); $toAdd = array(); foreach (
	 * $to as $index => $addr ) { $pos = strpos( $addr, ',' ); if ( $pos !== false ) { $toRem[] =
	 * $index; $toAdd = array_merge( $toAdd, explode( ',', strval( $addr ) ) ); } } if (
	 * !empty($toRem) ) { foreach ( $toRem as $pos ) { unset( $to[$pos] ); } foreach ( $toAdd as
	 * $emailAddr ) { $emailAddrNew = new MailAddress( trim( $emailAddr ) ); $to[] = $emailAddrNew;
	 * } } }
	 */
	public static function onFilterOutBouncingEmails( &$to ) {
		if ( empty( $to ) || ! is_array( $to ) )
			return false;

			// self::splitAddrs( $to ); //do not use this as it might add more email
			// addresses in to $to. when that happens
			// emails do not go out throwing
			// undisclosed-recipients:;... List:; syntax illegal for recipient addresses

		$toSubArrs = array_chunk( $to, self::IN_CLAUSE_SIZE );

		$bEmailAddrs = array();

		// get bouncing email addresses from db
		foreach ( $toSubArrs as $toSubArr ) {
			$tbEmailAddrs = self::getDBBouncingEmails( $toSubArr );

			if ( $tbEmailAddrs != null && ! empty( $tbEmailAddrs ) ) {
				$bEmailAddrs = array_merge( $bEmailAddrs, $tbEmailAddrs );
			}
		}

		// remove bouncing emails from to list
		foreach ( $bEmailAddrs as $bemailAddr ) {
			$pos = self::searchEmails( $bemailAddr, $to );
			if ( $pos !== false )
				unset( $to[$pos] );
		}

		return true;
	}

	public static function searchEmails( $bemailAddr, $recipient_list ) {
		$index = 0;

		foreach ( $recipient_list as $recipient ) {
			// not sure why this was working before, so we're going to test for string just in case

			if ( is_string( $recipient ) ) {
				if ( strtolower( $recipient ) === $bemailAddr ) {
					return $index;
				}
			} else {
				if ( strtolower( $recipient->address ) === $bemailAddr ) {
					return $index;
				}
			}

			$index ++;
		}

		return false;
	}

}

class EmailNotificationHooks {

	public static function appendUnsubscribeLinkToBody( &$body, &$user ) {
		$link = UnsubscribeLink::newFromId( $user->getId() );
		$body .= "\n\n" .
			 wfMessage( "enotif_body_footer", $link->getLink() )->inContentLanguage()->plain();

		return true;
	}

}

