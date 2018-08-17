<?php

class GoogSearch extends SpecialPage {

	public function __construct() {
		parent::__construct( 'GoogSearch' );
		$this->setListed(false);
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrum) {
		$showBreadCrum = false;
		return true;
	}

	public static function removeGrayContainerCallback(&$showGrayContainer) {
		$showGrayContainer = false;
		return true;
	}

	public static function getSearchBox($formid, $q = '', $size = 30) {
		global $wgLanguageCode;
		$search_box = wfMessage('cse_search_box_new', "", $formid, $size, htmlspecialchars($q), $wgLanguageCode)->text();
		$search_box = preg_replace('/\<[\/]?pre\>/', '', $search_box);
		return $search_box;
	}

	public static function getSearchBoxJS() {
		global $wgLanguageCode;
		$html = <<<EOHTML
<script type="text/javascript">
	$(document).ready(function () {
		loadGoogleCSESearchBox('$wgLanguageCode');
	});
</script>
EOHTML;
		return $html;
	}

	public function execute($par) {
		global $wgOut, $wgRequest, $wgHooks;

		$wgHooks['ShowBreadCrumbs'][] = array($this, 'removeBreadCrumbsCallback');
		$wgHooks['ShowSideBar'][] = array($this, 'removeSideBarCallback');
		$wgHooks['ShowGrayContainer'][] = array($this, 'removeGrayContainerCallback');

		$me = Title::makeTitle(NS_SPECIAL, "GoogSearch");

		$q = $wgRequest->getVal('q');
		$q = strip_tags($q); // clean html to avoid XSS attacks
		$wgRequest->setVal('q', $q);

		$start = $wgRequest->getInt('start', 0);

		$wgOut->setHTMLTitle(wfMessage('lsearch_title_q', $q)->text());
		$wgOut->setRobotPolicy('noindex,nofollow');

		$search_page_results = wfMessage('cse_search_page_results')->text();
		$search_page_results = preg_replace('/\<[\/]?pre\>/', '', $search_page_results);

		$wgOut->addHTML('<div class="wh_block cse_search_page_block">'.$search_page_results.'</div>');
		return;
	}

}
