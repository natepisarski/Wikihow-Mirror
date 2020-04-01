<?php

/********************************************************************************************************
 * SherlockController is a Special Page that acts as a controller the Sherlock data-collection			*
 * tool. SherlockController only acts on post requests, and stops execution if invalid data is posted.	*
 * Author: Sam Gussman (intern 2015), Responsible Adult: Reuben Smith									*
 ********************************************************************************************************/
class SherlockController extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'SherlockController' );
	}

	public function execute( $params ) {
		$context = RequestContext::getMain();
		$request = $context->getRequest();

		// Make sure it was a post using the mediawiki request object.
		if (! $request->wasPosted()){
			// Do nothing
			return;
		}

		// Get the Sherlock data and create a new article entry
		$data = $request->getValues();

		// Check tha the data is all there
		if ($data["sha_id"] === NULL || $data["sha_index"] === NULL || $data["shs_key"] === NULL || $data["sha_title"] === NULL){
			// Do nothing
			return;
		}
		Sherlock::logSherlockArticle($data["sha_id"], $data["sha_index"], $data["shs_key"], $data["sha_title"]);
	}

	public function isMobileCapable() {
		return true;
	}
}

