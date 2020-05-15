<?php

class MobileWikihowCategoryPage extends CategoryPage {

	const PULL_CHUNKS = 96;
	const SMALL_PULL_CHUNKS = 12;
	const ONE_ROW_CHUNKS = 6;
	const MAX_FA = 48;
	const SINGLE_WIDTH = 375;
	const SINGLE_HEIGHT = 321;
	const WATCH_LIST = 'covid19_category_videos';
	const MAX_WATCH = 12;
	const THUMB_WIDTH = 375;
	const THUMB_HEIGHT = 250;
	const MAX_NEW_PAGES = 12;

	public function view() {
		global $wgHooks;
		$wgHooks['UseMobileRightRail'][] = ['MobileWikihowCategoryPage::removeSideBarCallback'];
		$wgHooks['MobilePreRenderPreContent'][] = ['MobileWikihowCategoryPage::addPageNavigation'];

		if (Misc::isAltDomain()) {
			Misc::exitWith404();
		}

		$ctx = $this->getContext();
		$isLoggedIn = $ctx->getUser()->isLoggedIn();
		$req = $ctx->getRequest();
		$out = $ctx->getOutput();
		$categoryTitle = $ctx->getTitle();

		if (!$categoryTitle->exists()) {
			self::show404Page($out, $categoryTitle->getText());
			return;
		}

		if ($req->getVal('diff') > 0) {
			return Article::view();
		}

		$categoryName = $categoryTitle->getText();
		if ($categoryName == 'COVID 19') $categoryName = 'COVID-19';

		$out->setRobotPolicy('index,follow', 'Category Page');
		// allow redirections to mobile domain
		Misc::setHeaderMobileFriendly();
		$out->setPageTitle($categoryName);

		if ($req->getVal('viewMode',0)) {
			//this is for the text view
			$sortDirection =  $req->getVal('rev', 'ASC');
			$oppSortDirection = ($sortDirection == 'ASC') ? "DESC" : "ASC";
			$viewer = new WikihowCategoryViewer($this->mTitle, $this->getContext(), true, $sortDirection);
			$viewer->clearState();
			$viewer->doQuery();
			$vars = [
				'reverseURL' => $categoryTitle->getLocalURL('viewMode=text&rev=' . $oppSortDirection),
				'categoryName' => $categoryName,
				'reverse_order' => wfMessage('cat_reverse_order')->text(),
				'articles' => []
			];
			$articles = $viewer->articles;
			foreach ($articles as $title) {
				$vars['articles'][] = ['articleLink' => Linker::link($title)];
			}

			$html = self::renderTemplate('responsive_textonly.mustache', $vars);
			$out->addHTML($html);

			$out->addModuleStyles(['mobile.wikihow.mobile_category_page_styles']);
		}
		else {
			//don't have a ?pg=1 page
			if ($req->getInt('pg') == 1) $out->redirect($categoryTitle->getFullURL());

			//get pg and start info
			$pg = $req->getInt('pg',1);

			$covidPage = $categoryName == 'COVID-19';

			$topCats = CategoryHelper::getTopLevelCategoriesForDropDown();
			$isTopCat = in_array($categoryName, $topCats);

			$vars = [];
			$vars['description'] = AdminCategoryDescriptions::getCategoryDescription($this->mTitle);
			$vars['catName'] = $categoryName;
			$vars['featuredHeader'] = wfMessage("cat_featured")->text();
			$vars['topicsHeader'] = wfMessage("cat_topics")->text();
			$vars['cat_more'] = wfMessage("cat_more")->text();
			if($ctx->getUser()->isLoggedIn()) {
				$vars['default_expanded_class'] = 'expanded';
				$vars['text_only_url'] = $categoryTitle->getLocalURL('viewMode=text');
				$vars['text_only_text'] = wfMessage('text_view')->text();
			}
			if($categoryName == "Featured Articles") {
				$vars['allArticlesHeader'] = $categoryName;
			} else {
				$vars['allArticlesHeader'] = wfMessage("cat_toplevel_all", $categoryName)->text();
			}

			$viewer = new WikihowCategoryViewer($this->mTitle, $this->getContext());

			$fas = $viewer->getFAs(); //we still do this call even if we don't want FA section on this page b/c it initializes the article viewer object

			//First get the featured articles, but only if not on the featured article category page
			if ($this->mTitle->getLocalUrl() !== wfMessage('Featuredarticles_url')->text()) {
				$featuredImages = [];
				$i = 0;
				if (count($fas) >= 4) {
					foreach ($fas as $fa) {
						if( !$isLoggedIn && AlternateDomain::getAlternateDomainForPage($fa->getArticleID()) )
							continue;
						$info = $this->getArticleThumbWithPathFromTitle($fa);
						if ($info) {
							$featuredImages[] = $info;
						}
						if (++$i >= self::MAX_FA) {
							break;
						}
					}

					$vars['hasFeatured'] = true;
					$vars['featured'] = $featuredImages;
				}
			}

			//we need to do subcats first b/c it determines whether there is a sidebar or not.
			$subcatsArray = [];
			$topLevelSubcats = [];
			$total = 0;
			if (count($viewer->children) > 0) {
				foreach ($viewer->children as $subcats) {
					if ($subcats instanceof Title) {
						if ($subcats->getArticleID() != $categoryTitle->getArticleID()) {
							$subcatsArray[] = ['categoryLink' => Linker::link($subcats, $subcats->getText(), ['class' => 'cat_link'])];
							$topLevelSubcats[] = $subcats;
							$total++;
						}
					}
					elseif (count($subcats) == 1) {
						if ($subcats[0] instanceof Title) {
							$subcatsArray[] = ['categoryLink' => Linker::link($subcats[0], $subcats[0]->getText(), ['class' => 'cat_link'])];
							$topLevelSubcats[] = $subcats[0];
							$total++;
						}
					} elseif (count($subcats) == 2) {
						$subsubcatsArray = [];
						if (is_array($subcats[1])) {
							foreach ($subcats[1] as $t) {
								$subsubcatsArray[] = ['categoryLink' => Linker::link($t, $t->getText(), ['class' => 'cat_link'])];
							}
							$total += count($subsubcatsArray);
						}
						$subcatsArray[] = ['categoryLink' => Linker::link($subcats[0], $subcats[0]->getText(), ['class' => 'cat_link']), 'hasSubsubcats' => true, 'subsubcats' => $subsubcatsArray];
						$topLevelSubcats[] = $subcats[0];
						$total++;
					}
				}
				if (count($subcatsArray) > 0) {
					$vars['hasSubcats'] = true;

					$len = count($subcatsArray);

					$vars['subcats1'] = array_slice($subcatsArray, 0, ceil($len / 2));
					$vars['subcats2'] = array_slice($subcatsArray, ceil( $len / 2 ));
				}
			}

			if (count($subcatsArray) > 0) {
				$articlesPerPage = self::PULL_CHUNKS;
			} else {
				$articlesPerPage = $covidPage ? self::ONE_ROW_CHUNKS*4 : ceil(self::PULL_CHUNKS/4)*4;
			}

			//now just regular articles
			$start = ($pg > 0) ? (($pg - 1) * $articlesPerPage) : 0;

			//don't need to doQuery b/c it was done earlier in the getFA call
			$articles = $viewer->articles;
			$count = 0;
			$allArticles = [];
			for ($i = $start; $i < count($articles) && $i < ($start + $articlesPerPage); $i++){
				if( !$isLoggedIn && AlternateDomain::getAlternateDomainForPage($articles[$i]->getArticleID()) )
					continue;
				$info = $this->getArticleThumbWithPathFromTitle($articles[$i]);
				if ($info) {
					$allArticles[] = $info;
					$count++;
				}
			}

			if ($pg == 1 && $isTopCat && count($allArticles) < $articlesPerPage) {
				//if we're in a top level category and there isn't a full page, fill it!
				$pageIds = TopCategoryData::getPagesForCategory($categoryTitle->getDBkey(), TopCategoryData::HIGHTRAFFIC, ($articlesPerPage - count($allArticles)));
				foreach ($pageIds as $pageId) {
					$addedTitle = Title::newFromID($pageId);
					if ($addedTitle && RobotPolicy::isIndexable($addedTitle)) {
						if( !$isLoggedIn && AlternateDomain::getAlternateDomainForPage($pageId) )
							continue;
						$info = $this->getArticleThumbWithPathFromTitle($addedTitle);
						if ($info) {
							if($count >= self::SMALL_PULL_CHUNKS) {
								$info['classes'] .= " small_extra";
							}
							$allArticles[] = $info;
							$count++;
						}
					}
				}
			}

			if (count($articles) > $articlesPerPage) {
				$vars['pagination'] = $this->getPaginationHTML($pg, count($articles), $articlesPerPage);
			}
			$vars['all'] = $allArticles;

			$showFurtherEditing = $isLoggedIn && !$covidPage;

			if ($showFurtherEditing) {
				$furtherEditing = $viewer->getArticlesFurtherEditing($viewer->articles, $viewer->article_info);
				if ($furtherEditing != "") {
					$vars['furtherEditing'] = $furtherEditing;
				}
			}

			if ($pg == 1) {
				$this->getVideoArticles($vars);
				if($isTopCat) {
					$this->getNewPages($vars, $categoryName);
				}
			}

			$vars['covid_section'] = $this->covidSection();

			$html = self::renderTemplate("responsive_category_page.mustache", $vars);
			if (count($allArticles) == 0) {
				//nothin' in this category
				self::show404Page($out, $categoryName);
				return;
			} else {
				$out->addModuleStyles(['mobile.wikihow.mobile_category_page_styles']);
				$out->addModules('mobile.wikihow.mobile_category_page');
				$out->addHTML($html);
			}
		}
	}

	public static function show404Page( $out, $categoryName ) {
		$out->setStatusCode(404);
		$out->addModuleStyles(['mobile.wikihow.mobile_category_page_styles']);
		$out->addHTML( self::renderTemplate('responsive_no_results.mustache',
			[
				'title' => $categoryName,
				'special_message' => wfMessage( 'Noarticletextanon' )->parse(),
				'search_header' => wfMessage( 'pagepolicy_search_header' )->text(),
				'searchbox' => SearchBox::render( $out ),
				'cat_not_exists' => wfMessage('cat_not_exists')->text()
			]
		) );
	}

	private function getArticleThumbWithPathFromTitle(Title $title) {
		global $wgContLang, $wgLanguageCode;

		if (!$title) return null;

		$width = SELF::SINGLE_WIDTH;
		$height = SELF::SINGLE_HEIGHT;

		$image = Wikitext::getTitleImage($title);

		// Make sure there aren't any issues with the image.
		//Filenames with question mark characters seem to cause some problems
		// Animatd gifs also cause problems.  Just use the default image if image is a gif
		if (!($image && $image->getPath() && strpos($image->getPath(), "?") === false)
			|| preg_match("@\.gif$@", $image->getPath())) {
			$image = Wikitext::getDefaultTitleImage($title);
		}

		$sourceWidth = $image->getWidth();
		$sourceHeight = $image->getHeight();
		$xScale = ($sourceWidth == 0) ? $xScale = 1 : $width/$sourceWidth;
		if ( $height > $xScale*$sourceHeight ) {
			$heightPreference = true;
		} else {
			$heightPreference = false;
		}
		$thumb = WatermarkSupport::getUnwatermarkedThumbnail($image, $width, $height, true, true, $heightPreference);
		$thumbSrc = wfGetPad( $thumb->getUrl() );

		//removed the fixed width for now
		$articleName = $title->getText();
		if ($wgLanguageCode == "zh") {
			$articleName = $wgContLang->convert($articleName);
		}

		// Show how-to message for main namespace articles
		// but prefixed title with no lead for other namespaces

		if ( $title->inNamespace( NS_MAIN ) ) {
			$msg = wfMessage('howto_prefix');
			$howToPrefix = $msg->exists() ? $msg->text() : '';
			$howToSuffix = wfMessage('howto_suffix')->showIfExists();
			$textBlock = "{$howToPrefix}<span>{$articleName}{$howToSuffix}</span>";
		} else {
			$textBlock = "<br/><span>" . $title->getFullText() . "</span>";
		}

		$image = Misc::getMediaScrollLoadHtml( 'img', ['src' => $thumbSrc] );

		return [
			//'classes' => implode( ' ', $thumbnailClasses ),
			'url' => $title->getFullUrl(),
			'data-src' => $thumbSrc,
			'textBlock' => $textBlock,
			'title' => $articleName,
			'howto' => $howToPrefix,
			'image' => $image,
			'isExpert' => VerifyData::isExpertVerified($title->getArticleID()),
			'expertLabel' => ucwords(wfMessage('expert')->text())
		];
	}

	public function isFileCacheable($mode = HTMLFileCache::MODE_NORMAL) {
		return true;
	}

	public static function onArticleFromTitle(&$title, &$page) {
		switch ($title->getNamespace()) {
			case NS_CATEGORY:
				$page = new MobileWikihowCategoryPage($title);
		}
		return true;
	}

	private function getPaginationHTML($pg, $total, $articlesPerPage) {
		global $wgCanonicalServer;

		$ctx = $this->getContext();
		$out = $ctx->getOutput();

		// Dalek: "CAL-CU-LATE!!!"
		$here = str_replace(' ','-','/'.$this->getContext()->getTitle()->getPrefixedText());
		$numOfPages = ($articlesPerPage > 0) ? ceil($total / $articlesPerPage) : 0;

		// prev & next links
		if ($pg > 1) {
			$prev_page = ($pg == 2) ? '' : '?pg='.($pg-1);
			$prev = '<a rel="prev" href="'.$here.$prev_page.'" class="button buttonleft primary pag_prev">'.wfMessage('cat_previous')->escaped().'</a>';
		}
		else {
			$prev = '<a class="button buttonleft primary pag_prev disabled">'.wfMessage('cat_previous')->escaped().'</a>';
		}
		if ($pg < $numOfPages) {
			$next = '<a rel="next" href="'.$here.'?pg='.($pg+1).'" class="button buttonright pag_next primary">'.wfMessage('cat_next')->escaped().'</a>';
		}
		else {
			$next = '<a class="button buttonright pag_next primary disabled">'.wfMessage('cat_next')->escaped().'</a>';
		}
		$html = $prev.$next;

		// set <head> links for SEO
		if ($pg > 1) $out->setCanonicalUrl($out->getTitle()->getFullURL().'?pg='.$pg);
		if ($pg == 2) {
			$out->addHeadItem('prev_pagination','<link rel="prev" href="'.$wgCanonicalServer.$here.'" />');
		}
		elseif ($pg > 2) {
			$out->addHeadItem('prev_pagination','<link rel="prev" href="'.$wgCanonicalServer.$here.'?pg='.($pg-1).'" />');
		}
		if ($pg < $numOfPages) $out->addHeadItem('next_pagination','<link rel="next" href="'.$wgCanonicalServer.$here.'?pg='.($pg+1).'" />');

		$html .= '<ul class="pagination">';
		for ($i=1; $i<=$numOfPages; $i++) {
			if ($i == ($pg-1)) {
				$rel = 'rel="prev"';
			}
			elseif ($i == ($pg+1)) {
				$rel = 'rel="next"';
			}
			else {
				$rel = '';
			}

			if ($pg == $i) {
				$html .= '<li>'.$i.'</li>';
			}
			elseif ($i == 1) {
				$html .= '<li><a '.$rel.' href="'.$here.'">'.$i.'</a></li>';
			}
			else {
				$html .= '<li><a '.$rel.' href="'.$here.'?pg='.$i.'">'.$i.'</a></li>';
			}
		}
		$html .= '</ul>';
		return $html;
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public static function addPageNavigation(&$data) {
		$ctx = RequestContext::getMain();
		$categoryTitle = $ctx->getTitle();
		$categoryName = $categoryTitle->getText();
		$vars['catName'] = $categoryName;

		$parent = array_pop(array_keys($categoryTitle->getParentCategories()));
		$t = Title::newFromText($parent, NS_CATEGORY);

		$vars['cat_parent_url'] = $t ? $t->getLocalUrl() : SpecialPage::getTitleFor('CategoryListing')->getLocalURL();

		$html = self::renderTemplate("responsive_navigation.mustache", $vars);
		$data['prebodytext'] = $html;
	}

	private function getVideoArticles(&$vars) {
		//only for COVID-19 currently
		$dbkey = $this->getTitle()->getDBkey();
		if ($dbkey != 'COVID-19') return;

		$vars['videoHeader'] = wfMessage('cat_videos', $dbkey)->text();
		$this->getWatchArticles($vars);
	}

	private function getNewPages(&$vars, $categoryName) {
		$pageIds = NewPages::getCategoryPageArticles($categoryName);

		if(count($pageIds) == 0) return;

		$vars['hasNewpages'] = true;
		$vars['newpagesHeader'] = wfMessage('cat_newpages_header', $categoryName);
		foreach ($pageIds as $id) {
			$title = Title::newFromID($id);
			if(!$title || !$title->exists()) continue;

			$vars['newpages'][] = $this->getArticleThumbWithPathFromTitle($title);

			if(count($vars['newpages']) >= self::MAX_NEW_PAGES) break;
		}
	}

	private function covidSection(): string {
		if ($this->getTitle()->getDBkey() != 'COVID-19' ||
			$this->getTitle()->getPageLanguage()->getCode() != 'en') return '';

		$vars = [
			'header' => wfMessage('cat_covid_msg_header')->text(),
			'subheader' => wfMessage('cat_covid_msg_subheader')->text(),
			'text' => wfMessage('cat_covid_msg_text')->text(),
			'learn_more' => wfMessage('cat_covid_msg_more')->text(),
			'learn_more_link' => Title::newFromText(wfMessage('corona-guide')->text(), NS_PROJECT)->getLocalURL()
		];

		return self::renderTemplate('category_covid_message.mustache', $vars);
	}

	private static function renderTemplate( string $template, array $vars = [] ): string {
		$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
		$options = array('loader' => $loader);

		$m = new Mustache_Engine($options);
		return $m->render( $template, $vars );
	}

	public function getWatchArticles(&$vars) {
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

				if ($this->getContext()->getLanguage()->getCode() == "en") {
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
							'url' => $title->getLocalURL() . '#' . wfMessage('videoheader')->text(),
							'title' => $info['title'],
							'image' => $preview,
							'howto' => wfMessage('howto_prefix')->showIfExists(),
							'isVideo' => true
						];
						$count++;
						if ($count >= self::MAX_WATCH) break;
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

}
