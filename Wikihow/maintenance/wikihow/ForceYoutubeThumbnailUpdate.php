<?php
//
// Generate a list of all URLs for the sitemap generator and for
// scripts that crawl the site (like to generate cache.wikihow.com)
//

require_once __DIR__ . '/../Maintenance.php';

class ForceYoutubeThumbnailUpdate extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'id', 'Article Id', true, true, 'i' );
	}

	public function execute() {
		$id = $this->getOption( 'id' );
		$title = Title::newFromID($id);
		if(!$title || !$title->exists()) {
			return;
		}

		$youtubeId = WikihowMobileHomepage::getVideoId($id);
		if(is_null($youtubeId)) return;

		SchemaMarkup::getYouTubeVideo($title, $youtubeId, true);
	}
}

$maintClass = "ForceYoutubeThumbnailUpdate";
require_once RUN_MAINTENANCE_IF_MAIN;
