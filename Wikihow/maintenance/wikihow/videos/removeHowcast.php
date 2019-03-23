<?php
define( 'MAINT_DIR', dirname( dirname( __DIR__ ) ) );

require_once MAINT_DIR . '/Maintenance.php';
require_once MAINT_DIR . '/wikihow/videos/EmbeddedVideo.php';
require_once MAINT_DIR . '/wikihow/videos/VideoProvider.php';
class RemoveHowcast extends Maintenance {
	private $totalRemoved = 0;
	private $simulate = false;

	public function __construct() {
		parent::__construct();
	}

	public function execute() {

		if ( $this->simulate ) {
			$this->output( "Simulating output. Will not remove videos.\n\n" );
		}
		
		$this->output( "Pulling video list ........ " );
		$videos = $this->getVideos();
		$this->output( "Done, " . count( $videos ) . " videos to sort through.\n" );
		
		foreach ( $videos as $video ) {
			if ( $video->getProvider() == 'howcast') {
				$this->output( "*** Removing Howcast video: " . $video->getDBKey() . "\n" );
				if ( !$this->simulate ) {
					$this->output( "remove\n" );
					$video->remove('Deleting Howcast video', 'Removing Howcast video');
				}
				$this->totalRemoved++;
			}
		}
		
		echo "REPORT: " . $this->totalRemoved . " Howcast videos removed\n";
	}

	public function getVideos() {
		$videos = array();
		
		$dbr = wfGetDB( DB_REPLICA );
		
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

$maintClass = 'RemoveHowcast';
require_once RUN_MAINTENANCE_IF_MAIN;

