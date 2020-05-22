<?php

require_once __DIR__ . '/../Maintenance.php';

class updateHomepageNewpages extends Maintenance {

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		global $wgLanguageCode;
		NewPages::setHomepageArticles($wgLanguageCode);
		NewPages::setCategorypageArticles($wgLanguageCode);

		if($wgLanguageCode == "en") {
			//add new ids to the spreadsheet
			$newPages = NewPages::getHomepageArticles();
			$rows = [];
			foreach ($newPages as $title) {
				if ($title && $title->exists()) {
					$rows[] = [
						$title->getArticleID(),
						date('m/d/Y')
					];
				}
			}

			$sheetId = '1ZySO8TLroY20yZFvckLF4ZfTkFniOkdgueNXZl7zH68';

			$sheet = 'Sheet1';
			GoogleSheets::appendRows($sheetId, $sheet, $rows);
		}
	}
}

$maintClass = 'updateHomepageNewpages';
require_once RUN_MAINTENANCE_IF_MAIN;

