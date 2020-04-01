<?php

class MyWikihow extends SpecialPage {

	const LIMIT = 13;
	const BOXES_TO_SHOW = 10;

	public function __construct() {
		global $wgHooks;

		parent::__construct("MyWikihow", "MyWikihow");
		$wgHooks['getMobileToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		$out = $this->getOutput();

		//only for mobile
		if (!Misc::isMobileMode()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($this->getRequest()->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$cats = trim($this->getRequest()->getVal('cats'));
			$cats = explode(',',$cats);
			$articles = $this->getArticles($cats);
			$boxes = $this->formatBoxes($articles);
			print json_encode($boxes);
			return;
		}
		elseif ($this->getRequest()->getVal('getsubcats')) {
			$out->setArticleBodyOnly(true);
			$subcats = $this->getSubCats($this->getRequest()->getVal('getsubcats'));
			print json_encode($subcats);
			return;
		}

		$out->setPageTitle(wfMessage('mywikihow_title'));
		$out->addModules('ext.wikihow.my_wikihow');
		$out->addModuleStyles('ext.wikihow.my_wikihow_styles');

		$template = 'my_wikihow';

		$tmpl = new EasyTemplate(__DIR__);
		$tmpl->set_vars($this->getVars());
		$out->addHTML($tmpl->execute($template,$vars));
	}

	private function getVars() {
		$vars = array(
			'hdr' => wfMessage('mywikihow_hdr')->text(),
			'btn' => wfMessage('mywikihow_btn')->text(),
			'cats' => $this->getCats()
		);
		return $vars;
	}

	private static function getCats() {
		global $wgCategoryNames;
		$cats = $wgCategoryNames;
		return $cats;
	}

	private static function getSubCats($topcat) {
		$t = Title::newFromText($topcat, NS_CATEGORY);
		//TODO: add caching???
		$subcats = Sitemap::getSubcategories($t);
		return $subcats;
	}

	private function getArticles($cats) {
		$articles = array();

		if ($cats[0] != '') {
			//use their chosen cats
			$dbr = wfGetDB(DB_REPLICA);

			$catarray = [];
			foreach ($cats as $cat) {
				$cat = str_replace(' ', '-', $cat);
				if ($cat) {
					$catarray[] = $cat;
				}
			}

			$sql = "SELECT cl_sortkey, page_id, page_title, page_namespace, page_is_featured
				FROM (page, categorylinks )
				LEFT JOIN newarticlepatrol
					ON nap_page = page_id
				WHERE
					cl_from = page_id
					AND cl_to IN (" . $dbr->makeList($catarray) . ")
					AND page_namespace != " . NS_CATEGORY . "
					AND (nap_demote = 0 OR nap_demote IS NULL)
					AND page_is_featured = 1
				GROUP BY page_id
				ORDER BY page_is_featured DESC, cl_sortkey
				LIMIT ". self::LIMIT;

			$res = $dbr->query($sql, __METHOD__);
			foreach ($res as $row) {
				$articles[] = $row->page_title;
			}
		}

		$filler_count =  self::LIMIT - count($articles);
		if ($filler_count > 0) {
			//grab Featureds
			$fas = FeaturedArticles::getTitles($filler_count);
			foreach ($fas as $fa) {
				$articles[] = $fa['title']->getText();
			}
		}

		return $articles;
	}

	private function formatBoxes($articles) {
		$boxes = array();
		$html = '';

		//grab box info
		foreach ($articles as $article) {
			if (!$article) continue;
			$article = str_replace('-',' ',$article);
			$t = Title::newFromText($article);
			if ($t && $t->exists()) {
				$temp_box = WikihowMobileTools::makeFeaturedArticlesBox($t);
				if ($temp_box && $temp_box->url) {
					$boxes[] = $temp_box;
					if (count($boxes) == self::BOXES_TO_SHOW) break;
				}
			}
		}

		//format 'em
		foreach ($boxes as $key => $rel) {
			$html .= '<a href="'. $rel->url .'" class="related_box" style="'. $rel->bgimg .'">'.
						'<p><span>How to</span> '. $rel->name .'</p></a>';
			if ($key > 0 && $key % 2 != 0) {
				$html .= '</div><div class="mwh_row">';
			}
		}

		$html = '<div class="mwh_boxes"><div class="mwh_row">'.$html.'</div></div>';

		return $html;
	}
}
