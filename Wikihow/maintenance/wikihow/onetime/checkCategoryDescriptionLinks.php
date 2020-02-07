<?php

require_once __DIR__ . '/../../Maintenance.php';


class checkCategoryDescriptionLinks extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "";
	}

	public function execute() {
		global $wgServer;

		$categories = DatabaseHelper::batchSelect('categorylinks', 'cl_to', [], __METHOD__, ['GROUP BY' => 'cl_to']);

		print "Category Page\tLink\n";
		foreach($categories as $cat) {
			$catTitle = Title::newFromText($cat->cl_to, NS_CATEGORY);

			$description = AdminCategoryDescriptions::getCategoryDescription($catTitle);
			if(preg_match_all('/<a href=\"\/([^\"]*)\"/', $description, $matches)) {
				foreach($matches[1] as $match) {
					print $catTitle->getFullURL() . "\t$wgServer/" . $match . "\n";
				}
			}

		}
	}
}


$maintClass = "checkCategoryDescriptionLinks";
require_once RUN_MAINTENANCE_IF_MAIN;

