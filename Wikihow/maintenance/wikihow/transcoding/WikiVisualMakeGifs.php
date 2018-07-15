<?php

require_once __DIR__ . '/../../Maintenance.php';

// takes an article name or id and gif name/names and inserts them into the article
// the name of the gif will be based on the name of the video 
class WikiVisualMakeGifs extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "add gifs to wikitext of article";

		// optional argument to force an id
		$this->addOption( 'id', 'page id to act on', false, true, 'p' );
    }

	// update the gif processed field in the DB
	private static function markArticleStatusComplete( $id ) {
		$ts = wfTimestampNow( TS_MW );
		$values = array( 'gif_processed' => $ts );
		$conditions = array( 'article_id' => $id );
		$table = 'wikivisual_article_status';
		$dbw = wfGetDB( DB_MASTER );
		return $dbw->update( $table, $values, $conditions, __METHOD__ );
	}

	// update the gif processed field in the DB
	private static function markArticleStatusError( $id ) {
		$ts = wfTimestampNow( TS_MW );
		$values = array( 'gif_processed_error' => $ts );
		$conditions = array( 'article_id' => $id );
		$table = 'wikivisual_article_status';
		$dbw = wfGetDB( DB_MASTER );
		return $dbw->update( $table, $values, $conditions, __METHOD__ );
	}

	/**
	 * looks in the wikivisual article status table to see if a video was
	 * added to the article before a given date
	 *
	 * If the title does not exist or has no video entry, false is returned
	 *
	 * @param Title|$title the title of the article
	 * @param string $date the cutoff date in format like 20151228132517
	 *
	 * @return bool if the video was added to the title before the date (or has no video)
	 */
	private function videoAddedBefore( $title, $date ) {
		if ( !$title || !$title->exists() ) {
			return false;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$table = "wikivisual_article_status";
		$var = "count(*)";
		$conds = [ 'article_id = ' . $title->getArticleID(),
			'vid_processed < ' . $date,
			'vid_cnt > 0' ];
		$fname = __METHOD__;
		$count = $dbr->selectField( $table, $var, $conds, $fname );

		return $count > 0;
	}

	private function getTitleForGifCreation() {
		$dbr = wfGetDB( DB_SLAVE );
		$table = "wikivisual_article_status";
		$vars = "article_id";
		$conds = [ 'status = 40',
			'gif_processed = ""',
			'gif_processed_error = ""',
			'reviewed = 1',
			'error = ""',
			'vid_cnt > 0' ];
		$fname = __METHOD__;
		$options = array( 'ORDER BY' => "vid_processed DESC" );
		$res = $dbr->selectRow( $table, $vars, $conds, $fname, $options );

		// there may be no results anymore, so handle that case
		if ( $res === FALSE ) {
			return null;
		}

		$id = $res->article_id;
		$title = Title::newFromID( $id );
		// sometimes there will be an id but no title, for example if a title was deleted
		if ( $id > 0 && !$title ) {
			$this->output( "found article id of $id but no title found for this id (the page may have been deleted). will mark as gif error\n" );

			// mark this id as error so we will skip it on the next processing round
			self::markArticleStatusError( $id );

			// exit the script and let it just run again next time
			exit(1);
		}
		return $title;
	}

	public function execute() {
		global $wgContLang, $wgTitle;
		$id = $this->getOption( 'id' );
		if ( $id ) {
			$title = Misc::getTitleFromText( $page );
		} else {
			$title = $this->getTitleForGifCreation();
		}

		// set wgtitle in case some other parser function expects it
		$wgTitle = $title;
		if ( !$title || !$title->exists() ) {
			$this->output( "no article found to process at this time or error in proccessing this round.\n" );
			exit(1);
		}

		$id = $title->getArticleID();
		decho( "will make gifs on $title with id", $id, false );

		$url = "http://www.wikihow.com/index.php?curid=$id";
		$output = "";
		$return_var = "";

		// any extra arguments to pass to the shell script that creates the gifs
		$extraArgs = "";

		// if the video was added before the cutoff date of 01/01/2016, then
		// we want to use the first frame of the animation as the default static image
		$useFirstFrameForStatic = $this->videoAddedBefore( $title, '20160101000000' );
		if ( $useFirstFrameForStatic ) {
			decho("will use first frame for static images", false, false);
			$extraArgs .= "-e ";
		}

		exec( "/opt/wikihow/scripts/gifcreation/createGifsFromArticle.sh -s $url $extraArgs", $output, $return_var );
		foreach ( $output as $line ) {
			echo $line . "\n";
		}

		if ( $return_var == 0 ) {
			self::markArticleStatusComplete( $id );
			$this->output( "WikiVisualMakeGifs: done\n" );
			$exit = 0;
		} else {
			$this->output( "AddGifsToVidTag: error processing article $title\n" );
			self::markArticleStatusError( $id );
			$exit = 1;
		}
		exit( $exit );
	}
}


$maintClass = "WikiVisualMakeGifs";
require_once RUN_MAINTENANCE_IF_MAIN;

