<?php

require_once __DIR__ . '/../../Maintenance.php';

// takes an article name or id and gif name/names and inserts them into the article
// the name of the gif will be based on the name of the video 
class AddGifsToVidTag extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "add gifs to wikitext of article";
		$this->addOption( 'page', 'page or id to act on', false, true, 't' );
		$this->addOption( 'allids', 'run on all ids', false, false );
		$this->addOption( 'remove', 'remove gifs from wikitext', false, false, 'r' );

		// dev option to not check if gif exists before adding it
		$this->addOption( 'nocheck', 'remove gifs from wikitext', false, false );
    }

	/**
	 * Edits wikitext to add gifs to the video tags.
	 * Can also add the first frame gif
	 * Can optionally remove the gif tags
	 * Checks for the gif file existence before adding it  unless $noCheck is true
	 *
	 * @param string $oText the original text of the article
	 * @param bool $addGif
	 * @param bool $addGifFirst to add the first frame gif placeholder image (default true)
	 * @param bool $remove to remove the gifs from the wikitext (default false)
	 * @param bool $noCheck to not check for gif existence before adding them (default false)
	 * @return string - the altered wikitext
	 */
	public static function addToText( $oText, $addGif = true, $addGifFirst = true, $remove = false, $noCheck = false ) {
		// we will match any whvid templates like {{whvid|param|param}}
		// and add (or remove) the gif parameters to it.
		$nText = preg_replace_callback(
			'@(\{\{whvid\|[^\}]*\}\})@im',
			function ( $matches ) use ( $addGif, $addGifFirst, $remove, $noCheck ) {
				$whvid = $matches[1];
				$whvid = self::addGifs( $whvid, $addGif, $addGifFirst, $remove, $noCheck );
				return $whvid;
			},
			$oText);
		return $nText;
	}

	public function execute() {
		global $wgLanguageCode;
		$page = $this->getOption( 'page' );
		$allIds = $this->getOption( 'allids' );

		if ( $allIds && $page ) {
			$this->output( "cannot choose allids and page param\n" );
			exit(1);
		} else if ( $page ) {
			$status = $this->processPage( $page );
			exit( $status );
		} else if ( $allIds ) {
			$this->output( "will process all ids\n" );
			if ( $wgLanguageCode == "en" ) {
				$this->output( "at this time you cannot process all ids for english\n" );
				exit(1);
			}
			$ids = array();
			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select( 'templatelinks', 'tl_from', array('tl_title' => array( 'whvid', 'Whvid' ) ) );
			foreach ( $res as $row ) {
				$ids[] = $row->tl_from;
			}

			foreach ( $ids as $page ) {
				$this->processPage( $page );
			}
		}

	}

	private function processPage( $page ) {
		global $wgTitle, $wgUser;
		$title = Misc::getTitleFromText( $page );
		$wgTitle = $title;
		if ( !$title || !$title->exists() ) {
			$this->output( "no title from $page\n" );
			return 1;
		}


		// get the most recent revision and text
		$gr = GoodRevision::newFromTitle( $title );
		$revId = $gr->latestGood();
		$rev = Revision::newFromId( $revId );
		if ( !$rev ) {
			//$this->output( "no latest good revision on $page\n" );
			return 1;
		}
		$oText = ContentHandler::getContentText( $rev->getContent() );
		if ( strpos( $oText, 'whvid' ) === false ) {
			//$this->output("no whvid template in title\n" );
			return 1;
		}

		$this->output( "will process title $title with id $page\n" );

		// special flags to add gifs or add firstgif image to the template
		$addGif = true;
		$addGifFirst = true;
		$remove = $this->getOption( 'remove' );
		$noCheck = $this->getOption( 'nocheck' );

		$nText = self::addToText( $oText, $addGif, $addGifFirst, $remove, $noCheck );

		$wikiPage = WikiPage::newFromID( $title->getArticleID() );
		$content = ContentHandler::makeContent( $nText, $title );
		$summary = $remove ? "Removing gifs from videos." : "Adding gifs to videos.";
		$user = User::newFromName( "Wikivisual" );

		// set global user to wikivisual user so the autopatrol happens as wikivisual
		$wgUser = $user;

		$flags = EDIT_UPDATE;
		// we will edit the page now
		// if there is no change, we will simply get a failed status which is what we want
		// ie no forced edit with no change
		// the user should have the rights to force the edit through RC patrol and into good revision status
		$status = $wikiPage->doEditContent( $content, $summary, $flags, $revId, $user );
		$exit = 1;
		if ( !$status->isGood() ) {
			if ( $status->hasMessage( 'edit-no-change' ) ) {
				$this->output( "no edit made due to no change in wikitext\n");
			}
		} else if ( $status->isOK() ) {
			$this->output( "finished\n" );
			$exit = 0;
		} else {
			$this->output( "failed\n" );
			$exit = 1;
		}
		return $exit;

	}


	// add or removes gifs to a whvid string tag such as {{whvid|myvideo.mp4|default.jpg}}
	// checks for title/file existence of the gif and giffirst before adding
	// @param whvid - the string that is the actual tag found in wikitext
	// @param $addGif - add the gif, false will skip it
	// @param $addGifFirst - add giffirst to the tag, requires $addGif to be true
	// @param $remove - do no add any gifs to the tag and will remove ones that are already there
	// @param $noCheck - do not check for gif file existence
	public static function addGifs( $whvid, $addGif, $addGifFirst, $remove, $noCheck ) {
		// create placeholders for the video and image names
		// that we will pull out of the existing template
		$videoName = null;
		$baseName = null;
		$previewImg = null;
		$defaultImg = null;

		// we are going to extract the video name and the
		// image params, and ignore any existing gif params
		// because we will re add them based on the video name
		$params = array();
		$whvid = trim( $whvid, "{}" );
		$params = explode( "|", $whvid );

		$count = 0;
		// get the params for use later
		foreach ( $params as $param ) {
			$count++;
			// first param should be whvid, the name of this template
			if ( $count == 1 ) {
				continue;
			}
			// the 2nd param should be the mp4 video name. used in gif file name as well
			if ( $count == 2 && substr($param, -4) == ".mp4" ) {
				$videoName = $param;
				$baseName = substr( $param, 0, strlen( $param ) - 4 );
			}
			// the 3rd param is either a preview image or a default image
			if ( $count == 3 && substr($param, -4) == ".jpg" ) {
				if ( stristr( $param, "preview" ) !== false ) {
					$previewImg = $param;
				} else {
					$defaultImg = $param;
				}
				continue;
			}
			// if there is a 4th param it is likely the default image (if it's a jpg)..
			if ( $count == 4 && substr($param, -4) == ".jpg" ) {
				// check if the preview image was not set above
				// since it may have been set to the defaultImg
				if ( $previewImg == null ) {
					$previewImg = $defaultImg;
				}
				$defaultImg = $param;
			}

			// there may be more params (like existing gif params) but we will ignore them
			// this code is here to be explicit about that and this is where you would
			// add any logic to handle the existing gifs
			if ( $count > 4 ) {
				continue;
			}
		}

		// create an array to hold the params which will make the new whvid tag
		$params = array( "whvid", $videoName );

		if ( $previewImg ) {
			$params[] = $previewImg;
		}
		if ( $defaultImg ) {
			$params[] = $defaultImg;
		}

		// add the gifs to the whvid template if the $remove flag is not true
		if ( !$remove ) {

			// we will check if the gifs exist..and only add the giffirst if
			// the first gif exists so keep track of that
			$gifExists = false;

			if ( $addGif ) {
				// the gif name is just based on the basename of the video
				$gif = $baseName . ".gif";

				// check if it exists before we will add it to the template
				if ( WHVid::gifExists( $gif ) || $noCheck ) {
					$gifExists = true;
					$params[] = "gif=".$gif;
				}
			}
			// only add giffirst if the gif has also been added
			// and the addGifFirst flag is true
			if ( $addGifFirst && $gifExists ) {
				$gif = $baseName . ".first.gif";
				if ( WHVid::gifExists( $gif ) || $noCheck ) {
					$params[] = "giffirst=".$gif;
				}
			}
		}

		// create the new whvid tag and return it
		$whvid = implode( "|", $params );
		$whvid = "{{".$whvid."}}";

		return $whvid;
	}

}


$maintClass = "AddGifsToVidTag";
require_once RUN_MAINTENANCE_IF_MAIN;

