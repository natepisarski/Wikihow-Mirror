<?php

require_once __DIR__ . '/../Maintenance.php';

class LookForVideo extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "get page id for title";
    }

	public static function process( $title ) {
		$articleResult = AppDataFormatter::parseArticle($title, 0);

		/*
		if (stristr(json_encode($articleResult), '<video' ) ) {
			decho("foundit", $title->getArticleID());
		}
		 */
		//decho("here", $articleResult);

	}

	public function execute() {
		global $IP;
		require_once("$IP/extensions/wikihow/api/ApiApp.body.php");
		$dbr = wfGetDb( DB_REPLICA );
		$table = "titus_copy";
		$vars = array( 'ti_page_id' );
		$conds = array(
			'ti_language_code' => 'en',
			'ti_robot_policy' => 'index,follow',
			'ti_num_photos > 0'
		);
		$orderBy = 'ti_30day_views DESC';
		$limit = 10;
		$options = array( 'ORDER BY' => $orderBy, 'LIMIT' => $limit );

		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
		foreach ( $res as $row ) {
			$pageIds[] = $row->ti_page_id;
		}
		foreach ( $pageIds as $pageId ) {
			$title = Title::newFromID( $pageId );
			self::process( $title );
		}
		/*
		// for running with piped input and testing recipe schema functions
		while ( false !== ( $line = fgets( STDIN ) ) ) {
			$title = Misc::getTitleFromText( trim( $line ) );
			self::process( $title );
		}
		 */
	}
}


$maintClass = "LookForVideo";
require_once RUN_MAINTENANCE_IF_MAIN;

