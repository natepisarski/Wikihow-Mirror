<?php

require_once __DIR__ . '/../Maintenance.php';

/*
CREATE TABLE `recipe_schema` (
`rs_page_id` int(10) unsigned NOT NULL,
`rs_rev_id` int(10) unsigned NOT NULL,
`rs_data` blob NOT NULL,
UNIQUE KEY (`rs_page_id`)
);
 */
// this  will update all indexable recipe pages based on latest good revision
// print out recip schema for a given title
// useful for seeing what the recipe schema will look like on a given article
// cat ~/titles  | php getRecipeSchema.php | paste -s -d','
class updateRecipeSchema extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "update recipe scheme";
		$this->addOption( 'title', 'title of page', false, true, 't' );
    }

	private function processTitle( $title, $forceUpdate = false ) {
		global $wgTitle;
		if ( !$title ) {
			return;
		}
		$gr = GoodRevision::newFromTitle( $title );
		$oldTitle = $wgTitle;
		$wgTitle = $title;
		SchemaMarkup::processRecipeSchema( $title, $gr, $forceUpdate );
		$wgTitle = $oldTitle;
	}

	private function searchCategoryLinks( &$pageIds, &$subCats ) {
		$subCatsNew = array();
		$dbr = wfGetDb( DB_REPLICA );
        $table = 'categorylinks';
        $vars = array( 'cl_from', 'cl_type' );
		foreach ( $subCats as $subCat ) {
			$conds = array( "cl_to" => $subCat );
			$options = array();
			$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
			foreach ( $res as $row ) {
				if ( $row->cl_type == 'page' ) {
					$pageIds[] = $row->cl_from;
				} else if ( $row->cl_type == 'subcat' ) {
					$title = Title::newFromID( $row->cl_from );
					$subCatsNew[] = $title->getDBKey();
				}
			}
		}
		$subCats = $subCatsNew;
	}

	private function updateAll() {
		$pageIds = array();
		$subCats = array( 'Recipes' );
		$this->searchCategoryLinks( $pageIds, $subCats );

		while ( $subCats ) {
			$this->searchCategoryLinks( $pageIds, $subCats );
		}
		$indexableTitles = array();
		foreach ( $pageIds as $pageId ) {
			$title = Title::newFromID( $pageId );
			if (RobotPolicy::isIndexable( $title ) ) {
				$indexableTitles[] = $title;
			}
		}
		foreach( $indexableTitles as $title ) {
			$this->processTitle( $title );
		}
	}

	public function execute() {
		global $wgTitle;

		$checkAllRecipes = true;

		$title = $this->getOption( 'title' );
		if ( $title ) {
			$checkAllRecipes = false;
		}

		if ( $checkAllRecipes == true ) {
			$this->updateAll();
			die();
		}

		$title = Misc::getTitleFromText( $title );
		$forceUpdate = true;
		$this->processTitle( $title, $forceUpdate );
	}
}


$maintClass = "updateRecipeSchema";
require_once RUN_MAINTENANCE_IF_MAIN;

