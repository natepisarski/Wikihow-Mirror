<?php

require_once __DIR__ . '/../Maintenance.php';

class changeMotionToStatic extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "change specific steps in article from video to image";
		$this->addOption( 'page', 'page id', true, true, 'p' );
		$this->addOption( 'reverse', 'static to motion', false, false, 'r' );
		$this->addOption( 'verbose', 'print verbose info', false, false, 'v' );
		$this->addOption( 'steps', 'step numbers, comma separated', true, true, 's' );
    }

	public static function getScriptUser() {
		$user = User::newFromName( "MiscBot" );
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
	}

	public function execute() {
		$pageId = $this->getOption( "page" );
		$steps = $this->getOption( "steps" );
		$verbose = $this->getOption( "verbose" );
		if ( $verbose ) {
			decho("page", $page);
			decho("steps", $steps);
		}
		$steps = explode( ",", $steps );
		$user = $this->getScriptUser();
		$editSummary = "motion to static";
		MotionToStatic::changeVideosToStatic( $pageId, $steps, $user, $editSummary );
		//MotionToStatic::removeAllMedia( $pageId, $user, $editSummary );
		//MotionToStatic::changeAllVideosToStatic( $pageId, $user, $editSummary );
	}
}


$maintClass = "changeMotionToStatic";
require_once RUN_MAINTENANCE_IF_MAIN;

