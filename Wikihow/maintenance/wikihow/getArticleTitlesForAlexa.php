<?php
//
// One time script to get a list of article titles to use for the alexa model
//

require_once __DIR__ . '/../Maintenance.php';

class GetArticleTitlesForAlexa extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->mDescription = 'Get a list of all indexable article titles in the NS_MAIN namespace';
    }

    public function execute() {
    	global $wgLanguageCode;

	    $dbr = wfGetDB(DB_REPLICA);
	    $res = $dbr->select(
	    	'page',
		    ['page_id', 'page_title'],
		    ['page_namespace' => NS_MAIN, 'page_is_redirect' => 0],
		    __FILE__
	    );

	    foreach($res as $row) {
		    $id = $row->page_id;
			$t = Title::newFromId($id);
		    $indexed = RobotPolicy::isTitleIndexable($t);
		    if (!$indexed) continue;

		    $titles = [$t];
		    $titles = TitleFilters::filterExplicitAidsForAlexa($titles, $wgLanguageCode);
		    $titles = TitleFilters::filterTopLevelCategories($titles, [CAT_RELATIONSHIPS]);
		    $titles = TitleFilters::filterByBadWords($titles, BadWordFilter::TYPE_ALEXA, $wgLanguageCode);
		    if (empty($titles)) continue;

		    echo $t->getText() . "\n";
	    }
	}
}

$maintClass = 'GetArticleTitlesForAlexa';
require_once RUN_MAINTENANCE_IF_MAIN;
