<?php
/*
CREATE TABLE `moved_title_images` (
    `mti_page_id` int(10) NOT NULL DEFAULT 0,
    `mti_processed` tinyint(3) NOT NULL DEFAULT 0,
    `mti_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (`mti_page_id`, `mti_timestamp`)
);
 */

require_once __DIR__ . '/../Maintenance.php';

/**
 * Maintenance script that purges images from moved titles
 *
 */
class PurgeThumbnailsFromMovedTitles extends Maintenance {
    public function __construct() {
        parent::__construct();
    }

    public function execute() {
        global $IP;
        $this->purgeImages();
    }

	/**
	 * returns an array with key of pageId and val is an array of image names on that page
	 */
    private function getPagesWithImages() {
        $dbr = wfGetDB( DB_REPLICA );
        $table = array( 'moved_title_images' );
        $var = array( 'mti_page_id' );
        $cond = array( 'mti_processed' => 0 );
        $options = array( 'LIMIT' => 1, 'ORDER BY' => "mti_timestamp ASC" );
        $res = $dbr->select( $table, $var, $cond, __METHOD__, $options );

        $pageIds = array();

        foreach ( $res as $row ) {
            $pageIds[] =  $row->mti_page_id;
        }

        $table = array( 'imagelinks' );
        $var = array( 'il_from', 'il_to' );
        $options = array();
        $pages = array();
        foreach( $pageIds as $pageId ) {
            $cond = array( 'il_from' => $pageId );
            $res = $dbr->select( $table, $var, $cond, __METHOD__, $options );
			$pages[$pageId] = array();
            foreach ( $res as $row ) {
                $pages[$pageId][] = "Image:".$row->il_to;
            }
        }

		foreach ( $pages as $pageId => $images ) {
			$this->output( "will process pageId: $pageId\n" );
			foreach( $images as $imageName ) {
				$this->output( "will process image: $imageName\n" );
			}
		}
        return $pages;
    }

	/**
	 * takes a list of pageIds and sets them to processed in the moved title image table
	 */
	private function setProcessed( $pageIds ) {
		$dbw = wfGetDB( DB_MASTER );
		$table = 'moved_title_images';
		$values = array( 'mti_processed' => 1 );
		$conds = array( 'mti_page_id' => $pageIds );
		$dbw->update( $table, $values, $conds, __METHOD__ );
	}

	/**
	 * calls a script to purge the image list
	 * @arg array string names of the images in format Image:Kiss-Step-5.jpg
	 */
	private function purgeImageList( $images ) {
        global $IP;
        // write them to a file
        $tempFile = tempnam( '/tmp', 'purgemovedimage-' );
        $handle = fopen( $tempFile, "w" );
        foreach( $images as $image ) {
			fwrite( $handle, $image . "\n" );
        }
        fclose( $handle );

        // run the script on them to purge them
        $return_var = 1;
        exec( $IP."/../scripts/url_purge/purge-image-list.sh $tempFile --for-title-based-watermark 2>&1", $output, $return_var );
        $lastLine = "";
        foreach ( $output as $line ) {
            $this->output( $line . "\n" );
            $lastLine = $line;
        }
        if ( $return_var == 0 ) {
            $this->output( "finished with no error\n" );
            $error = "";
        } else {
            $this->output( "finished with error: " . $lastLine . "\n" );
        }

        unlink( $tempFile );

	}

    private function purgeImages() {
        // get the array of pages with images
        $pages = $this->getPagesWithImages();
        if ( !$pages ) {
            $this->output( "no titles to process. done.\n" );
            exit;
        }

		$images = array();
		$pageIds = array();
		foreach ( $pages as $pageId => $imageNames ) {
			if ( count( $imageNames ) == 0 ) {
				$this->setProcessed( [$pageId] );
			} else {
				$images = array_merge( $images, $imageNames );
				$pageIds[] = $pageId;
			}
		}
		if ( !$images ) {
			$this->output( "no images to process. done.\n" );
			exit;
		}
		$this->purgeImageList( $images );

        // for now we want to update the db to set this as processed
        // even if there was an error, to make sure we do not keep retrying something that
        // may be failing
        $this->setProcessed( $pageIds );
    }

}

$maintClass = "PurgeThumbnailsFromMovedTitles";
require_once RUN_MAINTENANCE_IF_MAIN;

