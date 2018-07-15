<?php
define( 'MAINT_DIR', dirname( dirname( __DIR__ ) ) );

require_once MAINT_DIR . '/Maintenance.php';
require_once MAINT_DIR . '/wikihow/videos/EmbeddedVideo.php';
require_once MAINT_DIR . '/wikihow/videos/VideoProvider.php';
class RemoveVideoJug extends Maintenance {
	private $totalRemoved = 0;

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		if ( $this->hasOption( 'test' ) ) {
			$this->output( "Notice: Running in sandbox mode. Let's build a castle.\n\n" );
		}
		
		$this->output( "Pulling video list ........ " );
		$videos = $this->getVideos();
		$this->output( "Done, " . count( $videos ) . " videos to sort through.\n" );
		
		foreach ( $videos as $video ) {
			if ( $video->getProvider() == 'videojug' ) {
				$this->output( "*** Removing VideoJug video: " . $video->getDBKey() . "\n" );
				$video->remove( 'Deleting VideoJug video', 'Removing VideoJug video' );
				$this->totalRemoved++;
			}
		}
		
		echo "REPORT: " . $this->totalRemoved . " VideoJug videos removed\n";
	}

	public function getVideos() {
		$videos = array();
		
		$dbr = wfGetDB( DB_SLAVE );
		
		$res = $dbr->select( 'page', array( 
			'page_id' 
		), array( 
			'page_namespace' => NS_VIDEO,
			'page_is_redirect' => 0 
		) );
		
		foreach ( $res as $row ) {
			$videos[] = new EmbeddedVideo( $row->page_id );
		}
		
		return $videos;
	}
}

$maintClass = 'RemoveVideoJug';
require_once RUN_MAINTENANCE_IF_MAIN;

