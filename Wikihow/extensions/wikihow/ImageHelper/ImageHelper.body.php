<?php

class ImageHelper extends UnlistedSpecialPage {

	const IMAGES_ON = true;

	public function __construct() {
		parent::__construct( 'ImageHelper' );
	}

	// Used by getConnectedImages
	private static function heightPreference($desiredWidth, $desiredHeight, &$file) {
		$heightPreference = false;
		if ($file) {
			$sourceWidth = $file->getWidth();
			$sourceHeight = $file->getHeight();
			if ($desiredHeight > 0 && $sourceHeight > 0 &&
				$desiredWidth/$desiredHeight < $sourceWidth/$sourceHeight
			) {
				//desired image is portrait
				$heightPreference = true;
			}
		}
		return $heightPreference;
	}

	public static function getRelatedWikiHows($title) {
		global $wgOut;

		$articles = self::getLinkedArticles($title);
		$relatedArticles = array();
		foreach ($articles as $t) {
			$related = self::setRelatedWikiHows($t);
			foreach ($related as $titleString) {
				$relatedArticles[$titleString] = $titleString;
			}
		}

		$section = '';

		$count = 0;
		$images = '';
		foreach ($relatedArticles as $titleString) {
			$t = Title::newFromText($titleString);
			if ($t && $t->exists()) {
				$result = self::getArticleThumb($t, 127, 140);
				$images .= $result;

				if (++$count == 4) break;
			}
		}

		if ($count > 0) {
			$section .= "<div class='other_articles minor_section'>
						<h2>" . wfMessage('ih_relatedArticles') . "</h2>
						$images
						<div class='clearall'></div>
						</div>";
		}

		$wgOut->addHTML($section);
	}

	// copied from WikihowSkinHelper::getRelatedArticlesBox originally
	private static function setRelatedWikiHows($title) {
		global $wgTitle, $wgParser, $wgMemc;

		$key = wfMemcKey("ImageHelper_related", $title->getArticleID());
		$result = $wgMemc->get($key);
		if ($result) {
			return $result;
		}

		$templates = wfMessage('ih_categories_ignore')->inContentLanguage()->text();
		$templates = explode("\n", $templates);
		$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
		$templates = array_flip($templates); // make the array associative.

		$r = Revision::newFromTitle($title);
		$relatedTitles = array();
		if ($r) {
			$text = ContentHandler::getContentText( $r->getContent() );
			$whow = WikihowArticleEditor::newFromText($text);
			$related = preg_replace("@^==.*@m", "", $whow->getSection('related wikihows'));

			if ($related != "") {
				$preg = "/\\|[^\\]]*/";
				$related = preg_replace($preg, "", $related);
				$rarray = explode("\n", $related);
				foreach ($rarray as $related) {
					preg_match("/\[\[(.*)\]\]/", $related, $rmatch);

					//check to make sure this article isn't in a category
					//that we don't want to show
					$title = Title::MakeTitle( NS_MAIN, $rmatch[1] );
					$cats = ($title->getParentCategories());
					if (is_array($cats) && sizeof($cats) > 0) {
						$keys = array_keys($cats);
						$found = false;
						for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
							$t = Title::newFromText($keys[$i]);
							if (isset($templates[urldecode($t->getPartialURL())]) ) {
								//this article is in a category we don't want to show
								$found = true;
								break;
							}
						}
						if ($found) continue;
					}

					$relatedTitles[] = $rmatch[1];
				}

			} else {
				$cats = $title->getParentCategories();
				$cat1 = '';
				if (is_array($cats) && sizeof($cats) > 0) {
					$keys = array_keys($cats);
					$cat1 = '';
					$found = false;
					$templates = wfMessage('ih_categories_ignore')->inContentLanguage()->text();
					$templates = explode("\n", $templates);
					$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
					$templates = array_flip($templates); // make the array associative.
					for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
						$t = Title::newFromText($keys[$i]);
						if (isset($templates[urldecode($t->getPartialURL())]) ) {
							continue;
						}
						$cat1 = $t->getDBKey();
						$found = true;
						break;
					}
				}
				if ($cat1) {
					$dbr = wfGetDB( DB_REPLICA );
					$num = (int)wfMessage('num_related_articles_to_display')->inContentLanguage()->text();
					$res = $dbr->select('categorylinks', 'cl_from', array ('cl_to' => $cat1),
						__METHOD__,
						array ('ORDER BY' => 'rand()', 'LIMIT' => $num*2));

					$count = 0;
					foreach ($res as $row) {
						if ($count >= $num) {
							break;
						}

						if ($row->cl_from == $title->getArticleID()) {
							continue;
						}
						$t = Title::newFromID($row->cl_from);
						if (!$t) {
							continue;
						}
						if (!$t->inNamespace(NS_MAIN)) {
							continue;
						}
						$relatedTitles[] = $t->getText();
						$count++;
					}

				}
			}
		}

		$wgMemc->set($key, $relatedTitles);

		return $relatedTitles;
	}

	/**
	 * Returns an array of titles that have links to the given
	 * title (presumably an image). All returned articles will be in the
	 * NS_MAIN namespace and will also not be in a excluded category.
	 */
	public static function getLinkedArticles($title) {
		global $wgMemc;
		$cachekey = wfMemcKey("ImageHelper_linked", $title->getArticleID());

		$result = $wgMemc->get($cachekey);
		if ($result) {
			return $result;
		}

		$imageTitle = $title->getDBkey();
		$dbr = wfGetDB( DB_REPLICA );
		$page = $dbr->tableName( 'page' );
		$imagelinks = $dbr->tableName( 'imagelinks' );

		$sql = "SELECT page_namespace,page_title,page_id FROM $imagelinks,$page WHERE il_to=" .
		  $dbr->addQuotes( $imageTitle ) . " AND il_from=page_id";
		$sql = $dbr->limitResult($sql, 500, 0);
		$res = $dbr->query( $sql, __METHOD__ );

		$articles = array();

		$templates = wfMessage('ih_categories_ignore')->inContentLanguage()->text();
		$templates = explode("\n", $templates);
		$templates = str_replace("http://www.wikihow.com/Category:", "", $templates);
		$templates = array_flip($templates); // make the array associative.

		foreach ($res as $s) {
			//check if in main namespace
			if ($s->page_namespace != NS_MAIN) {
				continue;
			}

			//check if in category exclusion list
			$title = Title::MakeTitle( $s->page_namespace, $s->page_title );
			$cats = ($title->getParentCategories());
			if (is_array($cats) && sizeof($cats) > 0) {
				$keys = array_keys($cats);
				$found = false;
				for ($i = 0; $i < sizeof($keys) && !$found; $i++) {
					$t = Title::newFromText($keys[$i]);
					if (isset($templates[urldecode($t->getPartialURL())]) ) {
						//this article is in a category we don't want to show
						$found = true;
						break;
					}
				}
				if ($found)
					continue;
			}
			if ($s->page_title != $imageTitle) {
				$articles[] = $title;
			}

		}

		$wgMemc->set($cachekey, $articles);
		return $articles;
	}

	private static function getImages($articleId) {
		global $wgMemc;

		$key = wfMemcKey("ImageHelper_getImages", $articleId);
		$result = $wgMemc->get($key);
		if ($result) {
			return $result;
		}

		$dbr = wfGetDB( DB_REPLICA );
		$results = array();
		$res = $dbr->select('imagelinks', '*', array('il_from' => $articleId));
		foreach ($res as $row) {
			$results[] = (array)$row;
		}

		$wgMemc->set($key, $results);

		return $results;
	}

	/**
	 *
	 * This function takes an array of titles and finds other images
	 * that are in those articles.
	 * NOTE: Used in WikihowImagePage
	 */
	public function getConnectedImages($articles, $title) {
		global $wgOut, $wgMemc;

		$exceptions = wfMessage('ih_exceptions');
		$imageExceptions = explode("\n", $exceptions);

		$key = wfMemcKey("ImageHelper_getConnectedImages", $title->getText());
		$result = $wgMemc->get($key);
		if ($result) {
			$wgOut->addHTML($result);
			return;
		}

		$imageName = $title->getDBkey();
		if (in_array($imageName, $imageExceptions)) {
			$wgMemc->set($key, "");
			return;
		}

		$html = '';

		$noImageArray = array();
		foreach ($articles as $title) {
			$imageUrl = array();
			$thumbUrl = array();
			$imageTitle = array();
			$imageWidth = array();
			$imageHeight = array();

			$results = self::getImages($title->getArticleID());

			$count = 0;
			if (count($results) <= 1) {
				$noImageArray[] = $title;
				continue;
			}

			$titleLink = Linker::linkKnown( $title );
			$found = false;
			foreach ($results as $row) {
				if ($count >= 4) break;

				if ($row['il_to'] != $imageName && !in_array($row['il_to'], $imageExceptions)) {
					$image = Title::newFromText("Image:" . $row['il_to']);
					if ($image && $image->getArticleID() > 0) {

						$file = wfFindFile($image);
						if ($file && isset($file)) {
							$heightPreference = self::heightPreference(127, 140, $file);
							$thumb = $file->getThumbnail(127, 140, true, true, $heightPreference);
							$imageUrl[] = $image->getFullURL();
							$thumbUrl[] = $thumb->getUrl();
							$imageTitle[] = $row['il_to'];
							$imageWidth[] = $thumb->getWidth();
							$imageHeight[] = $thumb->getHeight();
							$count++;
							$found = true;
						}
					}
				}
			}
			if ($count > 0) {
				$tmpl = new EasyTemplate( __DIR__ );
				$tmpl->set_vars(array(
					'imageUrl' => $imageUrl,
					'thumbUrl' => $thumbUrl,
					'imageTitle' => $imageTitle,
					'title' => $titleLink,
					'numImages' => count($imageUrl),
					'imageWidth' => $imageWidth,
					'imageHeight' => $imageHeight,
					'imgStrip' => false
				));

				$html .= $tmpl->execute('connectedImages.tmpl.php');
			} else {
				$noImageArray[] = $title;
			}
		}

		if (sizeof($noImageArray) > 0) {
			$html .= "<div class='minor_section'>
						<h2>" . wfMessage('ih_otherlinks') . "</h2><ul class='im-images'>";
			foreach ($noImageArray as $title) {
				$link = Linker::linkKnown( $title );
				$html .= "<li>{$link}</li>\n";
			}
			$html .= "</ul></div>";
		}

		$wgMemc->set($key, $html);

		$wgOut->addHTML($html);
	}

	// Used in WikihowImagePage. NOTE: Should be static
	public function calcResize($width, $height, $maxWidth, $maxHeight) {
		if ( $width > $maxWidth || $height > $maxHeight ) {
			# Calculate the thumbnail size.
			# First case, the limiting factor is the width, not the height.
			if ( $width / $height >= $maxWidth / $maxHeight ) {
				$height = round( $height * $maxWidth / $width);
				$width = $maxWidth;
				# Note that $height <= $maxHeight now.
			} else {
				$newwidth = floor( $width * $maxHeight / $height);
				$height = round( $height * $newwidth / $width );
				$width = $newwidth;
				# Note that $height <= $maxHeight now, but might not be identical
				# because of rounding.
			}
			$size['width'] = $width;
			$size['height'] = $height;
			return $size;
		} else {
			# Image is small enough to show full size on image page
			$size['width'] = $width;
			$size['height'] = $height;
			return $size;
		}
	}

	// Used in WikihowImagePage. NOTE: Should be static
	public function showDescription($imageTitle) {
		global $wgOut;

		$description = "";

		$t = Title::newFromText('Image:' . $imageTitle->getPartialURL() . '/description');
		if ($t && $t->getArticleId() > 0) {
			$r = Revision::newFromTitle($t);
			$description = ContentHandler::getContentText( $r->getContent() );
			$wgOut->addHTML("<div style='margin-top:10px;' class='im-images'>");
			$wgOut->addHTML("<strong>Description: </strong>");
			$wgOut->addHTML($description);
			$wgOut->addHTML("</div>");
		}

	}

	// Used in WikihowImagePage
	public function addSideWidgets($imagePage, $title, $image) {
		$skin = $this->getSkin();

		if (self::IMAGES_ON) {
			// first add related images
			$html = self::getRelatedImagesWidget($title);
			if ($html != "")
				$skin->addWidget($html);

		}
		// first add image info
		$html = $this->getImageInfoWidget($imagePage, $title, $image);
		if ($html) {
			$skin->addWidget($html);
		}
		if (self::IMAGES_ON) {
			$html = self::getRelatedWikiHowsWidget($title);
			if ($html) {
				$skin->addWidget($html);
			}
		}
	}

	// Used by addSideWidgets
	private static function getRelatedWikiHowsWidget($title) {
		$articles = self::getLinkedArticles($title);
		$relatedArticles = array();
		foreach ($articles as $t) {
			$related = self::setRelatedWikiHows($t);
			foreach ($related as $titleString) {
				$relatedArticles[$titleString] = $titleString;
			}
		}

		$section = '';
		$count = 0;
		$images = '';
		foreach ($relatedArticles as $titleString) {
			$t = Title::newFromText($titleString);
			if ($t && $t->exists()) {
				$images .= self::getArticleThumb($t, 127, 140);

				if (++$count == 6) break;
			}
		}
		if ($count > 0) {
			$section .= "<h3>" . wfMessage('ih_relatedArticles') . "</h3>
						<div class='other_articles_side'>
						$images
						<div class='clearall'></div>
						</div>";
		}


		return $section;
	}

	// Used from addSideWidgets and the wikiHow Image page class
	public function getImageInfoWidget($imagePage, $title, $image) {
		global $wgOut;

		$t = Title::newFromText('Image-Templates', NS_CATEGORY);
		if ($t) {
			$cv = new WikihowCategoryViewer($t, $this->getContext());
			$cv->clearCategoryState();
			$cv->doQuery(/*$getSubcats*/ true, /*$calledFromCategoryPage*/ false);

			$templates = array();
			foreach ($cv->articles as $article) {
				$start = strrpos($article, 'title="Template:');
				if ($start > 0) {
					$end = strrpos($article, '"', $start + 16 + 1);
					if ($end > 0) {
						$templates[] = strtolower(str_replace(' ', '-', substr($article, $start + 16, $end - $start - 16)));
					}
				}

			}

			$license = '';
			$content = preg_replace_callback(
				'@({{([^}|]+)(\|[^}]*)?}})@',
				function ($m) use ($templates, &$license) {
					$name = trim(strtolower($m[2]));
					$name = str_replace(' ', '-', $name);
					foreach ($templates as $template) {
						if ($name == $template) {
							$license .= $m[0];
							return '';
						}
					}
					return $m[1];
				},
				$imagePage->getContent()
			);
		}

		$html = "<div id='im-info' style='word-wrap: break-word;'>".
						$wgOut->parse("=== Licensing / Attribution === \n" . $license );

		$lastUser = $image->getUser();
		if ($lastUser) {
			$userLink = Linker::link(Title::makeTitle(NS_USER, $lastUser), $lastUser);
			$html .= "<p>".wfMessage('image_upload', $userLink)->text()."</p><br />";
		}

		// now remove old licensing header
		$content = str_replace("== Licensing ==", "", $content);
		$content = str_replace("== Summary ==", "=== Summary ===", $content);
		$content = trim($content);

		if (strlen($content) > 0 && substr($content, 0, 1) != "=") {
			$content = "=== Summary === \n" . $content;
		}

		$html .= $wgOut->parse($content);

		$html .= "</div>";

		return $html;
	}

	// Used by addSideWidgets
	private static function getRelatedImagesWidget($title) {
		$exceptions = wfMessage('ih_exceptions');
		$imageExceptions = explode("\n", $exceptions);

		$articles = self::getLinkedArticles($title);
		$images = array();
		foreach ($articles as $t) {
			$results = self::getImages($t->getArticleID());
			if (count($results) <= 1) {
				continue;
			}

			$titleDb = $title->getDBkey();
			foreach ($results as $row) {
				if ($row['il_to'] != $titleDb && !in_array($row['il_to'], $imageExceptions)) {
					$images[] = $row['il_to'];
				}
			}
		}

		$count = 0;
		$maxLoc = count($images);
		$maxImages = $maxLoc;
		$finalImages = array();
		while ($count < 6 && $count < $maxImages) {
			$loc = rand(0, $maxLoc);
			if (isset($images[$loc]) && $images[$loc]) {
				$image = Title::newFromText("Image:" . $images[$loc]);
				if ($image && $image->getArticleID() > 0) {
					$file = wfFindFile($image);
					if ($file) {
						$finalImages[] = array('title' => $image, 'file' => $file);
						$images[$loc] = null;
						$count++;
					} else {
						$maxImages--;
					}
				} else {
					$maxImages--;
				}
				$images[$loc] = null;
			}
		}

		if (count($finalImages) > 0) {
			$html = '<div><h3>' . wfMessage('ih_relatedimages_widget') . '</h3><table style="margin-top:10px" class="image_siderelated">';
			$count = 0;
			foreach ($finalImages as $imageObject) {
				$image = $imageObject['title'];
				$file = $imageObject['file'];
				if ($count % 2 == 0)
					$html .= "<tr>";

				$heightPreference = self::heightPreference(127, 140, $file);
				$thumb = $file->getThumbnail(127, 140, true, true, $heightPreference);
				$imageUrl = $image->getFullURL();
				$thumbUrl = $thumb->getUrl();
				$imageTitle = $image->getText();

				$html .= "<td valign='top'>
							<a href='" . $imageUrl . "' title='" . htmlspecialchars($imageTitle) . "' class='image'>
							<img border='0' class='mwimage101' src='" . wfGetPad($thumbUrl) ."' alt='" . $imageTitle . "'>
							</a>
						</td>";

				if ($count % 2 == 2)
					$html .= "</tr>";

				$count++;
			}
			if ($count % 3 != 2)
				$html .= "</tr>";
			$html .= "</table></div>";

			return $html;
		}
	}

	public static function getGalleryImage($title, $width, $height, $skip_parser = false, $cdn_url = true) {
		global $wgMemc, $wgLanguageCode, $wgContLang, $wgDefaultImage;

		$cachekey = wfMemcKey('gallery2', $title->getArticleID(), $width, $height);
		$val = $wgMemc->get($cachekey);
		if ($val) {
			return $cdn_url ? wfGetPad( $val ) : $val;
		}

		$expiry = 6 * 3600; // 6 hours

		if ( $title->inNamespaces(NS_MAIN, NS_CATEGORY) ) {
			if ($title->inNamespace(NS_MAIN)) {
				$file = Wikitext::getTitleImage($title, $skip_parser);

				if ($file && isset($file)) {
					//need to figure out what size it will actually be able to create
					//and put in that info. ImageMagick gives prefence to width, so
					//we need to see if it's a landscape image and adjust the sizes
					//accordingly
					$sourceWidth = $file->getWidth();
					$sourceHeight = $file->getHeight();
					$heightPreference = false;
					if ($width/$height < $sourceWidth/$sourceHeight) {
						//desired image is portrait
						$heightPreference = true;
					}
					$thumb = $file->getThumbnail($width, $height, true, true, $heightPreference);
					if ($thumb instanceof MediaTransformError) {
						// we got problems!
						$thumbDump = print_r($thumb, true);
						wfDebug("problem getting thumb for article '{$title->getText()}' of size {$width}x{$height}, image file: {$file->getTitle()->getText()}, path: {$file->getPath()}, thumb: {$thumbDump}\n");
					} else {
						$wgMemc->set($cachekey, $thumb->getUrl(), $expiry);
						return $cdn_url ? wfGetPad( $thumb->getUrl() ) : $thumb->getUrl();
					}
				}
			}

			$catmap = CategoryHelper::getIconMap();

			// if page is a top category itself otherwise get top
			if (isset($catmap[urldecode($title->getPartialURL())])) {
				$cat = urldecode($title->getPartialURL());
			} else {
				$cat = CategoryHelper::getTopCategory($title);

				//INTL: Get the partial URL for the top category if it exists
				// For some reason only the english site returns the partial
				// URL for getTopCategory
				if (isset($cat) && $wgLanguageCode != 'en') {
					$title = Title::newFromText($cat);
					if ($title) {
						$cat = $title->getPartialURL();
					}
				}
			}

			if (isset($catmap[$cat])) {
				$image = Title::newFromText($catmap[$cat]);
				$file = wfFindFile($image, false);
				if ($file) {
					$sourceWidth = $file->getWidth();
					$sourceHeight = $file->getHeight();
					$heightPreference = false;
					if ($width/$height < $sourceWidth/$sourceHeight) {
						//desired image is portrait
						$heightPreference = true;
					}
					$thumb = $file->getThumbnail($width, $height, true, true, $heightPreference);
					if ($thumb) {
						$wgMemc->set($cachekey, $thumb->getUrl(), $expiry);
						return $cdn_url ? wfGetPad( $thumb->getUrl() ) : $thumb->getUrl();
					}
				}
			} else {
				$image = Title::makeTitle(NS_IMAGE, $wgDefaultImage);
				$file = wfFindFile($image, false);
				if (!$file) {
					$file = wfFindFile($wgDefaultImage);
					if (!$file) {
						return "";
					}
				}
				$sourceWidth = $file->getWidth();
				$sourceHeight = $file->getHeight();
				$heightPreference = false;
				if ($width/$height < $sourceWidth/$sourceHeight) {
					//desired image is portrait
					$heightPreference = true;
				}
				$thumb = $file->getThumbnail($width, $height, true, true, $heightPreference);
				if ($thumb) {
					$wgMemc->set($cachekey, $thumb->getUrl(), $expiry);
					return $cdn_url ? wfGetPad( $thumb->getUrl() ) : $thumb->getUrl();
				}
			}
		}
		return '';
	}

	// gets the thumbnail for an article and optionally a query param on the link
	public static function getArticleThumb($t, $width, $height, $query = array(), $defer = false) {
		$data = FeaturedArticles::featuredArticlesAttrs($t, "", $width, $height);
		return self::getArticleThumbFromData( $data, $query, $defer );
	}

	public static function getArticleThumbFromData($data, $query = array(), $defer = false) {
		global $wgContLang, $wgLanguageCode, $wgTitle;

		if ($wgLanguageCode == "zh") {
			$articleName = $wgContLang->convert($data['name']);
		} else {
			$articleName = $data['name'];
		}
		$url = $data['url'];
		if ($query) {
			$url .= "?".wfArrayToCgi($query);
		}

		$imgAttributes = array(
			"src" => $data['img'],
			"alt" => $articleName,
		);

		$defer = $defer && $wgTitle && $wgTitle->inNamespace(NS_MAIN);

		if ($defer) {
			$imgAttributes['class'] = 'defer';
			list($img, $noscript) = DeferImages::generateDeferredImageHTML($imgAttributes);
		} else {
			$img = Html::element( 'img', $imgAttributes );
			$noscript = '';
		}
		$msg = wfMessage('howto_prefix');
		$howToPrefix = $msg->exists() ? ($msg->text() . '<br>') : '';
		$howToSuffix = wfMessage('howto_suffix')->showIfExists();
		$html = <<<EOT
<div class='thumbnail' style='width:{$data['width']}px; height:{$data['height']}px;'>
	<a href='$url'>
		{$img}
		<div class='text'>
			<p>
				{$howToPrefix}<span>{$articleName}{$howToSuffix}</span>
			</p>
		</div>
	</a>
	{$noscript}
</div>
EOT;
		return $html;
	}

	/**
	 * As createThumb, but returns a ThumbnailImage object. This can
	 * provide access to the actual file, the real size of the thumb,
	 * and can produce a convenient <img> tag for you.
	 *
	 * For non-image formats, this may return a filetype-specific icon.
	 *
	 * @param integer $width    maximum width of the generated thumbnail
	 * @param integer $height   maximum height of the image (optional)
	 * @param boolean $render   True to render the thumbnail if it doesn't exist,
	 *                          false to just return the URL
	 *
	 * @return ThumbnailImage or null on failure
	 *
	 * @deprecated use transform()
	 */
	// NOTE: Reuben changed this function to ignore the $render flag; we should
	// refactor the calls to this function to remove that param. Chatted with
	// Aaron and Bebeth about this $render change on May 9, 2014.
	// NOTE: Reuben deprecated the $heightPreference param -- it does nothing now
	// NOTE: called from File::getThumbnail
	public static function getThumbnail( $file, $width, $height=-1, $render = true, $crop = false, $heightPreference = false, $pageId = null, $quality = null) {
		$params = array( 'width' => $width );
		if ( $height != -1 ) {
			$params['height'] = $height;
		}

		if ($crop) {
			$params['crop'] = 1;
		}

		// add the page id param if we have it.
		// it will be used in watermarks which have the title of the page they live on
		if ( $pageId != null ) {
			$params['mArticleID'] = $pageId;
		}

		if ($quality) {
			$params['quality'] = $quality;
		}

		$params['heightPreference'] = $heightPreference;
		// Reuben: No longer use RENDER_NOW flag because it's unnecessary and
		// messes up the transformVia404 stuff
		//$flags = $render ? File::RENDER_NOW : 0;
		$flags = 0;
		return $file->transform( $params, $flags );
	}

	/**
	 * get an img file given a thumbnail url
	 * useful if you have a thumbnail url but want to make a new sized thumb
	 */
	public static function getImgFileFromThumbUrl( $imageUrl ) {
		if ( !$imageUrl ) {
			return null;
		}

		$thumb = explode( "/", $imageUrl );
		if ( !$thumb || count($thumb) < 2 || $thumb[2] != "thumb" ) {
			return null;
		}
		$thumb = $thumb[count($thumb) - 2];
		$img = Title::newFromText( $thumb, NS_IMAGE);
		$imgFile = RepoGroup::singleton()->findFile( $thumb );
		return $imgFile;
	}

}
