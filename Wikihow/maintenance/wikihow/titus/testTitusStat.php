<?php

require_once( __DIR__ . '/../../Maintenance.php' );


/*
 * A php script to test titus
 * you can pass in a list of ids to act on or a list of stats
 * to run over multiple languages you can use runScriptAsLang
 *
 * need to run stat as apache if it uses the google docs api since it accesses a file owned by apache otherwise you don't need any sudo privelage to run
 *
 * for example to run in 3 languages on 6 pages over 2 stats do:
 * printf 'de\nes\nen\n' | xargs -I % /opt/wikihow/scripts/whrun --lang=% --user=apache -- testTitusStat.php --stat Helpful,PageViews --id 2053,6257,25467,88372,4380618
 *
 * if you need to fully rerun titus then use:
 * whrun run_nightly_titus.sh 
 */
class TitusTestStats extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'stat', 'stat', false, true, 'stats' );
		$this->addOption( 'id', 'pageId to act on', false, true, 'i' );
		$this->addOption( 'allids', 'run your stat on all ids', false, false );
	}

	public function execute() {
		global $wgLanguageCode, $IP;
		require_once( __DIR__ . '/titusController.php' );
		require_once("$IP/extensions/wikihow/titus/Titus.class.php");
		decho( "Running with language code", $wgLanguageCode, false );


		// get the optional stats
		$allIds = $this->getOption( 'allids', false );

		$stats = [];
		$stat = $this->getOption( 'stat', null );
		// Clean up titus stat name, if a (relatively common, for me) mistake
		// in naming was made ...
		if ( $stat && preg_match( '@^TS@', $stat ) ) {
			$stat = preg_replace( '@^TS@', '', $stat );
		}
		if ( $stat ) {
			decho( 'will test on stat', $stat, false );
			$stats = array_keys( array_flip( explode( ',', $stat ) ) );
			$stats = array_fill_keys( $stats, 1 );
		} else if ( $allIds ) {
			decho("can only run allids when using the --stat option as well", false, false);
			exit();
		}

		$pageId = $this->getOption( 'id' );
		$pageIds = null;
		if ( $pageId ) {
			decho( 'will test on pageId', $pageId, false );
			$pageIds = explode( ',', $pageId );

			if ( $allIds ) {
				decho("cannot run on id when using the --allids option", false, false);
				exit();
			}
		}

		// create the titus maintenance class
		// and set up any overriding params on it
		// such as running on a single stat or single page
		// then call updateTitus to do the titus update
		$tc = new TitusMaintenance();
		$tc->titus = new TitusDB( true );
		if ( $stats ) {
			$tc->activeStats = $stats;
		} else {
			$tc->activeStats = TitusConfig::getAllStats();
		}
		$tc->pageIds = $pageIds;

		if ( $allIds == true ) {
			$basicStats = array (
				"PageId" => 1,
				"LanguageCode" => 1,
				"Timestamp" => 1,
				"Title" => 1
			);
			$stats = array_merge( $basicStats, $stats );
			$tc->titus->calcStatsForAllPages( $stats );
			$tc->reportErrors( "", false );
		} else {
			$tc->updateTitus();
			$tc->reportErrors( "", false );
		}
	}
}

$maintClass = "TitusTestStats";
require_once RUN_MAINTENANCE_IF_MAIN;
