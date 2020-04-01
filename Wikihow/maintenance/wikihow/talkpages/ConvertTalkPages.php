<?php
define( 'MAINT_DIR', dirname( dirname( __DIR__ ) ) );
require_once MAINT_DIR . '/Maintenance.php';
require_once 'simple_html_dom.php';

class TalkPageConverter extends Maintenance {
	const BATCH_SIZE = 500; // converts 500 pages in one batch
	private $scriptUser;
	private $converted;
	private $skippedInvalid;
	private $skippedNonexistent;
	private $badParsedBlocks;
	private $startTime;
	private $stopTime;

	function __construct() {
		parent::__construct();
		$this->addOption( 'test', 'Whether to actually perform the conversion or just test.', false, false, 't' );
		$this->mDescription = 'Converts talk pages to use the new template format.';
		$this->converted = 0;
		$this->skippedInvalid = 0;
		$this->skippedNonexistent = 0;
		$this->badParsedBlocks = 0;
		$this->startTime = time();
	}

	public function execute() {
		global $wgDefaultUserOptions, $wgUser, $wgDisableSearchUpdate;

		$wgDefaultUserOptions['watchlisthidebots'] = 1;
		$wgDisableSearchUpdate = true;
		$this->scriptUser = User::newFromName( 'Talk page conversion script' );
		$wgUser = $this->scriptUser;
		RequestContext::getMain()->setUser( $this->scriptUser );
		if( !in_array( 'bot', $this->scriptUser->getGroups() ) ) {
			$this->scriptUser->addGroup( 'bot' );
		}

		if ( file_exists( 'last-page.dat' ) && is_readable( 'last-page.dat' ) ) {
			$last = unserialize( file_get_contents( 'last-page.dat' ) );
		}

		if ( !$last ) {
			$last = 0;
		}

		echo 'Grabbing ' . self::BATCH_SIZE . ' pages with page_id > ' . $last . "\n";
		$talkPages = $this->getPages( $last );
		$prevLast = $last;

		foreach ( $talkPages as $talk ) {
			$this->doReplacePage( $talk );
			$last = $talk;
		}

		if ( $prevLast === $last ) {
			$last += self::BATCH_SIZE;
		}

		if ( is_writable( 'last-page.dat' ) ) {
			file_put_contents( 'last-page.dat', serialize( $last ) );
		}

		$this->stopTime = time();
		$this->showStats();
	}

	public function getPages( $last ) {
		$dbr = wfGetDB( DB_REPLICA );
		$pages = array();
		$result = $dbr->select( array(
			'page'
		), array(
			'page_id'
		), array(
			'page_len > 0',
			'page_namespace IN (1,3)',
			'page_is_redirect' => 0,
			'page_id > ' . $dbr->addQuotes( $last )
		), __METHOD__, array(
			'ORDER BY' => 'page_id ASC',
			'LIMIT' => self::BATCH_SIZE,
			'USE INDEX' => 'primary'
		) );

		foreach ( $result as $page ) {
			$pages[] = $page->page_id;
		}
		return $pages;
	}

	public function convert( $text ) {

		// If it doesn't contain any comments, don't bother editing it.
		if ( !strpos( $text, "<div class=\"de\">\n<div class=\"de_header\">" ) ) {
			$this->skippedInvalid++;
			return false;
		}

		$parsed = str_get_html( $text, false, true, DEFAULT_TARGET_CHARSET, false );

		// everytime we encounter a situation where we can't parse or get what we need to,
		// increment the badParsedBlocks and skip over it

		if ( !$parsed || !is_object( $parsed ) ) {
			$this->badParsedBlocks++;
			$this->output( "******** Encountered a parsing error.\n");
			return false;
		}

		// loop over every discussion comment on the page

		foreach ( $parsed->find( 'div[class=de]' ) as $block ) {

			// We have to be able to recognize the HTML we're parsing...
			// Handles cases where someone messed up the formatting on a comment.
			// If so, we skip the block and move on to the next comment.

			if ( !is_object( $block ) || !is_object( $block->children(1) ) ) {
				$this->badParsedBlocks++;
				continue;
			}

			$subTags = $block->first_child();

			if ( !is_object( $subTags ) ) {
				$this->badParsedBlocks++;
				continue;
			}

			$dateBlock = $subTags->first_child();

			if ( !is_object( $dateBlock ) ) {
				$this->badParsedBlocks++;
				continue;
			}

			$date = substr( $dateBlock->innertext, 3 );
			$ts = strtotime( $date );
			$datestamp = wfTimestamp( TS_ISO_8601, $ts );

			if ( !is_object( $subTags->children(1) ) ) {
				$this->badParsedBlocks++;
				continue;
			}

			$user = $this->parseUser( $subTags->children(1)->innertext );
			$msg = $block->children(1)->innertext;

			$template = "\n{{comment_header|%s|date=%s}}%s{{comment_footer|%s}}\n";
			$replacement = sprintf( $template, $user, $datestamp, $msg, $user );
			$block->outertext = $replacement;
		}

		return $parsed->outertext;
	}

	function parseUser( $userString ) {
		preg_match( '/\[\[([^[]*)\]\]/i', $userString, $matches ); // [[User:inner|text]]
		$userLink = $matches[1]; // User:inner|text
		$userNames = substr( $userLink, 5 ); // inner|text
		return trim( $userNames );
	}

	public function doReplacePage( $page_id ) {
		$title = Title::newFromID( $page_id );
		$page = WikiPage::factory( $title );
		$rev = Revision::newFromTitle( $title );

		if ( !$page || !$page->exists() ) {
			$this->skippedNonexistent++;
			return false;
		}

		// I'm not OCD or anything
		if ( !is_object( $title ) || !$title instanceof Title || !$title->isTalkPage() ) {
			$this->skippedNonexistent++;
			return false;
		}

		if ( $title->getNamespace() === NS_TALK ) {
			$type = 'discussion';
		} else {
			$type = 'user talk';
		}

		$text = ContentHandler::getContentText( $rev->getContent() );
		$fixed = $this->convert( $text );

		if ( $fixed ) {
			if ( !$this->hasOption( 'test' ) ) {
				RequestContext::getMain()->setTitle( $title );
				$content = ContentHandler::makeContent( $fixed, $title );
				$flags = EDIT_UPDATE | EDIT_FORCE_BOT;
				// content, summary, flags, base rev id, user
				$page->doEditContent( $content, "Converting $type page comments to new format (bot)", $flags, false, $this->scriptUser );

				$this->output( "*** Converted " . $title->getPrefixedText() . " ***\n" );
			} else {
				$this->output( "*** Would convert " . $title->getPrefixedText() . " ***\n" );
			}
			$this->converted++;
		}
	}

	public function showStats() {
		$total = $this->converted + $this->skippedInvalid + $this->skippedNonexistent;

		if ( $total === 0 ) {
			$percent = 0;
		} else {
			$percent = round( 100 * ($this->converted / $total), 2 );
		}

		$this->output( "\n--------------------------------\n");
		$this->output( "Runtime: " . ($this->stopTime - $this->startTime) . " s\n");
		$this->output( "Successful conversions: " . $this->converted . "\n");
		$this->output( "Invalid discussion pages skipped: " . $this->skippedInvalid . "\n");
		$this->output( "Non-existent pages skipped: " . $this->skippedNonexistent . "\n");
		$this->output( "Bad parse blocks skipped: " . $this->badParsedBlocks . "\n");
		$this->output( "\n" );
		$this->output( "Conversion rate: " . $percent . "%\n" );
	}
}

$maintClass = 'TalkPageConverter';
require_once RUN_MAINTENANCE_IF_MAIN;
