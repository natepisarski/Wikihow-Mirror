<?php

require_once __DIR__ . '/../../Maintenance.php';

class getIndexStatus extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "";
		//you MUST use the lang parameter to do anything but EN
	}

	public function execute() {
		global $wgTitle, $wgLanguageCode;

		$host = Misc::getLangBaseURL($wgLanguageCode, false);

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('page', 'page_id', ['page_namespace' => NS_CATEGORY, 'page_is_redirect' => 0], __METHOD__);
		$ids = [];
		foreach($res as $row) {
			$ids[] = $row->page_id;
		}

		$skin = new SkinWikihowskin();
		$context = $skin->getContext();
		echo "Category Url\tIndex status\tIndexed article count\tBreadcrumb\n";
		foreach($ids as $id) {
			$title = Title::newFromID($id);
			if(!$title)
				continue;
			$wgTitle = $title;
			$url = $host ."/". $title->getPrefixedURL();
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$ret = curl_exec($ch);
			$needle = '<meta name="robots"';
			$startLoc = strpos($ret, $needle);
			$endLoc = strpos($ret, "\n", $startLoc);
			$indexStatus = substr($ret, $startLoc, $endLoc - $startLoc);

			$item = ['url' => $url, 'index' => $indexStatus];
			$res = $dbr->select(
				['categorylinks', 'index_info'],
				'count(*) as C',
				['cl_to' => $title->getDBkey(), 'ii_namespace != 14', 'ii_policy' => [1, 4]],
				__METHOD__,
				[],
				['index_info' => ['LEFT JOIN', 'ii_page = cl_from']]
			);
			$row = $dbr->fetchRow($res);
			if($row !== false) {
				$item['count'] = $row['C'];
			} else {
				$item['count'] = 0;
			}

			$fullCategoryTree = CategoryHelper::cleanCurrentParentCategoryTree( CategoryHelper::getCurrentParentCategoryTree($title));
			$breadcrumb = WikihowHeaderBuilder::getCategoryLinks(true, $context, $fullCategoryTree, true);

			$item['breadcrumb'] = $breadcrumb;
			echo $item['url'] . "\t"  . $item['index'] . "\t" . $item['count'] . "\t" . $item['breadcrumb'] . "\n";
		}

	}


}

$maintClass = 'getIndexStatus';
require_once RUN_MAINTENANCE_IF_MAIN;
