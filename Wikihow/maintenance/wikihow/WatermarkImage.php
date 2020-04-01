<?php

require_once __DIR__ . '/../Maintenance.php';

// takes an article name or id and gif name/names and inserts them into the article
// the name of the gif will be based on the name of the video
class WatermarkImage extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "add watermark to image";
		$this->addOption( 'page', 'page id', true, true, 'p' );
		$this->addOption( 'file', 'file', true, true, 'f' );
		$this->addOption( 'scale', 'scale', false, true, 's' );
		$this->addOption( 'dissolve', 'dissolve', false, true, 'd' );
    }

	public function execute() {
		global $wgLanguageCode;
		$pageId = $this->getOption( 'page' );
		$file = $this->getOption( 'file' );
		$exists = file_exists( $file );
		if ( !$exists ) {
			decho("could not find file", $file);
			exit();
		}

		$w = intval(trim(wfShellExec( "identify -format '%w' $file" )));
		$h = intval(trim(wfShellExec( "identify -format '%h' $file" )));
		$scale = $this->getOption( 'scale', 15 );
		$dissolve = $this->getOption( 'dissolve', 70 );
		WatermarkSupport::addTitleBasedWatermark( $file, $file, $w, $h, 'aid'.$pageId, $scale, $dissolve );
	}
}


$maintClass = "WatermarkImage";
require_once RUN_MAINTENANCE_IF_MAIN;

