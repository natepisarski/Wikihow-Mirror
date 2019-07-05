<?php
$wgHooks['PageContentSaveComplete'][] = array('RelatedWikihows::clearArticleMemc');
class RelatedWikihow {
	var $mThumbUrl = '';
	var $mText = '';
	var $mUrl = '';

	public function createSidebarHtml() {
		return $this->createHtml( RelatedWikihows::SIDEBAR_IMG_WIDTH, RelatedWikihows::SIDEBAR_IMG_HEIGHT, true );
	}

	public function createSidebarLargerHtml() {
		return $this->createHtml( RelatedWikihows::SIDEBAR_LARGER_IMG_WIDTH, RelatedWikihows::SIDEBAR_LARGER_IMG_HEIGHT, false, false, true );
	}

	public function createDesktopHtml() {
		return $this->createHtml( RelatedWikihows::RELATED_IMG_WIDTH, RelatedWikihows::RELATED_IMG_HEIGHT, false );
	}

	public function createMobileHtml() {
		return $this->createHtml( RelatedWikihows::RELATED_IMG_WIDTH, RelatedWikihows::RELATED_IMG_HEIGHT );
	}

	public function createAmpHtml() {
		return $this->createHtml( RelatedWikihows::RELATED_IMG_WIDTH, RelatedWikihows::RELATED_IMG_HEIGHT, false, true );
	}

	private function createHtml( $width, $height, $isSidebar = false, $ampMode = false, $largeSidebar = false ) {
		$imgSrc = '';
		$videoSrc = $this->mVideoUrl;
		if ( $isSidebar ) {
			$imgSrc = $this->mThumbUrlSide;
			$videoSrc = "";
		} elseif ( $largeSidebar ) {
			$imgSrc = $this->mThumbUrlSideLarger;
			$videoSrc = "";
		} else {
			$imgSrc = $this->mThumbUrl;
			if ( isset( $this->mGifUrl ) ) {
				$imgSrc = $this->mGifUrl;
			}
		}

		$imgSrc = wfGetPad( $imgSrc );
		$text =  $this->mText;
		$url =  $this->mUrl;
		$id = wfRandomString(10);
		$imgAttributes = [
			'id' => $id,
			'class' => 'scrolldefer content-fill',
			'src' => $imgSrc,
			'alt' => $text,
			'width' => $width,
			'height' => $height
		];

		// create the fallback noscript img tag
		$noscript = Html::openElement('noscript')
			. Html::element('img', ['src' => $imgAttributes['src']])
			. Html::closeElement('noscript');

		$imgAttributes['data-src'] = $imgAttributes['src'];
		unset( $imgAttributes['src'] );
		$img = Html::rawElement( 'img', $imgAttributes );

		// now create the video if we have it
		$videoElement = '';
		if ( $videoSrc ) {
			$videoAttributes = [
				'id' => $id,
				'class' => 'scrolldefer content-fill',
				'data-src' => $videoSrc,
				'alt' => $this->mText,
				'width' => $width,
				'height' => $height,
				'playsinline' => '',
				'webkit-playsinline' => '',
				'muted' => '',
				'data-poster' => $this->mThumbUrl,
				'loop' => '',
				//'autoplay' => ''
			];
			$videoElement = Html::rawElement( 'video', $videoAttributes );
		}

		if ( $videoElement ) {
			$img = $videoElement;
		}

		$img = Html::rawElement( 'div', [ 'class' => 'content-spacer' ], $img );
		$script = "WH.shared.addScrollLoadItem('$id')";
		$script = Html::inlineScript( $script );
		$img .= $script . $noscript;

		if ( $ampMode ) {
			$imgAttributes = [
				'src' => $imgSrc,
				'alt' => $text,
				'width' => $width,
				'height' => $height,
				'layout' => 'responsive'
			];
			$img = Html::rawElement( 'amp-img', $imgAttributes );
		}

		$linkAttributes = [
			'class' => 'related-image-link',
			'href' => $url
		];
		$link = Html::rawElement( "a", $linkAttributes, $img );

		// this is the wrapper div around the link
		$imageAttributes = [
			'class' => 'related-image',
		];
		$image = Html::rawElement( "div", $imageAttributes, $link );


		// the text to show for each related wikihow

		$msg = wfMessage('howto_prefix');
		$howToPrefix = $msg->exists() ? ('<p>' . $msg->text() . '</p>') : '';
		$howToText = $howToPrefix . $text . wfMessage('howto_suffix')->showIfExists();
		$titleText = Html::rawElement( "span", [ 'class' => 'related-title-text' ], $howToText );
		$titleAttributes = [
			'class' => 'related-title',
			'href' => $url
		];
		$titleLink = Html::rawElement( "a", $titleAttributes, $titleText );

		// we wrap it all in a div for styling purposes
		$wrapperAttributes = [
			'class' => 'related-article'
		];
		$wrapper = Html::rawElement( "div", $wrapperAttributes, $image . $titleLink);

		return $wrapper;
	}
}

class RelatedWikihows {

	const RELATED_IMG_WIDTH = 342;
	const RELATED_IMG_HEIGHT = 184;
	const MOBILE_IMG_WIDTH = 360;
	const MOBILE_IMG_HEIGHT = 231;
	const SIDEBAR_IMG_WIDTH = 127;
	const SIDEBAR_IMG_HEIGHT = 140;
	const SIDEBAR_LARGER_IMG_WIDTH = 290;
	const SIDEBAR_LARGER_IMG_HEIGHT = 156;
	const MIN_TO_SHOW_DESKTOP = 14;
	const QUERY_STRING_PARAM = "newrelateds";
	const MEMCACHED_KEY = "relarticles_data";

	var $mShowEdit = null;
	var $mEditLink = '';
	var $mTitle = null;
	var $mShowSection = null;
	var $mMobile = false;
	var $mAmpMode = false;
	var $mUsingPhpQuery = false;
	var $mShowOtherWikihowsTitle = false;
	var $mIdName = "relatedwikihows";
	var $mAd = null;

	public function __construct( $context, $user, $relatedSection = '' ) {
		$title = $context->getTitle();
		$this->mTitle = $title;
		$this->mShowEdit = $title->quickUserCan( 'edit', $user );
		$this->mShowSection = $title && $title->inNamespace( NS_MAIN ) && $title->exists() && !$title->isRedirect() && PagePolicy::showCurrentTitle( $context );
		$this->mMobile = Misc::isMobileMode();
		$this->mIdName = self::getSectionName();

		//if the $relatedSection is passed in,
		//we can safely assume that the php query object is set
		$this->mUsingPhpQuery = $relatedSection != '';

		if ($this->mUsingPhpQuery) {
			$this->loadRelatedArticles( pq($relatedSection)->find( '#'.$this->mIdName ) );
			if ( $this->mMobile ) {
				$this->mEditLink = trim( pq( $relatedSection )->find( '.edit-page' ) );
			} else {
				$this->mEditLink = trim( pq( $relatedSection )->find( '.editsection' ) );
			}
		}
		else {
			$this->loadRelatedArticles();
			$this->mEditLink = '';
		}

		$this->mAmpMode = GoogleAmp::isAmpMode( $context->getOutput() );
	}

	/*
	 * set html on an ad to appear in related wikihows
	 */
	public function setAdHtml( $adHtml ) {
		$this->mAd = $adHtml;
	}
	public static function forceShowNewRelated( $out ) {
		$showNew = $out->getRequest()->getVal( self::QUERY_STRING_PARAM ) == 1;
		return $showNew;
	}

	public static function forceShowOldRelated( $out ) {
		$showOld = $out->getRequest()->getVal( self::QUERY_STRING_PARAM ) === '0';
		return $showOld;
	}

	/*
	 * get list of related articles from a title given a category
	 * @param Title $title the title of the page to act on
	 * @param string $cat the category to look in
	 *
	 * @return array result is assoc array with pageids as they key
	 */
	private static function getRelatedArticlesForTitleAndCategory( $title, $cat ) {
		global $wgLanguageCode;

		if ( !$title ) {
			return array();
		}

		if ( !$cat ) {
			return array();
		}

		$cat = $cat->getDBKey();
		$result['category'] = $cat;

		// Populate related articles box with other articles in the category,
		// displaying the featured articles first
		$result = [];

		$dbr = wfGetDB( DB_REPLICA );

		$pageId = $title->getArticleID();
		$table = array( WH_DATABASE_NAME_EN.'.titus_copy' );
		$vars = array( 'ti_page_id' );

		$conds = array(
			'ti_page_id <> '.$pageId,
			'ti_language_code' => $wgLanguageCode,
			'ti_robot_policy' => 'index,follow',
			'ti_num_photos > 0'
		);

		$table[] = 'categorylinks';
		$conds[] = 'ti_page_id = cl_from';
		$conds['cl_to'] = $cat;

		if ( SensitiveRelatedWikihows::isSensitiveRelatedRemovePage( $title ) ) {
			$srpTable = SensitiveRelatedWikihows::SENSITIVE_RELATED_PAGE_TABLE;
			$conds[] = "ti_page_id NOT IN (select srp_page_id from $srpTable)";
		}

		$orderBy = 'ti_30day_views DESC';
		$limit = self::MIN_TO_SHOW_DESKTOP;
		$options = array( 'ORDER BY' => $orderBy, 'LIMIT' => $limit );

		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
		if ( !$res ) {
			return $result;
		}

		foreach ( $res as $row ) {
			$targetId = $row->ti_page_id;
			$result[$targetId] = true;
		}

		return $result;
	}


	/*
	 * get list of categories we can search in for related wikihows
	 * filters out categories to ignore
	 * @param Title $title the title of the page to act on
	 *
	 * @return first title which fits the criteria or null
	 */
	private static function getCategoryForTitle( $title ) {
		if ( !$title ) {
			return null;
		}

		$categories = $title->getParentCategories();

		if ( !is_array( $categories ) || empty( $categories ) ) {
			return null;
		}

		// first get an associative array of categories to ignore
		$categoriesToIgnore = wfMessage( 'categories_to_ignore' )->inContentLanguage()->text();
		$categoriesToIgnore = explode( "\n", $categoriesToIgnore );
		$tempCategories = array();
		foreach ( $categoriesToIgnore as $catToIgnore ) {
			$tempCategories[] = end( explode( ":", $catToIgnore ) );
		}
		$categoriesToIgnore = array_flip( $tempCategories );

		$keys = array_keys( $categories );
		for ( $i = 0; $i < sizeof( $keys ); $i++ ) {
			$t = Title::newFromText( $keys[$i] );
			$partial = $t->getPartialURL();
			if ( isset( $categoriesToIgnore[urldecode( $partial )] ) || isset( $categoriesToIgnore[$partial] ) ) {
				continue;
			} else {
				//$result[] = $t->getDBKey();
				return $t;
			}
		}

		return null;
	}

	/*
	 * get list of related articles and their category given a title
	 * @param Title $title the title of the page to act on
	 *
	 * @return array contains an associative array with "category" as the first key
	 * which is the category of the title passed in, and "articles" as the other key
	 * which is an array whose keys are pageIds of relted articles in that category
	 */
	public static function getRelatedArticlesByCat( $title ) {
		global $wgMemc;

		$cachekey = wfMemcKey( self::MEMCACHED_KEY, $title->getArticleID() );
		$val = $wgMemc->get( $cachekey );
		if ( $val ) {
			return $val;
		}

		$category = self::getCategoryForTitle( $title );
		if ( empty( $category ) ) {
			// return a list from chris
			$result['articles'] = self::getDefaultRelatedWikihows();
		}

		// Populate related articles box with other articles in the category,
		$data = self::getRelatedArticlesForTitleAndCategory( $title, $category );

		$result = array();
		$result['category'] = $category;
		$result['articles'] = $data;

		$wgMemc->set( $cachekey, $result );
		return $result;
	}

	/*
	 * a list of 10 related wikihows to show if we have no others
	 *		Fall-Asleep
	 *		Get-Effects-on-Snapchat
	 *		Get-Rid-of-a-Headache
	 *		Unclog-a-Toilet
	 *		Use-Uber
	 *		Take-a-Screenshot-in-Microsoft-Windows
	 *		French-Braid
	 *		Play-Poker
	 *		Care-for-Orchids
	 */
	private static function getDefaultRelatedWikihows() {
		return array_flip( [ 57203, 4157156, 14093, 5207, 1304771, 3450, 22372, 5014, 221266 ] );
	}

	// gets the related wikihow titles from wikitext
	private static function getRelatedArticlesFromWikitext( $relatedSection ) {
		global $wgTitle;
		$relatedArticles = array();

		$isSensitiveRelatedRemovePage = SensitiveRelatedWikihows::isSensitiveRelatedRemovePage( $wgTitle );
		//first lets check to make sure all the related are indexed
		foreach ( pq( "li a", $relatedSection ) as $related ) {
			$titleText = pq( $related )->attr( "title" );
			$title = Title::newFromText( $titleText );
			if ( !$title ) {
				continue;
			}
			if ( !$title->exists() ) {
				continue;
			}
			$id = $title->getArticleID();
			if ( !self::isIndexed( $id ) ) {
				continue;
			}
			if ( $isSensitiveRelatedRemovePage && SensitiveRelatedWikihows::isSensitiveRelatedPage( $id ) ) {
				continue;
			}
			$relatedArticles[$id] = true;
		}
		return $relatedArticles;
	}

	// takes in an array of titles
	// return an array containing just the info needed to create the related section
	// also gets the image thumbnails
	public static function makeRelatedArticlesData( $relatedArticles, $useCategoryThumbs = true) {
		// now that we have the list of titles, we can make a more compact array of related data
		// that is easily cachable
		$width = self::RELATED_IMG_WIDTH;
		$height = self::RELATED_IMG_HEIGHT;
		$sideWidth = self::SIDEBAR_IMG_WIDTH;
		$sideHeight = self::SIDEBAR_IMG_HEIGHT;
		$sideWidthLarger = self::SIDEBAR_LARGER_IMG_WIDTH;
		$sideHeightLarger = self::SIDEBAR_LARGER_IMG_HEIGHT;
		$mobileWidth = self::MOBILE_IMG_WIDTH;
		$mobileHeight = self::MOBILE_IMG_HEIGHT;
		$related = array();

		foreach ( $relatedArticles as $id => $val ) {
			$title = Title::newFromID( $id );
			if ( !$title || !$title->exists() ) {
				continue;
			}
			// uncomment this to get the article's representative gif and video
			//$gifUrl = ArticleMetaInfo::getGif( $title );
			$videoUrl = ArticleMetaInfo::getVideoSrc( $title );
			$thumbnailImage = ArticleMetaInfo::getRelatedThumb( $title, $width, $height );
			$thumbnailImageSide = ArticleMetaInfo::getRelatedThumb( $title, $sideWidth, $sideHeight );
			$thumbnailImageSideLarger = ArticleMetaInfo::getRelatedThumb( $title, $sideWidthLarger, $sideHeightLarger );
			$thumbnailImageMobile = ArticleMetaInfo::getRelatedThumb( $title, $mobileWidth, $mobileHeight );

			if ( !$thumbnailImage ) {
				continue;
			}

			if ( !$useCategoryThumbs && strstr( $thumbnailImage->getUrl(), "Category" ) ) {
				if ( mt_rand( 1, 3 ) <= 2 ) {
					$defaultImageFile = Wikitext::getDefaultTitleImage();
					$thumbnailImage = ImageHelper::getThumbnail( $defaultImageFile, $width, $height );
					$thumbnailImageSide = ImageHelper::getThumbnail( $defaultImageFile, $sideWidth, $sideHeight );
					$thumbnailImageMobile = ImageHelper::getThumbnail( $defaultImageFile, $mobileWidth, $mobileHeight );
				}
			}
			$item = new RelatedWikihow();
			//$item->mGifUrl = $gifUrl;
			$item->mVideoUrl = $videoUrl;
			$item->mThumbUrl = $thumbnailImage->getUrl();
			$item->mThumbUrlSide = $thumbnailImageSide->getUrl();
			$item->mThumbUrlSideLarger = $thumbnailImageSideLarger->getUrl();
			$item->mThumbUrlMobile = $thumbnailImageMobile->getUrl();
			$item->mText = $title->getText();
			$item->mUrl = $title->getLocalURL();

			$related[] = $item;
		}
		return $related;
	}


	private static function isIndexed( $pageId ) {
		$dbr = wfGetDB( DB_REPLICA );
		$count = $dbr->selectField(
			'index_info',
			'count(*)',
			array( 'ii_page' => $pageId, 'ii_policy in (1, 4)' ),
			__METHOD__ );

		return $count > 0;
	}

	private function getRelatedArticles( $title ) {
		return $this->mRelatedWikihows;
	}

	private function loadRelatedArticles( $relatedSection = '' ) {
		$relatedArticles = [];
		$title = $this->mTitle;
		// get the related articles in the wikitext
		if ($this->mUsingPhpQuery) {
			$relatedArticles = self::getRelatedArticlesFromWikitext( $relatedSection );
		}

		Hooks::run( 'RelatedWikihowsBeforeLoadRelatedArticles', array( $title, &$relatedArticles ) );

		// minimum number to always show
		$minNumber = self::MIN_TO_SHOW_DESKTOP;
		$count = count( $relatedArticles );
		if ( $count < $minNumber ) {
			// the array keys are the page ids, and the + operator here
			// prevent us having duplicate keys when combing arrays
			$relatedByCat = $this->getRelatedArticlesByCat( $title );
			if ( isset( $relatedByCat['category'] ) && $relatedByCat['category'] === '' ) {
				$this->mShowOtherWikihowsTitle = true;
			}
			if ( isset( $relatedByCat['articles'] ) ) {
				$relatedArticlesByCategory = $relatedByCat['articles'];
			} else {
				$relatedArticlesByCategory = $relatedByCat;
			}

			$relatedArticles = $relatedArticles + $relatedArticlesByCategory;

			// limit the results of the wikitext and by-cat
			$relatedArticles = array_slice( $relatedArticles, 0, self::MIN_TO_SHOW_DESKTOP, true );
		}

		Hooks::run( 'RelatedWikihowsAfterLoadRelatedArticles', array( $title, &$relatedArticles ) );

		// pull out the needed data from the related articles and create thumbnail urls
		$this->mRelatedWikihows = self::makeRelatedArticlesData( $relatedArticles );

		return $this->mRelatedWikihows;
	}

	// mostly copied from wikihow ArticleHooks onDoEditSectionLink
	// but doesn't have a secton number.
	// for mobile version we could use SkinMinerva.php doEditSedctionLink
	// however that requires a section number..so for now leave it blank
	private function createEditLink() {
		if ( $this->mMobile ) {
			return "";
		}

		$query = array();
		$query['action'] = "edit";

		$tooltip = wfMessage('relatedwikihows');
		$customAttribs = array(
			'class' => 'editsection',
			'onclick' => "gatTrack(gatUser,\'Edit\',\'Edit_section\');",
			'tabindex' => '-1',
			'title' => wfMessage('editsectionhint')->rawParams( htmlspecialchars($tooltip) )->escaped(),
			'aria-label' => wfMessage('aria_edit_section')->rawParams( htmlspecialchars($tooltip) )->showIfExists(),
		);

		$result = Linker::link( $this->mTitle, wfMessage('editsection')->text(), $customAttribs, $query, "known");
		return $result;
	}

	// the main function to be called to get the related articles section
	private function getSectionHtml() {
		$sectionTitle = "Related wikiHows";
		if ( $this->mShowOtherWikihowsTitle ) {
			$sectionTitle = wfMessage( 'otherwikihows' );
		} else {
			$sectionTitle = wfMessage( 'relatedwikihows' )->text();
		}

		Hooks::run( 'RelatedWikihowsBeforeGetSectionHtml', array( &$sectionTitle ) );

		$span = Html::element( "span", array( 'class'=>'mw-headline', 'id'=>'Related_wikiHows' ), $sectionTitle );
		$editLink = $this->mEditLink;
		if ( !$editLink && $this->mShowEdit && Hooks::run( 'RelatedWikihowsShowEditLink', array() ) ) {
			$editLink = $this->createEditLink();
		}
		$heading = Html::rawElement( "h2", array(), $editLink . $span );
		$editlink_text = wfMessage( 'editarticle' )->text();

		$relatedWikihows = $this->mRelatedWikihows;
		// if odd number
		if ( count( $relatedWikihows ) % 2 == 1 ) {
			array_pop( $relatedWikihows );
		}

		if ( count( $relatedWikihows ) == 0 ) {
			return "";
		}

		$insertAd = false;
		if ( $this->mAd && count( $relatedWikihows ) > 1 && !$this->mMobile && !$this->mAmpMode ) {
			$insertAd = true;
		}

		if ( $insertAd ) {
			array_pop( $relatedWikihows );
		}

		$thumbs = "";
		foreach ( $relatedWikihows as $relatedWikihow ) {
			if ( $this->mAmpMode ) {
				$thumbs .= $relatedWikihow->createAmpHtml();
			} elseif ( $this->mMobile ) {
				$thumbs .= $relatedWikihow->createMobileHtml();
			} else {
				$thumbs .= $relatedWikihow->createDesktopHtml();
			}
		}

		if ( $insertAd ) {
			// get the ad now
			$thumbs .= $this->mAd;
		}

		$clear = Html::rawElement( "div", array( 'class' => 'clearall' ) );

		$contents = Html::rawElement( "div", array( 'id' => 'relatedwikihows', 'class' => 'section_text' ), $thumbs.$clear );

		$section = Html::rawElement( "div", array( 'class' => [ 'section', 'relatedwikihows', 'sticky' ],  ), $heading.$contents );

		return $section;
	}

	private function okToShowSection() {
		return $this->mShowSection;
	}

	// takes the html of the related wikihows and adds it to the current php query document
	public function addRelatedWikihowsSection() {
		if ( !$this->okToShowSection() ) {
			return;
		}
		$relatedHtml = $this->getSectionHtml();

		if ( !$relatedHtml ) {
			return;
		}

		$prevSection = null;
		// remove existing section if it exists (we already have the data we need from it)
		$sectionSelector = ".".self::getSectionName();
		if ( pq( $sectionSelector )->length > 0 ) {
			// get the prev setion so we can insert the new one after it
			$prevSection = pq( $sectionSelector. ":first" )->prev();
		}

		pq( $sectionSelector )->remove();

		// try to put the related wikihows section back where it was
		// if we have created it new, then put it before 'About this wikiHow' (#sp_h2) on mobile
		// or before sourcesandcitations on desktop
		// or just put it as the last section if these other sections do not exist
		if ( $prevSection && $prevSection->length > 0  ) {
			pq( $prevSection )->after( $relatedHtml );
		} elseif (!$this->mMobile && pq( ".section.sourcesandcitations" )->length > 0 ) {
			pq( ".section.sourcesandcitations" )->before( $relatedHtml );
		} else if (!$this->mMobile && pq( ".section.references" )->length > 0 ) {
			pq( ".section.references" )->before( $relatedHtml );
		} else if ( pq( "#sp_h2" )->length > 0 ) {
			pq( "#sp_h2" )->before( $relatedHtml );
		} else {
			pq( ".section:last" )->after( $relatedHtml );
		}
	}

	// get 4 related wikihows to show in the side bar for an ad test
	public function getSideDataLarger() {
		$header = Html::element( 'h3', array(), wfMessage('relatedarticles')->text() );

		$relatedWikihows = $this->mRelatedWikihows;

		if ( count( $relatedWikihows ) == 0 ) {
			return "";
		}

		$relatedWikihows = array_slice( $relatedWikihows, 0, 4 );

		$thumbs = "";
		foreach ( $relatedWikihows as $relatedWikihow ) {
			$thumbs .= $relatedWikihow->createSidebarLargerHtml();
		}

		$clear = Html::rawElement( "div", array( 'class' => 'clearall' ) );

		$html = $header.$thumbs.$clear;
		return $html;
	}
	// get 4 related wikihows to show in the side bar
	public function getSideData() {
		$header = Html::element( 'h3', array(), wfMessage('relatedarticles')->text() );

		$relatedWikihows = $this->mRelatedWikihows;

		if ( count( $relatedWikihows ) == 0 ) {
			return "";
		}

		$relatedWikihows = array_slice( $relatedWikihows, 0, 4 );

		$thumbs = "";
		foreach ( $relatedWikihows as $relatedWikihow ) {
			$thumbs .= $relatedWikihow->createSidebarHtml();
		}

		$clear = Html::rawElement( "div", array( 'class' => 'clearall' ) );

		$html = $header.$thumbs.$clear;
		return $html;
	}

	//function we use when we're not inserting via the php Query object
	public function getRelatedHtml() {
		$relatedWikihows = $this->mRelatedWikihows;

		if ( count( $relatedWikihows ) == 0 ) {
			return "";
		}

		$num = $this->mMobile ? 2 : 4;
		$relatedWikihows = array_slice( $relatedWikihows, 0, $num );

		$thumbs = "";
		foreach ( $relatedWikihows as $relatedWikihow ) {
			$thumbs .= $this->mMobile ? $relatedWikihow->createMobileHtml() : $relatedWikihow->createDesktopHtml();
		}

		$clear = Html::rawElement( "div", array( 'class' => 'clearall' ) );
		$html = Html::rawElement( "div", array( 'id' => 'qa_related_box' ), $thumbs.$clear );

		return $html;
	}

	public static function clearArticleMemc( $wikiPage ) {
		global $wgMemc;
		if ( !$wikiPage ) {
			return;
		}

		$title = $wikiPage->getTitle();
		if ( !$title ) {
			return;
		}

		$cachekey = wfMemcKey( self::MEMCACHED_KEY, $title->getArticleID() );
		$wgMemc->delete( $cachekey );
	}

	// return the name of this section id
	public static function getSectionName() {
		$relatedsname = wfMessage('relatedwikihows')->text();
		$relatedsname = mb_strtolower($relatedsname); //make it lowercase
		$relatedsname = preg_replace('/[\s\p{P}]/u', '', $relatedsname); //remove spaces and punctuation
		return $relatedsname;
	}
}

class SensitiveRelatedWikihows {

	/*
	 * CREATE TABLE `sensitive_related_page` (
	 * `srp_page_id` int(10) unsigned NOT NULL,
	 * PRIMARY KEY (`srp_page_id`)
	 * );
	 * CREATE TABLE `sensitive_related_remove_page` (
	 * `srrp_page_id` int(10) unsigned NOT NULL,
	 * PRIMARY KEY (`srrp_page_id`)
	 * );
	 */

	const FEED_LINK = "https://spreadsheets.google.com/feeds/list/";
	const SHEET_ID = "1JCuh-aB-HxvZM-pKpaEIGFCrz7Er0c6fFyLM8qaWEK0";
	const FEED_LINK_2 = "/private/values?alt=json&access_token=";
	const SENSITIVE_RELATED_PAGE_TABLE = "sensitive_related_page";
	const SENSITIVE_RELATED_REMOVE_PAGE_TABLE = "sensitive_related_remove_page";

	public static function saveSensitiveRelatedArticles() {
		global $IP, $wgIsDevServer;

		require_once("$IP/extensions/wikihow/docviewer/SampleProcess.class.php");

		$service = SampleProcess::buildService();
		if ( !isset( $service ) ) {
			return;
		}
		$result = array();

		$client = $service->getClient();
		$token = $client->getAccessToken();
		$token = json_decode($token);
		$token = $token->access_token;

		$removeListWorksheetId = "ojdakpw";
		$feedLink = self::FEED_LINK . self::SHEET_ID.'/'.$removeListWorksheetId . self::FEED_LINK_2;
		//if ( $wgIsDevServer ) {
			//$feedLink = self::FEED_LINK . '1F7z21I1ePX43Rh9lj2ojMcvoD1rHlw-oxHWhGnf9Y0A'.'/'.$removeListWorksheetId . self::FEED_LINK_2;
		//}
		$sheetData = file_get_contents( $feedLink . $token );
		$sheetData = json_decode( $sheetData );
		//decho('sheetData', $sheetData->{'feed'});exit;
		$sheetData = $sheetData->{'feed'}->{'entry'};
		$removeList = self::parseRemoveList( $sheetData );
		$result = self::saveRemoveList( $removeList );

		$sensitiveMasterWorksheetId = "od6";
		$feedLink = self::FEED_LINK . self::SHEET_ID.'/'.$sensitiveMasterWorksheetId . self::FEED_LINK_2;
		//if ( $wgIsDevServer ) {
			//$feedLink = self::FEED_LINK . '1F7z21I1ePX43Rh9lj2ojMcvoD1rHlw-oxHWhGnf9Y0A'.'/'.$sensitiveMasterWorksheetId . self::FEED_LINK_2;
		//}
		$sheetData = file_get_contents( $feedLink . $token );
		$sheetData = json_decode( $sheetData );
		$sheetData = $sheetData->{'feed'}->{'entry'};
		$sensitiveMasterList = self::parseSensitiveMaster( $sheetData );
		$result .= self::saveSensitiveMasterList( $sensitiveMasterList );
		return $result;
	}

	/*
	 * saves the list of pages from sensitive related wikihows remove tab
	 *
	 * @param Array $data array which has key of lang code and value of array of pageids
	 */
	private static function saveRemoveList( $data ) {
		global $wgWikiHowLanguages;

		// initialize with en data..for some reason this is not in the wgLanguages global used below
		$updateData = array('en' => isset( $data['en'] ) ? $data['en'] : array() );

		foreach ( $wgWikiHowLanguages as $lang ) {
			if ( isset( $data[$lang] ) ) {
				$updateData[$lang] = $data[$lang];
			} else {
				$updateData[$lang] = array();
			}
		}

		$table = self::SENSITIVE_RELATED_REMOVE_PAGE_TABLE;
		$fieldName = 'srrp_page_id';
		$message = '';
		foreach ( $updateData as $lang => $pageIds ) {
			$resultMessage = self::saveNewIdsRemoveDeleteIds( $lang, $pageIds, $table, $fieldName );
			if ( $resultMessage ) {
				$message = $message . "\n" . $resultMessage;
			}
		}
		return $message;
	}

	/*
	 * saves all translations of pageId from EN to it's languages
	 * with data read from the sensitive related wikihows master tab
	 *
	 * updates the sensitive_related_page table with these pages
	 * and removes items which are no longer there
	 *
	 * @param Array $data array which has arrays of the form (pageid, articleurl)
	 * right now assumes EN although that may change in the future so we do read in the articleurl
	 * although we do not actually use it right now
	 */
	private static function saveSensitiveMasterList( $sheetData ) {
		global $wgWikiHowLanguages;
		$message = "";
		if ( !$sheetData ) {
			$message = "no items to remove";
			//decho( $message );
			return $message;
		}
		$data = array();
		// get translation page ids for each item in the list
		foreach ( $sheetData as $pageInfo ) {
			if ( !$pageInfo[0] ) {
				continue;
			}
			$data['en'][] = $pageInfo[0];
			$links = TranslationLink::getLinksTo( 'en', $pageInfo[0] );
			foreach ( $links as $link ) {
				$data[$link->toLang][] = $link->toAID;
			}
		}

		// initialize with en data..for some reason this is not in the wgLanguages global used below
		$updateData = array('en' => isset( $data['en'] ) ? $data['en'] : array() );

		foreach ( $wgWikiHowLanguages as $lang ) {
			if ( isset( $data[$lang] ) ) {
				$updateData[$lang] = $data[$lang];
			} else {
				$updateData[$lang] = array();
			}
		}

		$table = self::SENSITIVE_RELATED_PAGE_TABLE;
		$fieldName = 'srp_page_id';
		$message = '';
		foreach ( $updateData as $lang => $pageIds ) {
			$resultMessage = self::saveNewIdsRemoveDeleteIds( $lang, $pageIds, $table, $fieldName );
			if ( $resultMessage ) {
				$message = $message . "\n" . $resultMessage;
			}
		}
		return $message;
	}

	private static function parseRemoveList( $data ) {
		$result = array();
		if ( !$data ) {
			return $result;
		}
		foreach ( $data as $row ) {
			$lang = $row->{'gsx$language'}->{'$t'};
			$pageId = $row->{'gsx$id'}->{'$t'};
			$result[$lang][] = $pageId;
		}

		return $result;
	}

	private static function parseSensitiveMaster( $data ) {
		$result = array();
		foreach ( $data as $row ) {
			$pageId = $row->{'gsx$id'}->{'$t'};
			$url = $row->{'gsx$url'}->{'$t'};
			$result[] = array( $pageId, $url );
		}

		return $result;
	}

	/*
	 * used to update items from a google sheet into the db into a table which is simply pageId
	 *
	 * @param string $lang the lang to use to get the lang database
	 * @param Array $pageIds list of pages ids to be saved
	 * @param string $table the name of the table to be used
	 * @param string $fieldName the name of the field which contains the pageId on $table
	 */
	private static function saveNewIdsRemoveDeleteIds( $lang, $pageIds, $table, $fieldName ) {
		$dbw = wfGetDB( DB_MASTER );

		$langDB = Misc::getLangDB( $lang );
		if ( !$langDB ) {
			$message = "could not get lang db for:". $lang;
			//decho( $message );
			return $message;
		}
		$table = $langDB . '.' . $table ;
		$cond = array();

		$var = "$fieldName as page_id";
		$res = $dbw->select( $table, $var, $cond, __METHOD__ );
		$existing = array();
		foreach ( $res as $row ) {
			$existing[] = $row->page_id;
		}

		$removeIds = array_unique( array_values( array_diff( $existing, $pageIds ) ) );
		$insertIds = array_unique( array_values( array_diff( $pageIds, $existing ) ) );
		//$removeIds = array_diff( $existing, $pageIds );
		//$insertIds = array_diff( $pageIds, $existing );

		$message = '';
		if ( $removeIds ) {
			//decho("field: $fieldName lang: $lang remove ids", json_encode( $removeIds ) );
			$deleteCond = array( $fieldName => $removeIds );
			$dbw->delete( $table, $deleteCond, __METHOD__ );
			$message = "updated $table for $lang\n";
		}
		if ( $insertIds ) {
			//decho("field: $fieldName lang: $lang insert ids", json_encode( $insertIds ) );
			$insertData  = array();
			foreach ( $insertIds as $id ) {
				$insertData[] = array( $fieldName => $id );
			}

			$dbw->insert( $table, $insertData, __METHOD__ );
			$removeCount = count( $removeIds );
			$insertCount = count( $insertIds );
			$message .= "updated $table for $lang. $removeCount items removed. $insertCount items added.\n";
		}
		if ( !$message ) {
			$message = "no updates for $table for $lang\n";
		}


		$var = "count(*)";
		$removeCount = $dbw->selectField( $table, $var, $cond );
		if ( $fieldName == 'srrp_page_id' ) {
			$type = "remove pages";
		} else {
			$type = "master pages";
		}

		$message .= "number of $type for $lang is: $removeCount\n";
		return $message;
	}

	/*
	 * checks is this page is a sensitive related wikihows master page
	 *
	 * @param Title $title title of the page we are checking
	 */
	public static function isSensitiveRelatedRemovePage( $title ) {
		global $wgLanguageCode;
		if ( !$title ) {
			return false;
		}
		$pageId = $title->getArticleID();
		$dbr = wfGetDB( DB_REPLICA );
		$table = self::SENSITIVE_RELATED_REMOVE_PAGE_TABLE;
		$var = 'count(*)';
		$conds = array(
			'srrp_page_id' => $pageId,
		);
		$options = array();

		$count = $dbr->selectField( $table, $var, $conds, __METHOD__, $options );
		if ( $count ) {
			return true;
		}
		return false;
	}

	public static function isSensitiveRelatedPage( $pageId ) {
		global $wgLanguageCode;
		$dbr = wfGetDB( DB_REPLICA );
		$table = self::SENSITIVE_RELATED_PAGE_TABLE;
		$var = 'count(*)';
		$conds = array(
			'srp_page_id' => $pageId,
		);
		$options = array();
		$count = $dbr->selectField( $table, $var, $conds, __METHOD__, $options );
		if ( $count ) {
			return true;
		}
		return false;
	}
}
