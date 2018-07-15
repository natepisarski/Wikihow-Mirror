<?php
/**
 * Run doActiveUsersInit updater.
 */

$wgUseMasterForMaintenance = true;
require_once( __DIR__ . '/Maintenance.php' );

/**
 * Maintenance script to run database schema update.
 *
 * @ingroup Maintenance
 */
class ActiveUserUpdater extends Maintenance {

	// Copied and modified from DatabaseUpdater.php
	function doActiveUsersInit() {
		//$activeUsers = $this->db->selectField( 'site_stats', 'ss_active_users', false, __METHOD__ );
		//if ( $activeUsers == -1 ) {
			$activeUsers = $this->db->selectField( 'recentchanges',
				'COUNT( DISTINCT rc_user_text )',
				array( 'rc_user != 0', 'rc_bot' => 0, "rc_log_type != 'newusers'" ), __METHOD__
			);
			$this->db->update( 'site_stats',
				array( 'ss_active_users' => intval( $activeUsers ) ),
				array( 'ss_row_id' => 1 ), __METHOD__, array( 'LIMIT' => 1 )
			);
		//}
		$this->output( "...ss_active_users user count set...\n" );
	}

	function execute() {
		$this->db = wfGetDB( DB_MASTER );
		$this->doActiveUsersInit();
		$this->output( "\nDone.\n" );
	}

}

$maintClass = 'ActiveUserUpdater';
require_once( RUN_MAINTENANCE_IF_MAIN );
