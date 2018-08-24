<?php

require_once __DIR__ . '/../../Maintenance.php';

class importNewCategoryListing extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "";
	}

	public function execute() {
		$filename = $this->getArg(0);

		$categories = [];

		$handle = fopen($filename, 'r');
		$count = 0;
		while ($line = fgets($handle)) {
			if($count++ == 0) {
				continue;
			}
			$parts = explode("\t", $line);
			$subcat = trim($parts[3]);
			$image = trim(substr($parts[4], (strrpos($parts[4], "/")+1)));
			if(!isset($categories[$subcat])) {
				$categories[$subcat] = ['cl_category' => trim($parts[2]), 'cl_sub_category' => $subcat, 'cl_sub_image' => $image, 'cl_article_id1' => trim($parts[1])];
			} else {
				if(!isset($categories[$subcat]['cl_article_id2'])) {
					$categories[$subcat]['cl_article_id2'] = trim($parts[1]);
				} else {
					$categories[$subcat]['cl_article_id3'] = trim($parts[1]);
				}
			}
		}

		$categories = array_values($categories);

		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete('categorylisting', '*', __FILE__);
		$dbw->insert(
			'categorylisting',
			$categories,
			__FILE__
		);
	}

}

$maintClass = 'importNewCategoryListing';
require_once RUN_MAINTENANCE_IF_MAIN;
