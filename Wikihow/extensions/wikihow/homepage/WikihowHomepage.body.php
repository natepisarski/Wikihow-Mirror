<?php

class WikihowHomepage extends Article {
	var $faStream;
	var $rsStream;
	const FA_STARTING_CHUNKS = 6;
	const FA_ENDING_CHUNKS = 2;

	// Used only for intl
	const FA_MIDDLE_CHUNKS = 2;
	// Used only for English
	const RS_CHUNKS = 2;

	const SINGLE_WIDTH = 163; // (article_shell width - 2*article_inner padding - 3*SINGLE_SPACING)/4
	const SINGLE_HEIGHT = 119; //should be .73*SINGLE_WIDTH
	const SINGLE_SPACING = 16;

	function __construct( Title $title, $oldId = null ) {
		global $wgHooks;

		// We've recently been seeing errors on the home page that relate to memory allocation in AbuseFilter
		// and MobileFrontend on doh, and just now, live. We're not sure why the Main-Page is allocating so much
		// memory per request, but we can fix this since it seems like a Main-Page issue.
		// Fatal error: Allowed memory size of 134217728 bytes exhausted (tried to allocate 7864320 bytes) in /opt/wikihow/prod/extensions/MobileFrontend/MobileFrontend.i18n.php on line 17826
		ini_set('memory_limit', '256M');
		$wgHooks['ShowBreadCrumbs'][] = array('WikihowHomepage::removeBreadcrumb');
		$wgHooks['AfterHeader'][] = array('WikihowHomepage::showTopImage');
		parent::__construct($title, $oldId);
	}

	function view() {
		global $wgOut, $wgUser, $wgLanguageCode, $wgContLang;

		// add this head item for facbook instant article verification
		$wgOut->addHeadItem('fbinstant', '<meta property="fb:pages" content="91668358574" />');

		$wgHooks['ShowGrayContainer'][] = array('WikihowHomepage::removeGrayContainerCallback');

		$faViewer = new FaViewer($this->getContext());
		$this->faStream = new WikihowArticleStream($faViewer, $this->getContext(), 0);
		$html = $this->faStream->getChunks(WikihowHomepage::FA_STARTING_CHUNKS, WikihowHomepage::SINGLE_WIDTH, WikihowHomepage::SINGLE_SPACING, WikihowHomepage::SINGLE_HEIGHT);

		// We add more from the FA stream on international, because we don't have rising stars on international
		if ($wgLanguageCode != "en") {
			$this->faStream = new WikihowArticleStream($faViewer, $this->getContext(), $this->faStream->getStreamPosition() + 1);
			$html2 = $this->faStream->getChunks(WikihowHomepage::FA_MIDDLE_CHUNKS, WikihowHomepage::SINGLE_WIDTH, WikihowHomepage::SINGLE_SPACING, WikihowHomepage::SINGLE_HEIGHT);

		}
		else {
			$rsViewer = new RsViewer($this->getContext());
			$this->rsStream = new WikihowArticleStream($rsViewer, $this->getContext());
			$html2 = $this->rsStream->getChunks(WikihowHomepage::RS_CHUNKS, WikihowHomepage::SINGLE_WIDTH, WikihowHomepage::SINGLE_SPACING, WikihowHomepage::SINGLE_HEIGHT);
		}
		$this->faStream = new WikihowArticleStream($faViewer, $this->getContext(), $this->faStream->getStreamPosition() + 1);
		$html3 = $this->faStream->getChunks(WikihowHomepage::FA_ENDING_CHUNKS, WikihowHomepage::SINGLE_WIDTH, WikihowHomepage::SINGLE_SPACING, WikihowHomepage::SINGLE_HEIGHT);

		Hooks::run( 'WikihowHomepageFAContainerHtml', array( &$html, &$html2, &$html3 ) );

		$totalHtml = $html . $html2 . $html3;

		//now alter this to reduce the number of videos
		$tempDoc = phpQuery::newDocument($totalHtml);
		$targetCount = pq( '.thumbnail' )->length * 0.1;
		$numVideos = pq( 'video' )->length;
		$indices = range( 0, $numVideos - 1 );
		shuffle( $indices );
		for ( $i = 0; $i < pq( 'video' )->length - $targetCount; $i++ ) {
			$video = pq('video')->eq( $indices[$i] );
			$image = Misc::getMediaScrollLoadHtml( 'img', ['src' => $video->attr( 'data-poster' )] );
			$video->next('script')->remove();
			$video->next('noscript')->remove();
			$video->replaceWith( $image );
		}
		$totalHtml = $tempDoc->documentWrapper->markup();

		$container = Html::rawElement( 'div', ['id' => 'fa_container'], $totalHtml );

		$wgOut->addHTML( $container );

		$wgOut->setRobotPolicy('index,follow', 'Main Page');
		$wgOut->setCdnMaxage(3600);
	}

	public static function removeGrayContainerCallback(&$showGrayContainer) {
		$showGrayContainer = false;
		return true;
	}

	public static function removeBreadcrumb(&$showBreadcrumb) {
		$showBreadcrumb = false;
		return true;
	}

	/**
	 * NOTE: Much of this code is duplicated in WikihowMobileHomepage.body.php (Alberto - 2018-09)
	 */
	public static function showTopImage() {
		global $wgUser, $wgLanguageCode;

		$items = array();

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(WikihowHomepageAdmin::HP_TABLE, array('*'), array('hp_active' => 1), __METHOD__, array('ORDER BY' => 'hp_order'));

		$i = 0;
		foreach ($res as $result) {
			$item = new stdClass();
			$title = Title::newFromID($result->hp_page);
			// Append Google Analytics tracking to slider URLs
			$item->url = $title->getLocalURL() . "?utm_source=wikihow&utm_medium=main_page_carousel&utm_campaign=desktop";
			$item->text = $title->getText() . wfMessage('howto_suffix')->showIfExists();
			$imageTitle = Title::newFromID($result->hp_image);
			if ($imageTitle) {
				$file = wfFindFile($imageTitle->getText());
				if ($file) {
					$item->imagePath = wfGetPad($file->getUrl());
					$item->itemNum = ++$i;
					$items[] = $item;
				}
			}
		}
		Hooks::run( 'WikihowHomepageAfterGetTopItems', array( &$items ) );

		$searchTitle = Title::makeTitle(NS_SPECIAL, "LSearch");
		$search = '
		<form id="cse-search-hp" name="search_site" action="/wikiHowTo" method="get">
		<input type="text" class="search_box" name="search" />
		</form>';

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'items' => $items,
			'imagePath' => wfGetPad('/skins/owl/images/home1.jpg'),
			'search' => $search
		));
		$html = $tmpl->execute('top.tmpl.php');

		echo $html;

		return true;
	}

	public static function onArticleFromTitle(&$title, &$article) {
		if ($title->getText() == wfMessage('mainpage')->text()) {

			if (Misc::isMobileMode()) {
				$article = new WikihowMobileHomepage($title);
			} else {
				$article = new WikihowHomepage($title);
			}
			return true;
		}

		return true;
	}

	//add our site search schema.org json-ld for Google
	public static function onArticleJustBeforeBodyClose() {
		global $wgTitle;

		if ($wgTitle->getText() == wfMessage('mainpage')->text()) {
			$search_url = self::getSearchUrl();

			if (!$search_url) {
				// We need to make sure the current language has a search engine. For new languages, if there isn't one
				// set up, we need to set up a CSE. Ask Chris to set it up then get the URL from him for it.
				echo "ERROR: The Sitelinks Searchbox configuration is missing! Please edit: " . __FILE__;
				return true;
			}

			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array(
				'hp_url' => json_encode($wgTitle->getFullUrl()),
				'search_url' => json_encode($search_url),
			));
			$html = $tmpl->execute('sitesearchbox.tmpl.php');
			print $html;
		}
		return true;
	}

	public static function getSearchUrl($query = '') {
		global $wgDomainName;

		$query = $query ? urlencode($query) : '{search_term_string}';
		return 'https://' . $wgDomainName . '/wikiHowTo?search=' . $query;
	}

	public static function getLanguageLinksForHomePage() {
		global $wgActiveLanguages, $wgLanguageCode;

		if (wfMessage('mainpage')->inLanguage($wgLanguageCode) == '') {
			print 'STOP! There is no home page defined for this language. Please add it.';
			exit;
		}

		$languageHPs = array();
		$langs = array_merge(['en'], $wgActiveLanguages);
		foreach ($langs as $lang) {
			$hp = wfMessage('mainpage')->inLanguage($lang);
			if ($hp == '') continue;
			$languageHPs[] = $lang.':'.$hp;
		}
		return $languageHPs;
	}

	public static function categoryWidget(): String {
		global $wgCategoryNames, $wgCategoryNamesEn;

		$categories = [];
		$lang = RequestContext::getMain()->getLanguage();
		$lang_code = $lang->getCode();

		foreach ($wgCategoryNames as $ck => $cat) {
			$category = urldecode(str_replace("-", " ", $cat));
			if ($lang_code == "zh") $category = $lang->convert($category);

			// For Non-English we shall try to get the category name from message for the link. We fallback to the category name, because
			// abbreviated category names are used for easier display. For the icon, we convert to English category names of the corresponding category.
			if ($lang_code != "en") {
				$enCat = $wgCategoryNamesEn[$ck];
				$msgKey = strtolower(str_replace(' ','-',$enCat));
				$foreignCat = str_replace('-',' ',urldecode(wfMessage($msgKey)->text()));
				$catTitle = Title::newFromText("Category:" . $foreignCat);
				if (!$catTitle) $catTitle = Title::newFromText("Category:" . $cat);
				$cat = $enCat;
			}
			else {
				$catTitle = Title::newFromText("Category:" . $category);
			}

			// $categories[$category] = new stdClass();
			// $categories[$category]->url = $catTitle->getLocalURL();

			//icon
			// if ($lang_code != "en") $cat = $wgCategoryNamesEn[$ck];

			// $cat_class = 'cat_'.strtolower(str_replace(' ','',$cat));
			// $cat_class = preg_replace('/&/','and',$cat_class);
			// $categories[$category]->icon = $cat_class;

			$categories[] = [
				'icon' => CategoryListing::getCategoryIcon($category),
				'link' => $catTitle->getLocalURL(),
				'name' => $category
			];
		}

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'header' => wfMessage('browsecategories')->text(),
			'categories' => $categories
		];

		return $m->render('categoryWidget.mustache', $vars);
	}
}
