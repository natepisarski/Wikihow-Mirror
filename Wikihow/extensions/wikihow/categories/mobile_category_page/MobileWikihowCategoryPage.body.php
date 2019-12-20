<?php

class MobileWikihowCategoryPage extends CategoryPage {

	const PULL_CHUNKS = 72;
	const SMALL_PULL_CHUNKS = 12;
	const MAX_FA = 48;
	const SINGLE_WIDTH = 375;
	const SINGLE_HEIGHT = 321;

	public function view() {
		global $wgHooks;
		$wgHooks['UseMobileRightRail'][] = ['MobileWikihowCategoryPage::removeSideBarCallback'];
		$wgHooks['MobilePreRenderPreContent'][] = ['MobileWikihowCategoryPage::addPageNavigation'];

		if (Misc::isAltDomain()) {
			Misc::exitWith404();
		}

		$ctx = $this->getContext();
		$req = $ctx->getRequest();
		$out = $ctx->getOutput();
		$categoryTitle = $ctx->getTitle();
		$categoryName = $categoryTitle->getText();

		if (!$categoryTitle->exists()) {
			parent::view();
			return;
		}

		if ($req->getVal('diff') > 0) {
			return Article::view();
		}

		$out->setRobotPolicy('index,follow', 'Category Page');
		// allow redirections to mobile domain
		Misc::setHeaderMobileFriendly();
		$out->setPageTitle($categoryTitle->getText());
		if ($req->getVal('viewMode',0)) {
			//this is for the text view
			$viewer = new WikihowCategoryViewer( $this->mTitle, $this->getContext(), true);
			$viewer->clearState();
			$viewer->doQuery();
			$out->addHtml('<div class="section minor_section">');
			$out->addHtml('<ul>');
			$articles = $viewer->articles;
			foreach ($articles as $title) {
				$out->addHtml( "<li>" . Linker::link($title) . "</li>");
			}
			$out->addHtml('</ul>');
			$out->addHtml('</div>');
		}
		else {
			//don't have a ?pg=1 page
			if ($req->getInt('pg') == 1) $out->redirect($categoryTitle->getFullURL());

			//get pg and start info
			$pg = $req->getInt('pg',1);

			$topCats = CategoryHelper::getTopLevelCategoriesForDropDown();
			$isTopCat = in_array($categoryName, $topCats);

			$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
			$options = array('loader' => $loader);

			$m = new Mustache_Engine($options);
			$vars = [];
			$vars['description'] = AdminCategoryDescriptions::getCategoryDescription($this->mTitle);
			$vars['catName'] = $categoryName;
			$vars['featuredHeader'] = wfMessage("cat_featured")->text();
			$vars['topicsHeader'] = wfMessage("cat_topics")->text();
			$vars['cat_more'] = wfMessage("cat_more")->text();
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
				$articlesPerPage = ceil(self::PULL_CHUNKS/4)*4;
			}

			//now just regular articles
			$start = ($pg > 0) ? (($pg - 1) * $articlesPerPage) : 0;

			//don't need to doQuery b/c it was done earlier in the getFA call
			$articles = $viewer->articles;
			$count = 0;
			$allArticles = [];
			for ($i = $start; $i < count($articles) && $i < ($start + $articlesPerPage); $i++){
				$info = $this->getArticleThumbWithPathFromTitle($articles[$i]);
				if ($info) {
					if($count >= self::SMALL_PULL_CHUNKS) {
						//$info['classes'] .= " small_extra";
					}
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

			//Now the related section (which only shows if the "all articles" section isn't full AND there isn't a featured section
			if (!isset($vars['pagination']) && $vars['hasFeatured'] != true) {
				if ($isTopCat) {
					$topCat = $categoryTitle->getDBkey();
					$topCatText = $categoryTitle->getText();
				} else {
					$topCat = CategoryHelper::getTopCategory($categoryTitle);
					if ($topCat) {
						$topCatText = Title::newFromDBkey($topCat, NS_CATEGORY)->getText();
					} else {
						$topCatText = "";
					}
				}

				$pageIds = TopCategoryData::getPagesForCategory($topCat, TopCategoryData::FEATURED, 12);
				$featuredArticles = [];
				foreach ($pageIds as $pageId) {
					$addedTitle = Title::newFromID($pageId);
					if ($addedTitle) {
						$info = $this->getArticleThumbWithPathFromTitle($addedTitle);
						if ($info) {
							$featuredArticles[] = $info;
						}
					}
				}

				if (count($featuredArticles)) {
					$vars['hasRelated'] = true;
					$vars['topCat'] = $topCatText;
					$vars['related'] = $featuredArticles;
				}
			}

			if ($ctx->getUser()->isLoggedIn()) {
				$furtherEditing = $viewer->getArticlesFurtherEditing($viewer->articles, $viewer->article_info);
				if ($furtherEditing != "") {
					$vars['furtherEditing'] = $furtherEditing;
				}
			}

			$html = $m->render("responsive_category_page.mustache", $vars);

			if (count($allArticles) == 0) {
				//nothin' in this category
				$out->setStatusCode(404);
				return;
			} else {
				$out->addModuleStyles(['mobile.wikihow.mobile_category_page_styles']);
				$out->addModules('mobile.wikihow.mobile_category_page');
				$out->addHTML($html);
			}
		}
	}

	private function getArticleThumbWithPathFromUrl($link){

		if (preg_match('@title="([^"]+)"@', $link, $matches)) {
			$title = Title::newFromText($matches[1]);
			if ($title) {
				return $this::getArticleThumbWithPathFromTitle($title);

			} else {
				return null;
			}
		} else {
			return null;
		}

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

		return [
			//'classes' => implode( ' ', $thumbnailClasses ),
			'url' => $title->getFullUrl(),
			'data-src' => $thumbSrc,
			'textBlock' => $textBlock,
			'title' => $articleName,
			'howto' => $howToPrefix
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
		if ($pg > 1) $out->setRobotPolicy('noindex');
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

		$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
		$options = array('loader' => $loader);

		$m = new Mustache_Engine($options);
		$html = $m->render("responsive_navigation.mustache", $vars);
		$data['prebodytext'] = $html;
	}

}
