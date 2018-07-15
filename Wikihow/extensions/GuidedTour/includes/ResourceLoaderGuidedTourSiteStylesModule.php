<?php
/**
 * Resource loader module for GuidedTour site CSS.
 *
 * @file
 * @author Matthew Flaschen mflaschen@wikimedia.org
 */

/**
 * Module for on-wiki GuidedTour CSS customizations
 */
class ResourceLoaderGuidedTourSiteStylesModule extends ResourceLoaderWikiModule {
	/**
	 * Gets list of pages used by this module
	 *
	 * @param $context ResourceLoaderContext
	 *
	 * @return Array: List of pages
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		return array(
			// JRS commenting out since we don't want to use styles from the MediaWiki namespace
			/*'MediaWiki:Guidedtour-custom.css' => array( 'type' => 'style' ),*/
		);
	}

	/**
	 * Load custom CSS after all other Guiders and GuidedTour CSS.
	 * @return Array
	 */
	public function getDependencies() {
		return array(
			'ext.guidedTour.styles',
		);
	}
}
