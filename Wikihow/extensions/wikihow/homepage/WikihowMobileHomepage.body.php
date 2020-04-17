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
	const MAX_NEWPAGES = 6;

	static $isAltDomain = null;
	static $altDomainName;

	static $pageIdsOnHomepage = null;

	const THUMB_WIDTH = 375;
	const THUMB_HEIGHT = 250;

	var $languageCode;

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

		$this->languageCode = RequestContext::getMain()->getLanguage()->getCode();

		$vars = [
			'howto' => wfMessage('howto_prefix')->showIfExists(),
			'hp_trustworthy_cta' => wfMessage('hp_trustworthy_cta')->text(),
			'hp_trustworthy_header' => wfMessage('hp_trustworthy_header')->text(),
			'hp_trustworthy_text1' => wfMessage('hp_trustworthy_text1')->text(),
			'hp_trustworthy_text2' => wfMessage('hp_trustworthy_text2')->text(),
			'hp_trustworthy_text3' => wfMessage('hp_trustworthy_text3')->text(),
			'hp_news_header' => wfMessage('hp_news_header')->text(),
			'hp_news1_image' => wfMessage('hp_news1_image')->text(),
			'hp_news1_title' => wfMessage('hp_news1_title')->text(),
			'hp_news1_quote' => wfMessage('hp_news1_quote')->text(),
			'hp_news1_author' => wfMessage('hp_news1_author')->text(),
			'hp_news2_image' => wfMessage('hp_news2_image')->text(),
			'hp_news2_title' => wfMessage('hp_news2_title')->text(),
			'hp_news2_quote' => wfMessage('hp_news2_quote')->text(),
			'hp_news2_author' => wfMessage('hp_news2_author')->text(),
			'hp_news3_image' => wfMessage('hp_news3_image')->text(),
			'hp_news3_title' => wfMessage('hp_news3_title')->text(),
			'hp_news3_quote' => wfMessage('hp_news3_quote')->text(),
			'hp_news3_author' => wfMessage('hp_news3_author')->text(),
			'hp_news_read' => wfMessage('hp_news_read')->text(),
			'expertLabel' => ucwords(wfMessage('expert')->text())
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

		//new pages
		$this->getNewPages($vars);

		//special message
		$this->getSpecialMessage($vars);

		$this->getAltDomainArticles($vars);
		if(self::$isAltDomain && !in_array(self::$altDomainName, ['wikihow-fun.com', 'wikihow.life'])) {
			//$vars['showTrustworthy'] = true;
		}

		//newsletter
		$this->getNewsletterWidget($vars);

		//categories
		$vars = array_merge($vars, WikihowMobileHomepage::categoryWidget());

		//international
		$vars = array_merge($vars, WikihowMobileHomepage::internationalWidget());

		if($this->languageCode == "en") {
			// Amazon Ignite CTA messages
			$vars['has_education'] = true;
			$vars['amazon_ignite_header'] = wfMessage('hp_education_header')->text();
			$vars['amazon_ignite_header_desc'] = wfMessage('hp_education_header_desc')->text();
			$vars['amazon_ignite_desc'] = wfMessage('hp_education_desc')->text();
			$vars['amazon_ignite_btn'] = wfMessage('hp_education_button_text')->text();
			$vars['amazon_ignite_url'] = wfMessage('amazonignite_url')->text();

			// Cover Letter Course promo messages
			$vars['has_coverletter'] = true;
			$vars['coverletter_header_desc'] = wfMessage('hp_coverletter_header_desc')->text();
			$vars['coverletter_desc'] = wfMessage('hp_coverletter_desc')->text();
			$vars['coverletter_btn'] = wfMessage('hp_coverletter_button_text')->text();
			$vars['coverletterteachable_url'] = wfMessage('coverlettercourse_url')->text();
		}

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

	private function getSpecialMessage(&$vars) {
		if($this->languageCode == "en") {
			$mustache_vars = [
				'header' => wfMessage('hp_covid_header')->text(),
				'text' => wfMessage('hp_covid_text')->text(),
				'wh_covid_link' => Title::newFromText('COVID-19', NS_CATEGORY)->getLocalURL(),
				'wh_covid' => wfMessage('hp_covid_link_text')->text()
			];

			$vars['special_message'] = self::renderTemplate('special_message.mustache', $mustache_vars);
		}
	}

	public function getnewsletterWidget(&$vars) {
		if($this->languageCode == "en") {
			$vars['has_newsletter'] = true;
			$vars['newsletter_url'] = wfMessage('newsletter_url')->text();
			$vars['hp_newsletter_header'] = wfMessage('hp_newsletter_header')->text();
			$vars['hp_newsletter_subscribe'] = wfMessage('hp_newsletter_subscribe')->text();
			$vars['hp_newsletter_text'] = wfMessage('hp_newsletter_text')->text();
		}
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public function getNewPages(&$vars) {
		if(class_exists('NewPages')) {
			$newPages = NewPages::getHomepageArticles();

			if (count($newPages) > 0) {
				$vars['has_newpages'] = true;
				$vars['hp_newpages_header'] = wfMessage("hp_newpages_header")->text();
				$vars['newpages_items'] = [];
				$vars['seemore_newpages'] = wfMessage("seemore")->text() . " " . wfMessage("hp_newpages")->text();

				$count = 0;
				foreach ($newPages as $title) {
					if ($count >= self::MAX_NEWPAGES) break;

					$vars['newpages_items'][] = [
						'url' => $title->getLocalURL(),
						'title' => $title->getText(),
						'image' => Misc::getMediaScrollLoadHtml('img', ['src' => self::getThumbnailUrl($title)]),
						'isExpert' => VerifyData::isExpertVerified($title->getArticleID()),
					];
					$count++;
				}
			}
		}
	}

	public function getFeaturedArticles(&$vars) {
		$faViewer = new FaViewer($this->getContext());
		$faViewer->doQuery();
		$rsViewer = new RsViewer($this->getContext());
		$rsViewer->doQuery();

		$vars['hp_featured_header'] = wfMessage("hp_featured_header")->text();
		if($this->languageCode == "en") {
			$vars['showmore'] = true;
			$vars['showmoretext'] = wfMessage('Seemore')->text();
		}
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
				'isExpert' => VerifyData::isExpertVerified($title->getArticleID())
			];
			$count++;
			if($count >= self::MAX_RS) break;
		}

		foreach($faViewer->articleTitles as $title) {
			$vars['featured_items'][] = [
				'url' => $title->getLocalURL(),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => self::getThumbnailUrl($title)] ),
				'isExpert' => VerifyData::isExpertVerified($title->getArticleID())
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
				'isExpert' => VerifyData::isExpertVerified($title->getArticleID())
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

		if($ids !== false && $ids != "") {
			$vars['has_popular'] = true;
			$count = 0;
			foreach ($idArray as $id) {
				$title = Title::newFromID($id);
				if (!$title || !$title->exists()) {
					continue;
				}

				$vars['popular_items'][] = [
					'url' => $title->getLocalURL(),
					'title' => $title->getText(),
					'image' => Misc::getMediaScrollLoadHtml('img', ['src' => self::getThumbnailUrl($title)]),
					'isExpert' => VerifyData::isExpertVerified($id)
				];
				$count++;
				if ($count >= self::MAX_EXPERT) break;
			}
		}
	}

	public function getExpertArticles(&$vars) {
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
				'url' => $title->getLocalURL() . '#' . wfMessage('videoheader')->text(),
				'title' => $title->getText(),
				'image' => Misc::getMediaScrollLoadHtml( 'img', ['src' => $thumb] ),
				'isVideo' => true,
				'needsCrop' => true //these videos are from youtube and the figure element to allow us to crop out the black bars
			];
		}

		if(count($vars['expert_items']) > 0) {
			$vars['has_expert'] = true;
		}
	}

	public function getCoauthorArticles(&$vars) {
		$vars['hp_coauthor_header'] = wfMessage("hp_coauthor_header")->text();
		$vars['coauthor_items'] = [];
		$ids = ConfigStorage::dbGetConfig(self::COAUTHOR_LIST);
		$idArray = explode("\n", $ids);

		if($ids !== false && $ids != "") {
			$vars['has_coauthor'] = true;
			$count = 0;
			foreach ($idArray as $id) {
				$title = Title::newFromID($id);
				if (!$title || !$title->exists()) {
					continue;
				}

				$vars['coauthor_items'][] = [
					'url' => $title->getLocalURL(),
					'title' => $title->getText(),
					'image' => Misc::getMediaScrollLoadHtml('img', ['src' => self::getThumbnailUrl($title)]),
					'isExpert' => VerifyData::isExpertVerified($id)
				];
				$count++;
				if ($count >= self::MAX_EXPERT) break;
			}
		}
	}

	public function getWatchArticles(&$vars) {
		$vars['hp_watch_header'] = wfMessage("hp_watch_header")->text();
		$vars['watch_items'] = [];

		$ids = ConfigStorage::dbGetConfig(self::WATCH_LIST);
		$idArray = explode("\n", $ids);

		if($ids !== false && $ids != "") {
			$vars['has_watch'] = true;
			$count = 0;
			foreach ($idArray as $id) {
				$title = Title::newFromID($id);
				if (!$title || !$title->exists()) {
					continue;
				}

				if($this->languageCode == "en") {
					//get the anchor for this video. That will tell us which kind of
					//video it is, and how to get the right thumbnail
					$anchor = WHVid::getVideoAnchorForLoggedOut($title);
					if($anchor == '') continue;

					if($anchor == wfMessage("Videoheader")->text()) {
						//get a normal thumbnail for the article
						$vars['watch_items'][] = [
							'url' => $title->getLocalURL() . "#" . $anchor,
							'title' => $title->getText(),
							'image' => Misc::getMediaScrollLoadHtml('img', ['src' => self::getThumbnailUrl($title)]),
							'isVideo' => true,
							'isExpert' => VerifyData::isExpertVerified($id)
						];

						$count++;
						if ($count >= self::MAX_WATCH) break;
					} else {
						//has a summary video, so get the thubmnail
						$result = ApiSummaryVideos::query(['page' => $id]);
						if ($result['videos'] && count($result['videos']) > 0) {
							$info = $result['videos'][0];

							if ($info['clip'] !== '') {
								$src = $info['clip'];
								$prefix = 'https://www.wikihow.com/video';
								if (substr($src, 0, strlen($prefix)) == $prefix) {
									$src = substr($src, strlen($prefix));
								}
								$preview = Misc::getMediaScrollLoadHtml(
									'video', ['src' => $src, 'poster' => $info['poster']]
								);
							} else {
								$preview = Misc::getMediaScrollLoadHtml('img', ['src' => $info['poster']]);
							}

							$vars['watch_items'][] = [
								'url' => $title->getLocalURL("#" . $anchor),
								'title' => $info['title'],
								'image' => $preview,
								'isVideo' => true
							];
							$count++;
							if ($count >= self::MAX_WATCH) break;
						}
					}
				} else {
					$vars['watch_items'][] = [
						'url' => $title->getLocalURL() . "#" . wfMessage("Videoheader")->text(),
						'title' => $title->getText(),
						'image' => Misc::getMediaScrollLoadHtml('img', ['src' => self::getThumbnailUrl($title)]),
						'isExpert' => VerifyData::isExpertVerified($id)
					];

					$count++;
					if ($count >= self::MAX_WATCH) break;
				}
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

	public static function isPageIdOnHomepage($id) {
		$ids = self::getPageIdsOnHomepage();

		return in_array($id, $ids);
	}
	public static function getPageIdsOnHomepage() {
		if(is_null(self::$pageIdsOnHomepage)) {
			self::$pageIdsOnHomepage = [];

			//coauthor
			$ids = ConfigStorage::dbGetConfig(self::COAUTHOR_LIST);
			$idArray = explode("\n", $ids);
			if($ids !== false && $ids != "") {
				$count = 0;
				foreach ($idArray as $id) {
					$title = Title::newFromID($id);
					if (!$title || !$title->exists()) {
						continue;
					}

					self::$pageIdsOnHomepage[] = $id;
					$count++;
					if ($count >= self::MAX_EXPERT) break;
				}
			}

			//popular
			$ids = ConfigStorage::dbGetConfig(self::POPULAR_LIST);
			$idArray = explode("\n", $ids);
			if($ids !== false && $ids != "") {
				$count = 0;
				foreach ($idArray as $id) {
					$title = Title::newFromID($id);
					if (!$title || !$title->exists()) {
						continue;
					}

					self::$pageIdsOnHomepage[] = $id;
					$count++;
					if ($count >= self::MAX_EXPERT) break;
				}
			}

			//watch
			$ids = ConfigStorage::dbGetConfig(self::WATCH_LIST);
			$idArray = explode("\n", $ids);
			if($ids !== false && $ids != "") {
				$count = 0;
				foreach ($idArray as $id) {
					$title = Title::newFromID($id);
					if (!$title || !$title->exists()) {
						continue;
					}

					self::$pageIdsOnHomepage[] = $id;
					$count++;
					if ($count >= self::MAX_WATCH) break;
				}
			}

			//expert
			$ids = ConfigStorage::dbGetConfig(self::EXPERT_LIST);
			$idArray = explode("\n", $ids);
			if($ids !== false && $ids != "") {
				$count = 0;
				foreach ($idArray as $id) {
					$title = Title::newFromID($id);
					if (!$title || !$title->exists()) {
						continue;
					}

					self::$pageIdsOnHomepage[] = $id;
					$count++;
					if ($count >= self::EXPERT_LIST) break;
				}
			}

			//new pages
			$titleArray = class_exists('NewPages') ? NewPages::getHomepageArticles() : [];
			if(count($titleArray) > 0) {
				$count = 0;
				foreach ($titleArray as $title) {
					if (!$title || !$title->exists()) {
						continue;
					}

					self::$pageIdsOnHomepage[] = $title->getArticleID();
					$count++;
					if ($count >= self::MAX_NEWPAGES) break;
				}
			}

			//featured
			$context = RequestContext::getMain();
			$faViewer = new FaViewer($context);
			$faViewer->doQuery();
			$rsViewer = new RsViewer($context);
			$rsViewer->doQuery();

			$count = 0;
			$dbr = wfGetDB(DB_REPLICA);
			$cutoffDate = wfTimestamp(TS_MW, strtotime("1 month ago"));
			foreach($rsViewer->articleTitles as $title) {
				//check first edit
				$fedate = $dbr->selectField('firstedit', 'fe_timestamp', ['fe_page' => $title->getArticleID()], __METHOD__);
				if($fedate < $cutoffDate) continue;

				self::$pageIdsOnHomepage[] = $title->getArticleID();

				$count++;
				if($count >= self::MAX_RS) break;
			}

			foreach($faViewer->articleTitles as $title) {
				self::$pageIdsOnHomepage[] = $title->getArticleID();

				$count++;
				if($count >= self::MAX_FEATURED) break;
			}
		}

		return self::$pageIdsOnHomepage;
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
