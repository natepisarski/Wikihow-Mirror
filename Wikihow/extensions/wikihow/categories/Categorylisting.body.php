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

		$wgHooks['UseMobileRightRail'][] = ['CategoryListing::removeSideBarCallback'];

		$this->setHeaders();
		$out->setPageTitle(wfMessage("Categories")->text());
		$out->setRobotPolicy('index,follow');
		$out->setCdnMaxage(6 * 60 * 60);


		$catData = CategoryData::getCategoryListingData();
		if (Misc::isMobileMode()) {
			$this->renderMobile($catData);
		} else {
			$wgHooks['ShowGrayContainer'][] = array('CategoryListing::removeGrayContainerCallback');

			$this->getCategoryListingData($catData);
			$this->renderDesktop($catData);
		}
	}

	function renderMobile($catData) {
		$out = $this->getOutput();

		if (RequestContext::getMain()->getLanguage()->getCode() != "en") {
			$out->addHTML("<div class='section_text section_grid'>");
			foreach ($catData['subcats'] as $row) {
				$out->addHTML("<div class='thumbnail'><a href='{$row['url']}'><img src='{$row['img_url']}'/><div class='text'><p><span>{$row['cat_title']}</span></p></div></a></div>");
			}
			$out->addHTML("<div class='clearall'></div>");
			$out->addHTML("</div><!-- end section_text -->");
			$out->addModuleStyles('ext.wikihow.mobile_category_listing_intl');
		} else {
			$this->getCategoryListingData($catData);

			$out->addModuleStyles('ext.wikihow.mobile_category_listing');

			$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
			$options = array('loader' => $loader);
			$m = new Mustache_Engine($options);
			$html = $m->render("/templates/responsive.mustache", $catData);

			$out->addHTML($html);
		}

		$out->setPageTitle(wfMessage('categories')->text());
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
					$item['img'] = $this->getCategoryIcon($catName);
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

						if(Misc::isMobileMode()) {
							$image = ImageHelper::getGalleryImage($title, self::ARTICLE_WIDTH, self::ARTICLE_HEIGHT);
						} else {
							$image = ImageHelper::getArticleThumb($title, self::ARTICLE_WIDTH, self::ARTICLE_HEIGHT);
						}
						$catInfo = [
							'title_text' => $title->getText(),
							'title_url' => $title->getLocalURL(),
							'title_image' => $image,
							'howto' => wfMessage('howto_prefix')->text()
						];
						if($i < 3) {
							$catInfo['spacer'] = 1;
						}
						$subsubcat['cat_titles'][] = $catInfo;
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

	public static function getCategoryIcon($catName) {
		global $wgCategoryNames;
		foreach($wgCategoryNames as $key => $cat) {
			if($catName == $cat) {
				switch($key) {
					case CAT_ARTS:
						return '/extensions/wikihow/categories/images/arts_and_entertainment.svg';
					case CAT_CARS:
						return '/extensions/wikihow/categories/images/cars_and_other_vehicles.svg';
					case CAT_COMPUTERS:
						return '/extensions/wikihow/categories/images/computers_and_electronics.svg';
					case CAT_EDUCATION:
						return '/extensions/wikihow/categories/images/education_and_communication.svg';
					case CAT_FAMILY:
						return '/extensions/wikihow/categories/images/family_life.svg';
					case CAT_FINANCE:
						return '/extensions/wikihow/categories/images/finance_and_business.svg';
					case CAT_FOOD:
						return '/extensions/wikihow/categories/images/food_and_entertaining.svg';
					case CAT_HEALTH:
						return '/extensions/wikihow/categories/images/health.svg';
					case CAT_HOBBIES:
						return '/extensions/wikihow/categories/images/hobbies_and_crafts.svg';
					case CAT_HOLIDAYS:
						return '/extensions/wikihow/categories/images/holidays_and_tradition.svg';
					case CAT_HOME:
						return '/extensions/wikihow/categories/images/home_and_garden.svg';
					case CAT_PERSONAL:
						return '/extensions/wikihow/categories/images/personal_care_and_style.svg';
					case CAT_PETS:
						return '/extensions/wikihow/categories/images/pets_and_animals.svg';
					case CAT_PHILOSOPHY:
						return '/extensions/wikihow/categories/images/philosophy_and_religion.svg';
					case CAT_RELATIONSHIPS:
						return '/extensions/wikihow/categories/images/relationships.svg';
					case CAT_SPORTS:
						return '/extensions/wikihow/categories/images/sports_and_fitness.svg';
					case CAT_TRAVEL:
						return '/extensions/wikihow/categories/images/travel.svg';
					case CAT_WORK:
						return '/extensions/wikihow/categories/images/work_world.svg';
					case CAT_YOUTH:
						return '/extensions/wikihow/categories/images/youth.svg';
					case CAT_WIKIHOW:
						return '/extensions/wikihow/categories/images/wikihow.svg';
				}
			}
		}
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
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
