<?php
/**
 * move a google drive file to another folder
 *
 */

require_once __DIR__ . '/../../Maintenance.php';

/**
 * Maintenance script that moves google drive files to a new folder
 *
 */
class MoveDriveFiles extends Maintenance {
	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$this->doMove();
		$this->output( "Done!\n" );
	}

	/** Purge URL coming from stdin */
	private function doMove() {
		$oldFolderId = "0ANxdFk4C7ABLUk9PVA";
		$newFolderId = "0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc";

		$stdin = $this->getStdin();
		global $IP;
		require_once("$IP/extensions/wikihow/docviewer/SampleProcess.class.php");
		$service = SampleProcess::buildService();

		$newParent = new Google_Service_Drive_ParentReference();
		$newParent->setId( $newFolderId );

		while ( !feof( $stdin ) ) {
			$fileId = trim( fgets( $stdin ) );
			if ( !$fileId ) {
				continue;
			}
			echo("will move file with id: $fileId\n");
			try {
				$file = $service->files->get( $fileId );
			} catch (Exception $e) {
				decho("no file with id", $fileId, false );
				continue;
			}

			try {
				$parents = $service->parents->listParents( $fileId );
			} catch (Exception $e) {
				decho("could not list parents of file:", $fileId, false );
				continue;
			}


			$hasOldFolder = false;
			$hasNewFolder = false;
			foreach ( $parents->getItems() as $parentRef ) {
				$parentId = $parentRef->getId();

				if ( $parentId == $oldFolderId ) {
					$hasOldFolder = true;
				}

				if ( $parentId == $newFolderId ) {
					$hasNewFolder = true;
				}
			}
			if ( $hasNewFolder && !$hasOldFolder ) {
				echo("already in new folder\n");

			}

			if ( $hasOldFolder ) {
				echo("removing old folder\n");
				$service->parents->delete( $fileId, $oldFolderId );
			}

			if ( !$hasNewFolder ) {
				echo("adding to new folder\n");
				$service->parents->insert( $fileId, $newParent );
			}

		}
	}



}

$maintClass = "MoveDriveFiles";
require_once RUN_MAINTENANCE_IF_MAIN;

