<?php


require_once __DIR__ . '/../Maintenance.php';

class UpdateTopCategoryData extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = ".";
	}

	public function execute() {
		TopCategoryData::setFeaturedArticlePages();
		$topCats = CategoryHelper::getTopLevelCategoriesForDropDown();
		foreach($topCats as $cat) {
			$title = Title::newFromText($cat, NS_CATEGORY);
			if($title && $title->exists()) {
				TopCategoryData::setPagesForCategory($title->getDBkey(), DesktopWikihowCategoryPage::PULL_CHUNKS);
			}
		}
	}
}

$maintClass = 'UpdateTopCategoryData';
require_once RUN_MAINTENANCE_IF_MAIN;
