<?php

/**
 * EmbeddedVideo.php
 *
 * Represents an embedded video on a wikiHow page
 * to be checked for availability
 */

class EmbeddedVideo {
	private $provider;
	private $pageId;
	private $bot;
	private $videoURL;

	public function __construct( $pageId ) {
		$this->pageId = $pageId;
		$this->provider = $this->findProvider();


		global $wgUser;

		$this->bot = User::newFromName( 'Vidbot' );
		$this->bot->load();

		$wgUser = $this->bot;

	}

	private function getText() {

		$revision = Revision::newFromPageId( $this->pageId );

		if ( !$revision instanceof Revision) {
				#echo "Error while processing on " . $this->pageId . "\n";
				return '';
		}

		$content = $revision->getContent( Revision::RAW );
		return ContentHandler::getContentText( $content );
	}

	public function getDBKey() {
		$revision = Revision::newFromPageId( $this->pageId );
		if ( $revision ) {
			return $revision->getTitle()->getDBKey();
		}
	}

	private function findProvider() {
		$text = $this->getText();

		if ( preg_match( "@{{Curatevideo\|(" . implode( '|', wfVideoProviders() ) . ")\|([^\|]+)\|@", $text, $matches ) ) {
			$provider = wfGetVideoProvider( $matches[1] );
			$this->videoURL = $matches[2];
		} else {
			$provider = false;
		}

		return $provider;
	}

	// Identifies whether the video is available to view
	public function isAvailable() {
		if ( !$this->provider ) {
			// provider wasn't recognized, failsafe so that we *don't* delete the video
			return true;
		}

		#echo "Checking video " . $this->getDBKey() . "\n";
		return $this->provider->videoExists( $this->videoURL );
	}

	public function getProvider() {
		if ( !$this->provider ) {
			return '';
		}

		return $this->provider->getCode();
	}

	public function getProviderURL() {
		return $this->provider->getURL( $this->videoURL );
	}

	public function remove($deleteSummary = 'Removing unavailable video', $editSummary = 'Removing unavailable video from article') {
		$this->removeLinkedSections($editSummary);
		$this->delete($deleteSummary);
	}

	private function delete($deleteSummary) {
		$page = WikiPage::newFromId( $this->pageId );
		$errors = array();
		$page->doDeleteArticle(  $deleteSummary, false, 0, true, $errors, $this->bot );
	}

	private function removeLinkedSections($editSummary) {
		$dbr = wfGetDB( DB_SLAVE );

		// get all transclusions of this video page
		// normally there is only 1, so we can limit the rows to 500
		$res = $dbr->select( array( 'page', 'templatelinks' ),
					array( 'page_id' ),
					array( 'tl_title' => $this->getDBKey(),
						'tl_namespace' => NS_VIDEO,
						'tl_from=page_id' ), __METHOD__, array( 'LIMIT' => 500 ) );

		foreach ( $res as $row ) {
			$this->removeVideoSection( $row->page_id, $editSummary );
		}
	}

	private function removeVideoSection( $pageId, $editSummary ) {
		$revision = Revision::newFromPageId( $pageId );

		if ( !$revision || !$revision instanceof Revision ) {
			echo 'Error: Unable to fetch revision for page ' . $pageId . "\n";
			return;
		}

		$content = $revision->getContent( Revision::RAW );
		$text = ContentHandler::getContentText( $content );

		try {
			$new = Wikitext::removeVideoSection( $text );
		} catch( Exception $e ) {
			// no section
			echo "Error: " . $e->getMessage() . "\n";
			return; // nothing to do, go home
		}
		$page = WikiPage::newFromID( $pageId );
		$content = ContentHandler::makeContent( $new, $revision->getTitle() );
		$page->doEditContent( $content, $editSummary, EDIT_UPDATE | EDIT_FORCE_BOT, false, $this->bot );
	}
}
