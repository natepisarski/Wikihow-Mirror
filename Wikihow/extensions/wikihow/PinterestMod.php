<?php
/*
	Pinterest google chrome extension and mobile app pull pin descriptions from the alt field of images.
	This class removes the trailing .jpg and Version # from the alt field to make pinterest pins look cleaner.
	Note: Pinterest has poor documentation on how it scrapes information to populate the fields for its pins.
	If no alt field exists, the pin will automatically scrape from the <title> tag.
 */
class PinterestMod {

	const IMG_SELECTOR = '.mwimg a.image img';
	const JPG_REGEX = '/.+?(?=.jpg)/';
	const VERSION_REGEX = '/.+?(?=.Version)/';

	public static function modifyDOM() {
		if ( DeferImages::isArticlePage() ) {
			$images = pq( self::IMG_SELECTOR );

			foreach( $images as $node ) {
				$img = pq( $node );
				$altTag = $img->attr( 'alt' );
				$matches = array();
				preg_match( self::JPG_REGEX, $altTag, $matches );
				if ( count($matches) && $matches[0] ) {
					$altTag = $matches[0];
				}
				preg_match( self::VERSION_REGEX, $altTag, $matches );
				// change the alt tag if we count a match, otherwise keep as before
				if ( count($matches) && $matches[0] ) {
					$altTag = $matches[0];
				}
				if ($altTag) {
					$newAltTag = wfMessage('aria_image', $altTag)->showIfExists();
					if ($newAltTag) {
						$altTag = $newAltTag;
					}
				}
				$img->attr( 'alt', $altTag );
			}
		}
		return true;
	}
}
