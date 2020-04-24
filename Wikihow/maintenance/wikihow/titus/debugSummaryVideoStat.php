<?php

require_once( __DIR__ . '/../../Maintenance.php' );


class TitusTestStats extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'id', 'pageId to act on', false, true, 'i' );
	}

	public function execute() {
		global $wgLanguageCode;


		$pageId = $this->getOption( 'id' );

		if ( !$pageId ) {
			$pageId = 35298;
		}
		decho( 'will test on pageId', $pageId, false );
		$t = Title::newFromId($pageId);
		$cachekey = wfMemcKey('goodrev', $pageId);

		# the cache key was corrupt last time so clearing it
		global $wgMemc;
		$wgMemc->delete($cachekey);
		$goodRev = GoodRevision::newFromTitle($t);
		var_dump($goodRev);
		$revId = $goodRev->latestGood() ? $goodRev->latestGood() : 0;
		var_dump(WH_DATABASE_MASTER);
		var_dump("good rev latest good", $revId);
		var_dump("db8 latest good", 28018814);
		$dbr = wfGetDB(DB_MASTER);



		$r = Revision::loadFromTitle($dbr, $t, $revId);
		$r = Revision::loadFromTitle($dbr, $t, 28018814);
		var_dump("revision", $r->getId());
		$text = Wikitext::getSummarizedSection( ContentHandler::getContentText( $r->getContent() ) );
		var_dump($revId);
		var_dump($text);

		if (!empty($text)) {
			if ( strpos( $text, '{{whvid' ) !== false ) {
				$video = true;
			}

			$summary_data = SummarySection::summaryData($t->getText());
			$summary_position = $summary_data['at_top'] ? 'top' : 'bottom';
		}

		$result = array(
			'ti_summary_video' => $video ? 1 : 0,
			'ti_summarized' => $text ? 1 : 0,
			'ti_summary_position' => $summary_position
		);

		var_dump($result);

	}
}

$maintClass = "TitusTestStats";
require_once RUN_MAINTENANCE_IF_MAIN;
