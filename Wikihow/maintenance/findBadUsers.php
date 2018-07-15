<?
require_once( "commandLine.inc" );


	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->select('user', 
			array( 'user_name', 'user_id'), 
			array(),
			"findInlineImages",
			array("LIMIT" => 1000, "ORDER BY" => "user_id desc")
			);
	while ( $row = $dbr->fetchObject($res) ) {
		$user = User::newFromName($row->user_name);
		echo "{$row->user_id}: {$user->getUserPage()->getFullURL()}\n";
	}	
	$dbr->freeResult($res);
?>
