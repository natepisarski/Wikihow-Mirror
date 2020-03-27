<?php

class WikihowMobileHomepage extends Article {
	const POPULAR_LIST = "hp_popular";
	const EXPERT_LIST = "hp_expert";
	const WATCH_LIST = "hp_watch";
	const COAUTHOR_LIST = "hp_coauthor";

	const MAX_WATCH = 12;
	const MAX_FEATURED = 24;
	const MAX_EXPERT = 6;
	const MAX_ALTDOMAIN = 50;
	const MAX_RS = 4;

	static $isAltDomain = null;
	static $altDomainName;

	const THUMB_WIDTH = 375;
	const THUMB_HEIGHT = 250;

	function __construct( Title $title, $oldId = null ) {
		parent::__construct($title, $oldId);
	}

	function view() {
		global $wgHooks;
		$wgHooks['UseMobileRightRail'][] = ['WikihowMobileHomepage::removeSideBarCallback'];

		$out = $this->getContext()->getOutput();
		if(self::$isAltDomain == null)
			self::setAltDomain();

		IOSHelper::addIOSAppBannerTag();

		/* This will not work if any dependencies are added to the mobile homepage module.
		 * Perhaps an alternative would be creating a dependency module that loads the styles
		 * first to ensure the slider JS loads afterward.
		 */
		$out->addModuleStyles(['zzz.mobile.wikihow.homepage.styles']);
		$out->addModules(['zzz.mobile.wikihow.homepage.scripts']);

		$vars = [
			'howto' => wfMessage('howto_prefix')->showIfExists(),
			'hp_trustworthy_cta' => wfMessage('hp_trustworthy_cta')->text()
		];
		if( !wfMessage('howto_prefix')->exists() ) {
			$vars['hp_noprefix'] = true;
		}

		//coauthor
		$this->getCoauthorArticles($vars);

		//popular
		$this->getPopularArticles($vars);

		//expert interviews
		$this->getExpertArticles($vars);

		$this->getWatchArticles($vars);

		//fas
		$this->getFeaturedArticles($vars);

		$this->getAltDomainArticles($vars);
		if(self::$isAltDomain && !in_array(self::$altDomainName, ['wikihow-fun.com', 'wikihow.life'])) {
			//$vars['showTrustworthy'] = true;
		}

		//newsletter
		WikihowMobileHomepage::getNewsletterWidget($vars);

		//categories
		$vars = array_merge($vars, WikihowMobileHomepage::categoryWidget());

		//international
		$vars = array_merge($vars, WikihowMobileHomepage::internationalWidget());

		if(self::$isAltDomain) {
			$vars['trustworthyLink'] = '/wikiHow:About-wikiHow.'. AlternateDomain::getAlternateDomainClass(self::$altDomainName);
			$vars['hp_trustworthy_cta'] = wfMessage('hp_trustworthy_cta_altdomain')->text();
			$out->addHtml(self::renderTemplate('responsive_altdomain.mustache', $vars));
		} else {
			$vars['trustworthyLink'] = '/wikiHow:Delivering-a-Trustworthy-Experience';
			$out->addHtml(self::renderTemplate('responsive.mustache', $vars));
		}

		$out->setRobotPolicy('index,follow', 'Main Page');
	}

	public static function setAltDomain() {
		self::$isAltDomain = AlternateDomain::onAlternateDomain();
		if(self::$isAltDomain) {
			self::$altDomainName = AlternateDomain::getAlternateDomainForCurrentPage();
		}
	}

	public static function removeBreadcrumb(&$showBreadcrumb) {
		$showBreadcrumb = false;
		return true;
	}

	/**
	 * NOTE: Much of this code is duplicated in WikihowHomepage.body.php (Alberto - 2018-09)
	 */
	public static function showTopSection() {
		$vars = [];
		//if(!RequestContext::getMain()->getUser()->isLoggedin()) {
			$vars['loggedout'] = true;
		//}

		if(self::$isAltDomain == null)
			self::setAltDomain();

		$vars['topText'] = wfMessage('hp_top')->text();
		$vars['hp_question'] = wfMessage('hp_question')->text();
		$vars['searchPlaceholder'] = wfMessage('hp_search_placeholder')->text();
		$vars['classes'] = self::$isAltDomain ? 'altdomain' : '';
		if(self::$isAltDomain && in_array(self::$altDomainName, ['wikihow-fun.com', 'wikihow.life'])) {
			$vars['useAltTopImage'] = true;
		}
		if(self::$isAltDomain) {
			$vars['hp_question'] = wfMessage('hp_question_' . AlternateDomain::getAlternateDomainClass(self::$altDomainName))->text();
			$vars['topText'] = wfMessage('hp_top_' . AlternateDomain::getAlternateDomainClass(self::$altDomainName))->text();
		}
		return self::renderTemplate('responsive_top.mustache', $vars);
	}

	private function renderTemplate(string $template, array $vars): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);
		return $m->render($template, $vars);
	}

	public static function internationalWidget() {

		$vars = ['languages' => [], 'hp_lang_header' => wfMessage('hp_lang_header')->text()];
		$message = wfMessage('hp_languages')->text();
		$languages = explode("\n", $message);
		foreach($languages as $lang) {
			$vars['languages'][] = $lang;
		}

		return $vars;
	}

	public static function getnewsletterWidget(&$vars) {
		$vars['newsletter_url'] = wfMessage('newsletter_url')->text();
		$vars['hp_newsletter_header'] = wfMessage('hp_newsletter_header')->text();
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public function getFeaturedArticles(&$vars) {
		$faViewer = new FaViewer($this->getContext());
		$faViewer->doQuery();
		$rsViewer = new RsViewer($this->getContext());
		$rsViewer->doQuery();

		$vars['hp_featured_header'] = wfMessage("hp_featured_header")->text();
		$vars['featured_items'] = [];

		$rs = [];
		$count = 0;
		$dbr = wfGetDB(DB_REPLICA);
		$cutoffDate = wfTimestamp(TS_MW, strtotime("1 month ago"));
		foreach($rsViewer->articleTitles as $title) {
			//check first edit
			$fedate = $dbr->selectField('firstedit', 'fe_timestamp', ['fe_page' => $title->getArticleID()], __METHOD__);
			if($fedate < $cutoffDate) continue;

			$rs[] = [
				'url' => $title->getLocalURL(),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => self::getThumbnailUrl($title)] ),
				'isExpert' => self::isExpert($title->getArticleID())
			];
			$count++;
			if($count >= self::MAX_RS) break;
		}

		foreach($faViewer->articleTitles as $title) {
			$vars['featured_items'][] = [
				'url' => $title->getLocalURL(),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => self::getThumbnailUrl($title)] ),
				'isExpert' => self::isExpert($title->getArticleID())
			];
			$count++;
			if($count >= self::MAX_FEATURED) break;
		}

		$vars['featured_items'] = array_merge($vars['featured_items'], $rs);
	}

	public function getAltDomainArticles(&$vars) {
		$vars['hp_all_header'] = wfMessage("hp_all_header_" . AlternateDomain::getAlternateDomainClass(self::$altDomainName))->text();

		$faViewer = new FaViewer($this->getContext());
		$faViewer->doQuery();

		$count = 0;
		foreach($faViewer->articleTitles as $title) {
			$vars['all_items'][] = [
				'url' => $title->getLocalURL(),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => self::getThumbnailUrl($title)] ),
				'isExpert' => self::isExpert($id)
			];
			$count++;
			if($count >= self::MAX_ALTDOMAIN) break;
		}


	}

	public function getPopularArticles(&$vars) {
		$vars['hp_popular_header'] = wfMessage("hp_popular_header")->text();
		$vars['popular_items'] = [];

		$ids = ConfigStorage::dbGetConfig(self::POPULAR_LIST);
		$idArray = explode("\n", $ids);
		$count = 0;
		foreach($idArray as $id) {
			$title = Title::newFromID($id);
			if(!$title || !$title->exists()) {
				continue;
			}

			$vars['popular_items'][] = [
				'url' => $title->getLocalURL(),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => self::getThumbnailUrl($title)] ),
				'isExpert' => self::isExpert($id)
			];
			$count++;
			if($count >= self::MAX_EXPERT) break;
		}
	}

	public function getExpertArticles(&$vars) {
		global $wgMemc;

		$vars['hasExpert'] = true;
		$vars['hp_expert_header'] = wfMessage("hp_expert_header")->text();
		$vars['expert_items'] = [];

		$ids = ConfigStorage::dbGetConfig(self::EXPERT_LIST);
		$idArray = explode("\n", $ids);
		foreach($idArray as $id) {
			$title = Title::newFromID($id);
			if(!$title || !$title->exists()) {
				continue;
			}

			$youtubeId = WikihowMobileHomepage::getVideoId($id);
			if(is_null($youtubeId)) continue;

			$info = SchemaMarkup::getYouTubeVideo($title, $youtubeId);

			if ( empty( $info ) || $info === false  || !is_array($info['thumbnailUrl'])) continue;

			$thumb = $info['thumbnailUrl'][count($info['thumbnailUrl']) - 1];

			$vars['expert_items'][] = [
				'url' => $title->getLocalURL('#Video'),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => $thumb] ),
				'isVideo' => true,
				'needsCrop' => true //these videos are from youtube and the figure element to allow us to crop out the black bars
			];
		}

		if(count($vars['expert_items']) == 0) {
			$vars['hasExpert'] = false;
		}
	}

	public function getCoauthorArticles(&$vars) {
		$vars['has_coauthor'] = true;
		$vars['hp_coauthor_header'] = wfMessage("hp_coauthor_header")->text();
		$vars['coauthor_items'] = [];

		$ids = ConfigStorage::dbGetConfig(self::COAUTHOR_LIST);
		$idArray = explode("\n", $ids);
		$count = 0;
		foreach($idArray as $id) {
			$title = Title::newFromID($id);
			if(!$title || !$title->exists()) {
				continue;
			}

			$vars['coauthor_items'][] = [
				'url' => $title->getLocalURL(),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => self::getThumbnailUrl($title)] ),
				'isExpert' => self::isExpert($id)
			];
			$count++;
			if($count >= self::MAX_EXPERT) break;
		}
	}

	public function getWatchArticles(&$vars) {
		$vars['has_watch'] = true;
		$vars['hp_watch_header'] = wfMessage("hp_watch_header")->text();
		$vars['watch_items'] = [];

		$ids = ConfigStorage::dbGetConfig(self::WATCH_LIST);
		$idArray = explode("\n", $ids);
		$count = 0;
		foreach($idArray as $id) {
			$title = Title::newFromID($id);
			if (!$title || !$title->exists()) {
				continue;
			}

			$result = ApiSummaryVideos::query(['page' => $id]);
			if($result['videos'] && count($result['videos']) > 0) {
				$info = $result['videos'][0];

				if ( $info['clip'] !== '' ) {
					$src = $info['clip'];
					$prefix = 'https://www.wikihow.com/video';
					if ( substr( $src, 0, strlen( $prefix ) ) == $prefix ) {
						$src = substr( $src, strlen( $prefix ) );
					}
					$preview = Misc::getMediaScrollLoadHtml(
						'video', [ 'src' => $src, 'poster' => $info['poster'] ]
					);
				} else {
					$preview = Misc::getMediaScrollLoadHtml( 'img', [ 'src' => $info['poster'] ] );
				}

				$vars['watch_items'][] = [
					'url' => '/Video/' . str_replace( ' ', '-', $info['title'] ),
					'title' => $info['title'],
					'image' => $preview,
					'isVideo' => true
				];
				$count++;
				if($count >= self::MAX_WATCH) break;
			}
		}
	}

	private static function getThumbnailUrl($title) {
		$image = Wikitext::getTitleImage($title);
		if (!($image && $image->getPath() && strpos($image->getPath(), "?") === false)
			|| preg_match("@\.gif$@", $image->getPath())) {
			$image = Wikitext::getDefaultTitleImage($title);
		}

		$params = ['width' => self::THUMB_WIDTH, 'height' => self::THUMB_HEIGHT, 'crop' => 1, WatermarkSupport::NO_WATERMARK => true];
		$thumb = $image->transform($params);
		return $thumb->getUrl();
	}

	public static function categoryWidget($showWikihow = false) {
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

			if(strtolower($category) ==  "wikihow" && !$showWikihow) continue;

			$categories[] = [
				'icon' => CategoryListing::getCategoryIcon($category),
				'link' => $catTitle->getLocalURL(),
				'name' => $category
			];
		}

		$vars = [
			'hp_cat_header' => wfMessage('hp_cat_header')->text(),
			'categories' => $categories
		];

		return $vars;
	}

	private static function isExpert($articleId) {
		$verifierInfo = VerifyData::getByPageId($articleId);
		if(is_array($verifierInfo)) {
			foreach ($verifierInfo as $info) {
				if ($info->worksheetName == "expert") {
					return 1;
				}
			}
		}
	}

	/******************
	 * @param $articleId
	 * @return |null
	 * Gets the youtube id for the video linked in the wikitext.
	 * If no video exists, returns null
	 */
	public static function getVideoId($articleId) {
		$title = Title::newFromID($articleId);
		$videoSection = Wikitext::getVideoSection(Wikitext::getWikitext($title), false);
		preg_match('@^{{video:([^|}]*)@i', $videoSection[0], $m);

		if( count($m) < 2 || is_null($m[1]) ) return null;

		$title = Title::newFromText($m[1], NS_VIDEO);
		$revision = Revision::newFromTitle($title);

		preg_match_all('/{{Curatevideo\|whyoutube\|([^\|]*)\|/', ContentHandler::getContentText( $revision->getContent() ), $m);

		if(count($m) > 1) {
			return $m[1][0];
		} else {
			return null;
		}
	}

	/*******
	 * @param $key
	 * @param $config
	 * When the hp_expert config list is changed, make sure all yt videos have the info we
	 * need for them
	 */
	public static function onConfigStorageAfterStoreConfig($key, $config) {
		if($key == self::EXPERT_LIST) {
			$articleIds = explode("\n", $config);
			foreach($articleIds as $articleId) {
				$youtubeId = WikihowMobileHomepage::getVideoId($articleId);

				if(!is_null($youtubeId)) {
					//this call will grab it from the db and if it's not there, will start a job to do it
					SchemaMarkup::getYouTubeVideo(Title::newFromID($articleId), $youtubeId, true);
				}

			}
		}
	}

}
