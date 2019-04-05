<?php
if (!defined('MEDIAWIKI')) die();

abstract class ArticleViewer extends ContextSource {
	var $articles, $articles_start_char;

	function __construct(IContextSource $context) {
		$this->setContext($context);
		$this->clearState();
	}

	function clearState() {
		$this->articles = array();
		$this->articles_start_char = array();
	}

	abstract function doQuery();
}

class FaViewer extends ArticleViewer {
	function doQuery() {
		$fas = FeaturedArticles::getTitles(45);
		foreach ($fas as $fa) {
			$this->articles[] = Linker::link($fa['title']);
		}
	}
}

class RsViewer extends ArticleViewer {
	var $maxNum;

	function __construct(IContextSource $context, $maxNum = 16) {
		parent::__construct($context);
		$this->maxNum = $maxNum;
	}

	function doQuery() {
		$rs = RisingStar::getRS();

		if ($rs) {
			$i = 0;
			foreach ($rs as $titleString => $star) {
				$title = Title::newFromText($titleString);
				if ($title) {
					$this->articles[] = Linker::link($title);
				}
				if (++$i >= $this->maxNum) {
					break;
				}
			}
		}
	}
}

class WikihowCategoryViewer extends ArticleViewer {
	var $title, $limit,
		$children,
		$articles_fa,
		$sortAlpha,
		$article_info; //this is used to keep info about the various titles (currently using template info)

	public function __construct($title, IContextSource $context, $sortAlpha = false) {
		global $wgCategoryPagingLimit;

		parent::__construct($context);

		$this->title = $title;
		$this->limit = $wgCategoryPagingLimit;
		$this->sortAlpha = $sortAlpha;
	}

	public function getFAs() {
		$this->clearCategoryState();
		$this->doQuery();
		return $this->articles_fa;
	}

	public function clearCategoryState() {
		$this->articles = array();
		$this->children = array();
		$this->articles_fa = array();
		$this->article_info = array();
		$this->article_info_fa = array();
	}

	public function getChildren() {
		return $this->children;
	}

	public function getNumArticles() {
		return count($this->articles);
	}

	public function getNumOnPage($page, $perPage) {
		$pages = count($this->articles)/$perPage;
		if ($page < $pages) {
			return $perPage;
		} else {
			return ($pages-floor($pages))*$perPage;
		}
	}

	/****
	 * @param $page  = 1 based number
	 * @param $number = number of articles to return
	 * @return array
	 */
	public function getArticlesMobile($page, $number) {
		if ($page <= 0) {
			$page = 1;
		}
		$articlesArray = array_slice($this->articles, ($page-1)*$number, $number);
		$articles = ['articles' => []];
		foreach ($articlesArray as $title) {
			$data = [];
			$data['url'] = $title->getLocalUrl();
			$data['title'] = $title->getText();
			$data['thumb_url'] = wfGetPad($this->getThumbUrl($title, CategoryData::CAT_IMG_WIDTH, CategoryData::CAT_IMG_HEIGHT));
			$articles['articles'][] = $data;
		}
		return $articles;
	}

	protected function getThumbUrl($title, $width, $height) {
		$file = Wikitext::getTitleImage($title);
		$thumbUrl = "";
		if (!$file || !$file->exists()) {
			$file = Wikitext::getDefaultTitleImage($title);
		}

		if ($file && $file->exists()) {
			// Use same transform params as "Related Images"
			$params = array(
				'width' => $width,
				'height' => $height,
				'crop' => 1
			);
			$thumb = $file->transform($params, 0);
		}

		if ($thumb) {
			$thumbUrl = $thumb->getUrl();
		}

		return $thumbUrl;
	}

	public function doQuery($getSubcats = true, $calledFromCategoryPage = true) {
		$dbr = wfGetDB(DB_REPLICA);

		// Show only indexable articles to anons
		$indexConds = $this->getUser()->isAnon() ? 'ii_policy IN (1, 4)' : '1=1';
		$safeTitle = $this->title->getDBKey();
		if ($this->sortAlpha) {
			$order = "cl_sortkey ASC";
		} else {
			$order = "ti_30day_views DESC";
		}

		$res = $dbr->select(
			['page', 'categorylinks', 'index_info', 'titus_copy'],
			['page_title', 'page_namespace', 'page_len', 'page_further_editing', 'cl_sortkey', 'page_counter', 'page_is_featured', 'page_id as pageid'],
			['cl_to' => $safeTitle, 'page_namespace != ' . NS_CATEGORY, $indexConds],
			__METHOD__,
			['GROUP BY' => 'page_id', 'ORDER BY' => $order, 'LIMIT' => ($this->limit + 1)],
			[
				'categorylinks' => ['INNER JOIN', 'cl_from = page_id'],
				'index_info' => ['LEFT JOIN', 'ii_page = page_id'],
				'titus_copy' => ['LEFT JOIN', 'ti_page_id = page_id AND ti_language_code = "en"']
			]
		);

		$count = 0;
		$this->nextPage = null;
		foreach ($res as $row) {
			if ( !Hooks::run( "WikihowCategoryViewerQueryBeforeProcessTitle", array( $row->pageid ) ) ) {
				continue;
			}

			if (!$this->processRow($row, $count)) {
				break;
			}
		}

		if ($calledFromCategoryPage && $count == 0 && Misc::isAltDomain()) {
			Misc::exitWith404();
		}

		if ($getSubcats) {
			// get all of the subcategories this time
			$res = $dbr->select(
				['page', 'categorylinks', 'index_info'],
				['page_title', 'page_namespace', 'page_len', 'page_further_editing', 'cl_sortkey', 'page_counter', 'page_is_featured'],
				['cl_to' => $safeTitle, 'page_namespace' => NS_CATEGORY, $indexConds],
				__METHOD__,
				['GROUP BY' => 'page_id', 'ORDER BY' => 'cl_sortkey'],
				[
					'categorylinks' => ['INNER JOIN', 'cl_from = page_id'],
					'index_info' => ['LEFT JOIN', 'ii_page = page_id']
				]
			);

			$count = 0;
			foreach ($res as $row) {
				$this->processRow($row, $count);
			}
		}
	}

	private function processRow($x, &$count) {
		if (++$count > $this->limit) {
			// We've reached the one extra which shows that there are
			// additional pages to be had. Stop here...
			$this->nextPage = $x->cl_sortkey;
			return false;
		}

		$title = Title::makeTitle($x->page_namespace, $x->page_title);
		if ($title && $title->isMainPage()) {
			//we don't want the main page to show up on any category pages
			return true;
		}

		if ($title->inNamespace(NS_CATEGORY)) {
			// check for subcategories
			$subcats = $this->getSubcategories($title);
			if (sizeof($subcats) == 0) {
				$this->addSubcategory($title);
			} else {
				$this->addSubcategory($title, $subcats);
			}
		} else {
			// page in this category
			$info_entry = array();
			$info_entry['page_counter'] = $x->page_counter;
			$info_entry['page_len'] = $x->page_len;
			$info_entry['page_further_editing'] = $x->page_further_editing;
			$isFeatured = !empty($x->page_is_featured);
			$info_entry['page_is_featured'] = intval($isFeatured);
			$info_entry['number_of_edits'] = isset($x->edits) ? $x->edits : 0;
			$info_entry['template'] = isset($x->tl_title) ? $x->tl_title : '';
			$pageIsRedirect = isset($x->page_is_redirect) ? $x->page_is_redirect : false;
			$this->addPage($title, $pageIsRedirect, $info_entry);
			if ($info_entry['page_is_featured']) {
				$this->addFA($title);
			}
		}
		return true;
	}

	public function getSubcategories($title) {
		$onlyIndexed = $this->getUser()->isAnon() || Misc::isMobileMode();
		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['categorylinks', 'page', 'index_info'];
		$fields = ['page_title', 'page_namespace'];
		$where = [
			'page_id = cl_from',
			'ii_page = cl_from',
			'cl_to' => $title->getDBKey(),
			'page_namespace' => NS_CATEGORY
		];
		if ($onlyIndexed) {
			$where['ii_policy'] = [1, 4];
		}
		$options = ['ORDER BY' => 'cl_sortkey'];
		$res = $dbr->select($tables, $fields, $where, __METHOD__, $options);
		$results = array();
		foreach ($res as $row) {
			$results[] = Title::makeTitle($row->page_namespace, $row->page_title);
		}
		return $results;
	}

	/*
	*  Returns a query associative array if the viewMode is text, blank otheriwise (for image mode)
	*/
	static function getViewModeArray($context) {
		return $context->getRequest()->getVal('viewMode', 0) ? array('viewMode'=>'text'): array();
	}

	/*
	*  Returns a query string parameter if the viewMode is text, blank otheriwise (for image mode)
	*/
	static function getViewModeParam() {
		global $wgRequest;
		return $wgRequest->getVal('viewMode', 0) ? "viewMode=text" : '';
	}
	/**
	 * Add a subcategory to the internal lists
	 */
	function addSubcategory($title, $subcats = null) {
		if ($subcats == null) {
			$this->children[] = $title;
		} else {
			$rx = [];
			$rx[] = $title;
			$rx[] = $subcats;
			$this->children[] = $rx;
		}
	}

	/**
	 * Add a miscellaneous page
	 */
	function addPage($title, $isRedirect = false, $info_entry = null) {

		// AG - the makeSizeLinkObj is deprecated and Linker::link takes care of size/color of the link now
		$this->articles[] = $title;
		if (is_array($info_entry))
			$this->article_info[] = $info_entry;
	}

	function addFA($title) {
		$this->articles_fa[] = $title;
	}

	function getArticlesFurtherEditing($articles, $article_info) {
		$articles_with_templates = array();
		$articles_with_templates_info = array();

		for ($index = 0; $index < count($articles); $index++) {
			if (is_array($article_info) && isset($article_info[$index])) {
				$page_len = $article_info[$index]['page_len'];
				// save articles with certain templates to put at the end
				//TODO: internationalize the shit out of this
				if ($article_info[$index]['page_further_editing'] == 1 || $page_len < 750) {
					if (strpos($articles[$index], ":") === false) {
						$articles_with_templates[] = $articles[$index];
						$articles_with_templates_info[] = $article_info[$index];
						continue;
					}
				}
			}
		}

		if (sizeof($articles_with_templates) > 0) {
			$chunk = (int)(count($articles) / 2);
			$html = "";

			$html .= "<h3>" . wfMessage('articles_that_require_attention')->text() . "</h3>\n";

			$html .= "<p>" . wfMessage('cat_attention_description', count($articles_with_templates)) . "</p>\n";

			$html .= '<ul>' . "\n";
			for ($index = 0; $index < sizeof($articles_with_templates); $index++) {
				if (($index == $chunk) && (sizeof($articles_with_templates) > 5)) {
					$html .= '</ul> <ul>' . "\n";
				}
				$html .= "<li><a href='{$articles_with_templates[$index]->getLocalUrl()}'>{$articles_with_templates[$index]->getText()}</a></li>\n";
			}
			$html .= "</ul><div class=\"clearall\"></div>";
		}

		return $html;
	}
}
