<?php
/**
 * API for adding image transfers.
 *
 * @class
 */
class ApiAddImageTransfers extends ApiBase {

	/* Methods */

	public function execute() {
		global $wgLanguageCode;

		$user = $this->getUser();

		// Restrict to staff on en
		if ( !in_array( 'staff', $user->getGroups() ) || $wgLanguageCode !== 'en' ) {
			$this->dieUsage( 'Permission denied.', 'permissiondenied' );
			return;
		}

		$params = $this->extractRequestParams();

		$pages = self::normalizePageList( explode( "\n", $params['pages'] ) );
		$langs = explode( ',', $params['langs'] );

		$queued = [];
		$failed = [];

		if ( count( $pages['matches'] ) ) {
			// Build list of IDs and index of article URLs by article ID
			$fromIDs = [];
			$urlLookup = [];
			$baseUrl = Misc::getLangBaseURL( $wgLanguageCode );
			foreach ( $pages['matches'] as $page ) {
				$fromIDs[] = (int)$page['page_id'];
				$urlLookup[$page['page_id']] = "{$baseUrl}/{$page['page_title']}";
			}

			// Build list of language links
			foreach ( $langs as $lang ) {
				// Get links for requested pages
				$links = TranslationLink::getLinks(
					$wgLanguageCode, $lang, [ 'tl_from_aid in (' . implode( ',', $fromIDs ) . ')' ]
				);
				// Add items for requested pages with matched links
				$ids = [];
				foreach ( $links as $link ) {
					$this->addImage( $link->fromAID, $link->toLang, $link->toAID );
					$queued[] = [
						'fromAID' => $link->fromAID,
						'toAID' => $link->toAID,
						'fromLang' => $link->fromLang,
						'toLang' => $link->toLang,
						'fromURL' => $link->fromURL,
						'toURL' => $link->toURL
					];
					$ids[] = $link->fromAID;
				}
				// Add items for requested pages that didn't have matching links
				foreach ( $fromIDs as $id ) {
					if ( !in_array( $id, $ids ) ) {
						// Add error image to the database
						$this->addImage( $id, $lang, 0 );
						if ( isset( $urlLookup[$id] ) ) {
							$failed[] = [
								'fromAID' => $id,
								'toAID' => null,
								'fromLang' => $wgLanguageCode,
								'toLang' => $lang,
								'fromURL' => $urlLookup[$id],
								'toURL' => null
							];
						}
					}
				}
			}
		}

		$result = [ 'queued' => $queued, 'failed' => $failed ];
		$this->getResult()->setIndexedTagName( $result['queued'], 'queued' );
		$this->getResult()->setIndexedTagName( $result['failed'], 'failed' );
		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * Add images to translation table for automatically adding to
	 * translated pages
	 */
	private function addImage( $fromAID, $toLang, $toAID ) {
		global $wgUser, $wgLanguageCode;

		// Queue job
		$it = new ImageTransfer();
		$it->fromLang = $wgLanguageCode;
		$it->fromAID = $fromAID;
		$it->toLang = $toLang;
		$it->toAID = $toAID;
		$it->creator = $wgUser->getName();
		$it->timeStarted = wfTimestampNow();
		$it->insert();
	}

	public function getAllowedParams() {
		return [
			'pages' => [ ApiBase::PARAM_TYPE => 'string' ],
			'langs' => [ ApiBase::PARAM_TYPE => 'string' ],
			'token' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	public function needsToken() {
		return 'csrf';
	}

	public function getTokenSalt() {
		return '';
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	public function getPossibleErrors() {
		return [
			[ 'permissiondenied' ]
		];
	}

	private static function normalizePageList( $pages ) {
		// Split list of pages into lists of article IDs and URLs
		$ids = [];
		$urls = [];
		foreach ( $pages as $page ) {
			if ( is_numeric( $page ) ) {
				$ids[] = [ 'lang' => 'en', 'id' => $page ];
			} else if ( $page !== '' ) {
				$urls[] = $page;
			}
		}

		// Get lists of pages from article IDs and URLs
		$pagesFromIds = Misc::getPagesFromLangIds( $ids, [ 'page_id','page_title' ] );
		$pagesFromUrls = Misc::getPagesFromURLs( $urls, [ 'page_id', 'page_title' ] );

		// Build match and error lists
		$matches = [];
		$invalid = [];
		foreach ( $pagesFromIds as $page ) {
			// Detect bad IDs
			if ( !isset( $page['page_title'] ) ) {
				$invalid[] = (int)$page['page_id'];
				continue;
			}
			$matches[] = $page;
		}
		foreach ( $pagesFromUrls as $url => $page ) {
			// Detect bad URLs
			if ( $page['lang'] !== 'en' ) {
				$invalid[] = $url;
				continue;
			}
			$matches[] = $page;
		}

		// Detect bad URLs
		foreach ( $urls as $url ) {
			// Any articles that were dropped in getPagesFromURLs
			if ( !isset( $pagesFromUrls[$url] ) ) {
				$invalid[] = $url;
			}
		}

		return [ 'matches' => $matches, 'invalid' => $invalid ];
	}
}
