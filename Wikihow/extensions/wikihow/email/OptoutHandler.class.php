<?php
class OptoutHandler {

	public static function addOptout( $email ) {
		$dbw = & wfGetDB( DB_MASTER );
		
		if ( self::hasOptedOut( $email ) ) {
			return;
		}
		
		$row = array( 
			'email' => $email,
			'updated_ts' => $dbw->timestamp(),
			'reason' => '',
			'status' => '',
			'action' => 'optout' 
		);
		
		$dbw->insert( 'suppress_emails', $row, __METHOD__ );
	}

	public static function hasOptedOut( $email ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'suppress_emails', array( 
			'se_id' 
		), array( 
			'email' => $email 
		), __METHOD__, array() );
		
		$rows = $dbr->numRows( $res );
		$dbr->freeResult( $res );
		if ( $rows > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	public static function removeOptout( $email ) {
		$dbw = & wfGetDB( DB_MASTER );
		$dbw->delete( 'suppress_emails', array( 
			'email' => $email 
		), __METHOD__ );
	}
}