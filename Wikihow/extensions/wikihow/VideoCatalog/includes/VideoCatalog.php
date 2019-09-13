<?php

/*
 * Video Catalog.
 */
class VideoCatalog {

	/* Public Static Methods */

	/**
	 * Check if, during a page render, a summary video being rendered on the page should be included
	 * in the VideoCatalog.
	 *
	 * @param  {Context} $context Context of page view
	 * @return {bool} Include summary video
	 */
	public static function shouldIncludeSummaryVideo( $context ) {
		$langCode = $context->getLanguage()->getCode();
		$isGoogleAmpMode = GoogleAmp::isAmpMode( $context->getOutput() );
		$isAltDomainView = Misc::isAltDomain();
		$isAltDomainArticle = class_exists( 'AlternateDomain' ) &&
			AlternateDomain::getAlternateDomainForPage( $context->getTitle()->getArticleID() );

		return (bool)(
			// Only on English, for now, could change in the future
			$langCode == 'en' &&
			// Not in Google AMP, for now, could change in the future
			!$isGoogleAmpMode &&
			// Don't use VideoBrowser on alt-domains, even rendering a 404 for a real article on
			// an alt-domain could hit this code so we need this check to prevent that
			!$isAltDomainView &&
			// Check article being on alt-domain as well, because logged in users can see alt-domain
			// articles on the main site so $isAltDomainView isn't enough
			!$isAltDomainArticle
		);
	}

	/**
	 * Parse a source URL.
	 *
	 *	"Tie a Tie Step 0.360p.mp4" will return:
	 *		[ 'name' => 'Tie a Tie', 'step' => 0, 'version' => 1, 'size' => 360 ]
	 *	"/a/a4/Tie a Tie Step 0.360p.mp4" will return:
	 *		[ 'name' => 'Tie a Tie', 'step' => 0, 'version' => 1, 'size' => 360 ]
	 *	"Tie a Tie Step 0 Version 2.720p.mp4" will return:
	 *		[ 'name' => 'Tie a Tie', 'step' => 0, 'version' => 2, 'size' => 720 ]
	 *	"/6/6d/Tie a Tie Step 0 Version 2.720p.mp4" will return:
	 *		[ 'name' => 'Tie a Tie', 'step' => 0, 'version' => 2, 'size' => 720 ]
	 *
	 * @param {string} $url Source URL for video
	 * @return {array|null} Parsed URL contianing 'name', 'step', 'version' and 'size' values or
	 *	 null if URL was malformed
	 */
	public static function parseSourceUrl( $url ) {
		// Extract name, step and version
		preg_match( '/^(?:\/\w\/\w\w\/)?(.*)(?: Step (\d+))(?: Version (\d+))?\.(\d+)p\.mp4$/', $url, $matches );
		if ( !count( $matches ) ) {
			return null;
		}
		return (object)[
			'name' => $matches[1],
			'step' => (int)$matches[2],
			'version' => $matches[3] ? (int)$matches[3] : 1,
			'size' => (int)$matches[4]
		];
	}

	/**
	 * Logs a message and variable export to the "videocatalog" log.
	 *
	 * @param {string} $message Message to log
	 * @param {mixed} $object Variable to export
	 */
	protected static function log( $message, $object = [] ) {
		$export = var_export( $object, true );
		wfDebugLog( 'videocatalog', "-\n>> VideoCatalog {$message} {$export} \n" );
	}

	/**
	 * Get the wikiname of a video from the wikivisual_vid_names table.
	 *
	 * Because the same output URI can be re-used for multiple versions, this function always
	 * returns the highest version wikiname.
	 *
	 * @param {number} $articleId Article ID video is related to
	 * @param {string} $outputUri Output URI on S3
	 * @return {string} Highest-version wiki name of video file
	 */
	public static function getWikiname( $articleId, $outputUri ) {
		$dbr = wfGetDB( DB_REPLICA );
		// Get wikiname for preview image
		$conditions = [
			'filename = ' . $dbr->addQuotes( implode( '/', [ $articleId, basename( $outputUri ) ] ) ),
			'filename = '. $dbr->addQuotes( implode( '/', [ '', basename( $outputUri ) ] ) )
		];
		preg_match( '/(\d+)-0\.\d+p\.mp4/', $outputUri, $m );
		if ( $m ) {
			$conditions[] = 'filename = '. $dbr->addQuotes( implode( '/', [ $m[1], basename( $outputUri ) ] ) );
		}
		$rows = $dbr->select(
			'wikivisual_vid_names',
			[ 'wikiname' ],
			[ $dbr->makeList( $conditions, LIST_OR ) ],
			__METHOD__
		);

		// Get the highest version
		$wikiname = false;
		$version = 0;
		foreach ( $rows as $row ) {
			$url = self::parseSourceUrl( $row->wikiname );
			if ( $url->version > $version ) {
				$wikiname = $row->wikiname;
			}
		}

		if ( !$wikiname ) {
			var_dump( [ 'Cannot find filename in wikivisual_vid_names', $conditions ] );
			return '';
		}
		return $wikiname;
	}

	/**
	 * Update VideoCatalog using WikiVisualS3VideosAdded hook.
	 *
	 * @param {integer} $articleId Article ID of page to add videos for
	 * @param {string} $creator Name of user running script that invoked this method
	 * @param {array} $videosAdded List of videos added to the page
	 */
	public static function onWikiVisualS3VideosAdded( $articleId, $creator, $videosAdded ) {
		static::log( 'onWikiVisualS3VideosAdded', [
			'articleId' => $articleId,
			'creator' => $creator,
			'videosAdded' => $videosAdded
		] );
		// User master DB since items were just added to wikivisual_vid_transcoding_output that we
		// need access to
		$dbw = wfGetDB( DB_MASTER );
		foreach ( $videosAdded as $videoAdded ) {
			$source = self::parseSourceUrl( $videoAdded['aws_uri_out'] );
			if ( $source['step'] == 0 ) {
				// Lookup sources for added video
				$outputs = $dbw->select(
					'wikivisual_vid_transcoding_output',
					[ 'aws_uri_out' ],
					[ 'aws_job_id' => $videoAdded['aws_job_id'] ],
					__METHOD__
				);
				foreach ( $outputs as $output ) {
					$wikiname = self::getWikiname( $articleId, $output->aws_uri_out );
					echo " " . $wikiname . "\n";
					$item = VideoCatalogItem::getFromSourceUrl( $wikiname );
					if ( !$item ) {
						// No item exists, create one
						$item = VideoCatalogItem::newFromSourceUrl( $wikiname );
						if ( !$item ) {
							// Instantiation failed
							return false;
						}
						$item->setOriginalArticleId( $articleId );
						// Optionally override default item values
						if ( $videoAdded['previewMediawikiName'] ) {
							$item->setPosterUrl( $videoAdded['previewMediawikiName'] );
						}
						if ( !$item->create() ) {
							// Creation failed
							return false;
						}
					}

					// Create source
					$source = VideoCatalogSource::newFromItemIdAndUrl( $item->getId(), $wikiname );
					if ( !$source->create() ) {
						return false;
					}
				}
			}
		}
		return true;
	}
	/**
	 * Link a catalog item to a page.
	 *
	 * This should be called when parsing/rendering the summary video section of an article.
	 *
	 * Item and link rows will be automatically found/updated/created using the given $articleId and
	 * by parsing $sourceUrl.
	 *
	 * @param {integer} $articleId Article ID of page to link item to
	 * @param {string} $videoUrl Video source URL (omit to unlink)
	 * @param {string} [$posterUrl] Optional poster image URL
	 * @param {string} [$posterUrl] Optional short clip video URL
	 * @param {bool} Article link successfully updated
	 */
	public static function updateArticleLink( $articleId, $sourceUrl = '', $posterUrl = '', $clipUrl = '' ) {
		static::log( 'updateArticleLink', [
			'articleId' => $articleId,
			'sourceUrl' => $sourceUrl,
			'posterUrl' => $posterUrl,
			'clipUrl' => $clipUrl
		] );

		if ( $sourceUrl === '' ) {
			// Remove link
			$link = VideoCatalogLink::getFromArticleId( $articleId );
			return $link ? $link->delete() : false;
		}

		// Read/create item
		$item = VideoCatalogItem::getFromSourceUrl( $sourceUrl );
		if ( $item ) {
			// Check for change in poster image URL
			if ( $item->getPosterUrl() !== $posterUrl || $item->getClipUrl() !== $clipUrl ) {
				// Update existing item
				$item->setPosterUrl( $posterUrl );
				$item->setClipUrl( $clipUrl );
				if ( !$item->update() ) {
					// Update failed
					return false;
				}
			}
		} else {
			// No item exists, create one
			$item = VideoCatalogItem::newFromSourceUrl( $sourceUrl );
			$item->setPosterUrl( $posterUrl );
			$item->setClipUrl( $clipUrl );
			$item->setOriginalArticleId( $articleId );
			if ( !$item->create() ) {
				// Creation failed
				return false;
			}
		}

		// Look for existing link for article
		$link = VideoCatalogLink::getFromArticleId( $articleId );
		if ( $link ) {
			// Check for change in item
			if ( $link->getItemId() !== $item->getId() ) {
				// Update existing link
				$link->setItemId( $item->getId() );
				if ( !$link->update() ) {
					// Update failed
					return false;
				}
			}
		} else {
			// No link exists, create one
			$link = VideoCatalogLink::newFromArticleIdAndItemId( $articleId, $item->getId() );
			if ( !$link->create() ) {
				// Create failed
				return false;
			}
		}
		return true;
	}
}
