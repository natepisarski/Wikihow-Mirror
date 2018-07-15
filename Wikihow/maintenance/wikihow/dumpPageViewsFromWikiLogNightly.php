<?php
//
// Dumps all page views for Main and User namespace pages. These are dumped with
// with page view counts for yesterday to a flat file that is imported into
// master database page table elsewhere.
//

require_once __DIR__ . '/../Maintenance.php';

/**
 * Dumps page view data for data for yesterday, for just the relevant
 * domains, from wiki_log.page_views
 */
class DumpPageViewsFromWikiLogNightlyMaintenance extends Maintenance {

	const PAGE_VIEWS_LOG_DB = 'wiki_log';

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Dump page views from wiki_log table to page tables";
		$this->addOption( 'outfile', 'The filename for the flat page view output', true, true, 'o' );
    }

	/**
	 * Run the maintenance task to dump
	 */
	public function execute() {
		global $wgLanguageCode;

		require_once __DIR__ . "/../../extensions/wikihow/titus/Titus.class.php";
		$titus = new TitusDB();
		$dbr = $titus->getTitusDB();
		$dbr->selectDB(self::PAGE_VIEWS_LOG_DB);

		date_default_timezone_set('America/Los_Angeles');

		$yesterday = date('Y-m-d', strtotime('-1 day', strtotime(date('Ymd', time()))));
		$desktopSite = Misc::getLangDomain($wgLanguageCode, false);
		$mobileSite = Misc::getLangDomain($wgLanguageCode, true);

		// Select all page view data from the wiki_log database on titus server
		$res = $dbr->select('page_views',
			array('pv_t', 'page'),
			array('domain' => array($desktopSite, $mobileSite),
				'day' => $yesterday),
			__METHOD__);

		// Read all the pageid and page view count data from table
		$pages = array();
		foreach ($res as $row) {
			$page = $row->page;
			if ($page !== '' && $page{0} == '/') {
				$page = mb_substr($page, 1);
			}

			$title = Title::newFromText($page);
			if ($title && $title->exists() && $title->inNamespaces(NS_MAIN, NS_USER, NS_CATEGORY)) {
				$pageid = $title->getArticleId();
				if (!isset($pages[$pageid])) {
					$pages[$pageid] = 0;
				}
				$pages[$pageid] += $row->pv_t;
			}
		}

		// Write page view data for each article to a flat file
		$outFilename = $this->getOption('outfile');
		$fp = fopen($outFilename, 'wb') or die ("ERROR: could not write to file $outFilename\n");
		foreach ($pages as $pageid => $count) {
			fwrite($fp, "$pageid $count\n");
		}
		fclose($fp);
	}
}

$maintClass = "DumpPageViewsFromWikiLogNightlyMaintenance";
require_once RUN_MAINTENANCE_IF_MAIN;

