<?php

class CategoryListing extends SpecialPage {
	const LISTING_TABLE = "categorylisting";
	const CAT_WIDTH = 459;
	const CAT_HEIGHT = 344;
	const ARTICLE_WIDTH = 137;
	const ARTICLE_HEIGHT = 120;

	function __construct($source = null) {
		global $wgHooks;
		parent::__construct( 'CategoryListing' );
		if (RequestContext::getMain()->getLanguage()->getCode() == "en") {
			$wgHooks['ShowSideBar'][] = ['AdminCategoryDescriptions::removeSideBarCallback'];
		}
	}

	function execute($par) {
		global $wgHooks;

		$out = $this->getOutput();

		$this->setHeaders();
		$out->setPageTitle(wfMessage("Categories")->text());
		$out->setRobotPolicy('index,follow');
		$out->setSquidMaxage(6 * 60 * 60);


		$catData = CategoryData::getCategoryListingData();
		if (Misc::isMobileMode()) {
			$this->renderMobile($catData);
		} else {
			$wgHooks['ShowGrayContainer'][] = array('CategoryListing::removeGrayContainerCallback');

			// allow varnish to redirect this page to mobile if browser conditions are right
			Misc::setHeaderMobileFriendly();

			$this->getCategoryListingData($catData);
			$this->renderDesktop($catData);
		}
	}

	function renderMobile($catData) {
		$out = $this->getOutput();
		$out->addModules('mobile.wikihow.mobile_category_page');
		$out->setPageTitle(wfMessage('categories')->text());
		$out->addHTML(CategoryCarousel::getCategoryListingHtml($catData));
	}

	function renderDesktop($catData) {
		$out = $this->getOutput();
		if (RequestContext::getMain()->getLanguage()->getCode() != "en") {
			$out->addHTML("<br /><br />");
			$out->addHTML("<div class='section_text'>");
			foreach ($catData['subcats'] as $row) {
				$out->addHTML("<div class='thumbnail'><a href='{$row['url']}'><img src='{$row['img_url']}'/><div class='text'><p><span>{$row['cat_title']}</span></p></div></a></div>");
			}
			$out->addHTML("<div class='clearall'></div>");
			$out->addHTML("</div><!-- end section_text -->");
		} else {


			$css = Misc::getEmbedFile('css', __DIR__ . '/categories-listing.css');
			$out->addHeadItem('listcss', HTML::inlineStyle($css));

			$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
			$options = array('loader' => $loader);
			$m = new Mustache_Engine($options);
			$html = $m->render("/templates/categorylisting", $catData);

			$out->addHTML($html);
		}
	}


	public function isMobileCapable() {
		return true;
	}

	private function getCategoryListingData(&$data) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			self::LISTING_TABLE,
			'*',
			[],
			__METHOD__
		);

		$data['cat_width'] = self::CAT_WIDTH;
		$data['cat_height'] = self::CAT_HEIGHT;

		foreach ($res as $row) {
			$catName = $row->cl_category;
			foreach ($data['subcats'] as &$item) {
				if ($item['cat_title'] == $catName) {
					if (!isset($item['subsubcats'])) {
						$item['subsubcats'] = [];
					}
					$imageTitle = Title::newFromText($row->cl_sub_image, NS_IMAGE);
					$file = wfFindFile($imageTitle, false);
					if (!$file) {
						$file = Wikitext::getDefaultTitleImage($imageTitle);
					}

					$params = array(
						'width' => self::CAT_WIDTH,
						'height' => self::CAT_HEIGHT,
						'crop' => 1
					);
					$thumb = $file->transform($params, 0);
					$title = Title::newFromText($row->cl_sub_category, NS_CATEGORY);
					$subsubcat = [
						'cat_text' => $row->cl_sub_category,
						'cat_url' => $title->getLocalURL(),
						'cat_image' => wfGetPad($thumb->getUrl()),
						'cat_titles' => []
					];

					for ($i = 1; $i <= 3; $i++) {
						$title = Title::newFromId($row->{"cl_article_id{$i}"});
						if (!$title) {
							UserMailer::send(
								new MailAddress('bebeth@wikihow.com'),
								new MailAddress('ops@wikihow.com'),
								"Category listing page issue",
								"The article with id " . $row->{"cl_article_id{$i}"} . " no longer exists"
							);
							continue;
						}
						if (!RobotPolicy::isTitleIndexable($title)) {
							UserMailer::send(
								new MailAddress('bebeth@wikihow.com'),
								new MailAddress('ops@wikihow.com'),
								"Category listing page issue",
								"The article with id " . $row->{"cl_article_id{$i}"} . " is no longer indexed"
							);
							continue;
						}
						if ($title->isRedirect()) {
							UserMailer::send(
								new MailAddress('bebeth@wikihow.com'),
								new MailAddress('ops@wikihow.com'),
								"Category listing page issue",
								"The article with id " . $row->{"cl_article_id{$i}"} . " is now a redirect"
							);
							continue;
						}
						$image = ImageHelper::getArticleThumb($title, self::ARTICLE_WIDTH, self::ARTICLE_HEIGHT);
						$subsubcat['cat_titles'][] = [
							'title_text' => $title->getText(),
							'title_url' => $title->getLocalURL(),
							'title_image' => $image
						];
					}
					$item['subsubcats'][] = $subsubcat;
				}
			}
		}
	}

	public static function removeGrayContainerCallback(&$showGrayContainer) {
		$showGrayContainer = false;
		return true;
	}
}

/****
 CREATE TABLE `categorylisting` (
  `cl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cl_category` varbinary(255) NOT NULL DEFAULT '',
  `cl_sub_category` varbinary(255) NOT NULL DEFAULT '',
  `cl_sub_image` varbinary(255) NOT NULL DEFAULT '',
  `cl_article_id1` int(10) unsigned NOT NULL,
  `cl_article_id2` int(10) unsigned NOT NULL,
  `cl_article_id3` int(10) unsigned NOT NULL,
  PRIMARY KEY (`cl_id`)
) ENGINE=InnoDB;
******/
