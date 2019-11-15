<?php

class CopyArticleImages {

	public static function addImages( $pages, $langs ) {
		global $wgLanguageCode;

		$status = [ 'queued' => [], 'not-found' => [] ];

		// Short-cut when there's nothing to do
		if ( !count( $pages ) ) {
			return $status;
		}

		// Build list of IDs and index of article URLs by article ID
		$fromIDs = [];
		$urlLookup = [];
		$baseUrl = Misc::getLangBaseURL( $wgLanguageCode );
		foreach ( $pages as $page ) {
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
				self::addImage( $link->fromAID, $link->toLang, $link->toAID );
				$status['queued'][] = [
					'fromURL' => $link->fromURL,
					'toURL'=> $link->toURL,
					'fromLang' => $link->fromLang,
					'toLang' => $link->toLang
				];
				$ids[] = $link->fromAID;
			}
			// Add items for requested pages that didn't have matching links
			foreach ( $fromIDs as $id ) {
				if ( !in_array( $id, $ids ) ) {
					// Add error image to the database
					self::addImage( $id, $lang, 0 );
					if ( isset( $urlLookup[$id] ) ) {
						$status['not-found'][] = [
							'fromURL' => $urlLookup[$id],
							'toURL' => '',
							'fromLang' => $wgLanguageCode,
							'toLang' => $lang
						];
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Add images to translation table for automatically adding to
	 * translated pages
	 */
	private static function addImage( $fromAID, $toLang, $toAID ) {
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
}
