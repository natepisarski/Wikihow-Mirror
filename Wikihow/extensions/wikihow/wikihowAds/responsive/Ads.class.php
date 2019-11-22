<?php

class Ads {
	var $mTitle = null;
	var $mUser = null;
	var $mParserOptions = null;
	var $mLanguageCode = null;
	var $mEnglishSite = null;
	var $mContext = null;

	// are the ads active on this page
	var $mActive = true;
	var $mDocViewer = false;
	var $mSearchPage = false;
	var $mFundingChoicesActive = false;

	var $mAlternateDomain = null;
	var $mAdCreator = null;

	var $mShowExtraRightRailElements = true;

	static $excluded = false;

	/*
	 * the constructor for the desktop ads class
	 * it will set if ads are active for this page
	 * it will create all the ad units and their html and dfp header code
	 * the class can then be accessed to modify the page for it's body ads
	 * and to get the html for the banner and right rail ads
	 */
	public function __construct( $context, $user, $languageCode, $opts, $isMainPage ) {
		// most of these are used to determine whether or not to show ads
		$this->mTitle = $context->getTitle();
		$this->mContext = $context;
		$this->mUser = $user;
		$this->mLanguageCode = $languageCode;
		$this->mParserOptions = $opts;
		$this->mIsMainPage = $isMainPage;
		$this->initAdsActive();

		if ( !$this->mActive ) {
			return;
		}

		$this->mEnglishSite = $languageCode == "en";
		$this->mAlternateDomain = class_exists( 'AlternateDomain' ) && AlternateDomain::onAlternateDomain();
		$this->mAdCreator = $this->getAdCreator();

		// get the html for all the ads and their dfpunitinfo
		//$this->mAdCreator->setupAdHtml();
		$this->mShowExtraRightRailElements = isset( $this->mAdCreator->mAdServices['rightrail1'] );
	}

	public function isActive() {
		return $this->mActive;
	}

	public function getShowExtraRightRailElements() {
		if ( !$this->mActive ) {
			return true;
		}

		return $this->mShowExtraRightRailElements;
	}

	/*
	 *
	 * @return the html for heading bidding
	 */
	public function getHeadHtml() {
		if ( $this->mActive && $this->mAdCreator ) {
			return $this->mAdCreator->getHeadHtml();
		}
	}

	private static function getKey($languageCode) {
		global $wgCachePrefix;

		return wfForeignMemcKey(Misc::getLangDB($languageCode), $wgCachePrefix, 'adExclusions' );
	}

	public static function isExcluded($title) : bool {
		global $wgLanguageCode, $wgMemc;

		if (self::$excluded) {
			return true;
		}

		if (!$title || !$title->exists()) {
			return false;
		}

		/**
		 * For now we're using memcache to store the array. If we get
		 * over ~2000 articles then we should switch to querying the table
		 * each time rather than storing the whole array.
		 **/
		$key = self::getKey($wgLanguageCode);
		$excludeList = $wgMemc->get($key);
		if (!$excludeList || !is_array($excludeList)) {
			$excludeList = array();

			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select(ArticleAdExclusions::TABLE, "ae_page", array(), __METHOD__);
			foreach ($res as $row) {
				$excludeList[] = $row->ae_page;
			}
			$wgMemc->set($key, $excludeList);
		}

		return in_array($title->getArticleID(), $excludeList);
	}

	/*
	 * determine of ads are active for this page
	 */
	private function initAdsActive() {
		if ( !$this->mUser->isAnon() ) {
			$this->mActive = false;
			return;
		}
		// sanity check on title
		if ( !$this->mTitle ) {
			$this->mActive = false;
			return;
		}

		if ( RobotPolicy::isIndexable( $this->mTitle, $this->mContext ) == false ) {
			$this->mActive = false;
			return;
		}

		// restricted url type
		if ( preg_match("@^/index\.php@", @$_SERVER["REQUEST_URI"]) ) {
			$this->mActive = false;
			return;
		}

		$allowedSpecialPage = false;
		if ( $this->mTitle->isSpecial( 'CategoryListing' )
			|| $this->mTitle->isSpecial( 'DocViewer' )
			|| $this->mTitle->isSpecial( 'Quizzes' )
			|| $this->mTitle->isSpecial( 'LSearch' )
		) {
			$allowedSpecialPage = true;
		}

		if ( $this->mTitle->isSpecial( 'DocViewer' ) ) {
			$this->mDocViewer = true;
		}

		if ( $this->mTitle->isSpecial( 'LSearch' ) ) {
			$this->mSearchPage = true;
		}

		// if not in these namespaces and not an allowed special page
		if ( !$this->mTitle->inNamespaces( NS_MAIN, NS_IMAGE, NS_CATEGORY ) && !$allowedSpecialPage ) {
			$this->mActive = false;
			return;
		}

		// restricted actions
		$action = $this->mContext->getRequest()->getVal('action', 'view');
		if ( $action == 'edit' ) {
			$this->mActive = false;
			return;
		}

		// do not show ads if the no-ads option is set
		if ( isset( $opts['no-ads'] ) && $opts['no-ads'] ) {
			$this->mActive = false;
			return;
		}

		if ( class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest() ) {
			$this->mActive = false;
			return;
		}

		// check for certain restricted titles
		if ( self::isExcluded( $this->mTitle ) ) {
			$this->mActive = false;
			return;
		}

		// no ads on category pages
		if ( $this->mTitle->inNamespace(NS_CATEGORY) ) {
			$this->mActive = false;
			return;
		}

		// no ads on main page
		if ( $this->mIsMainPage	) {
			$this->mActive = false;
			return;
		}

		// check for decline cookie policy cookie
		if ( $this->mIsMainPage	) {
			$this->mActive = false;
			return;
		}

		foreach ($_COOKIE as $name => $val) {
			if ($name == "gdpr_decline" && $val == 1) {
				$this->mActive = false;
				return;
			}
		}
	}

	/*
	 * @param int the right rail ad 0, 1 or 2
	 * @return the html for right rail ad
	 */
	public function getRightRailAdHtml( $position ) {
		if (!$this->mAdCreator) {
			return;
		}
		$ad = $this->mAdCreator->getBodyAd( 'rightrail'.$position );
		if ( !$ad ) {
			return "";
		}
		return $ad->mHtml;
	}

	/*
	 * note - i discovered an annoying and hard to track down bug
	 * in this function, which is if there is javascript in this htmlo
	 * and this javascript has strings which are <div> insertions
	 * then php query seems to get confused making a new document and 
	 * puts a closing script tag too early. this was happening on the right rail
	 * on the sample pages when loggged in because the staff pagestats js
	 * was in the html here and it was screwing it up completely.
	 * now we check if ads are active before running this to solve it
	 * but i could imagine a way that it causes another bug in the future
	 *
	 * due to this bug I am disabling this function until we fix the issue
	 * */
	public function modifyRightRailForAdTest( $html, $relatedWikihows ) {
		if ( !$this->isActive() ) {
			return $html;
		}
		return $html;
	}

	public function getGPTDefine() {
		if (!$this->mAdCreator) {
			return;
		}
		return $this->mAdCreator->getGPTDefine();
	}

	public function modifyForHealthlineTest( $html, $relatedWikihows ) {
		// first two rr elements are already in the html
		$rr3 = $this->getRightRailAdHtml( 3 );
		$html .= $rr3;

		//  add the rr that goes on the top of each method
		for ( $i = 4; $i < 10; $i++ ) {
			$rr = $this->getRightRailAdHtml( $i );
			if ( $rr ) {
				$html .= $rr;
			}
		}

		$doc = phpQuery::newDocument( $html );

		if ( pq( '#sp_stats_sidebox' )->length ) {
			pq( '#sp_stats_sidebox' )->after( pq( '#rightrail0' ) );
		} else if ( pq( '#social_proof_sidebox' )->length ) {
			pq( '#social_proof_sidebox' )->after( pq( '#rightrail0' ) );
		}

		pq( '#rightrail0' )->next()->prependTo( pq( '#rightrail1 .whad' ) );
		pq( '#rightrail0' )->after( pq( '#rightrail1' ) );

		pq( '#side_related_articles' )->prependTo( pq( '#rightrail2 .whad' ) );
		pq( '#rightrail1' )->after( pq( '#rightrail2' ) );

		pq( '#ratearticle_sidebar' )->prependTo( pq( '#rightrail3 .whad' ) );
		pq( '#rightrail2' )->after( pq( '#rightrail3' ) );

		if ( $relatedWikihows ) {
			$relatedWikihowsLarger = $relatedWikihows->getSideDataLarger();
			$attr = ['id' => 'side_related_articles_larger', 'class' => 'sidebox related_articles'];
			$relatedWikihowsLarger = Html::rawElement( 'div', $attr, $relatedWikihowsLarger );
			pq( $relatedWikihowsLarger )->prependTo( pq( '#rightrail4 .whad' ) );
		}

		// now add spacing on the right rail ads
		pq( '.rr_container' )->addClass( 'nofixed' );

		// clear the heights
		pq( '.rr_container' )->attr( 'style', '' );

		$rightRailHtml = $doc->htmlOuter();

		return $rightRailHtml;
	}

	/*
	 * @param int the doc viewer ad
	 * @return the html for doc viewer ad
	 */
	public function getDocViewerAdHtml( $position ) {
		if (!$this->mAdCreator) {
			return;
		}
		$ad = $this->mAdCreator->getBodyAd( 'docviewer'.$position );
		if ( !$ad ) {
			return "";
		}
		return $ad->mHtml;
	}


	/*
	 * determine which ad creator to use
	 */
	private function getAdCreator() {
		global $wgRequest;
		$pageId = $this->mTitle->getArticleID();
		if ( $this->mAlternateDomain == true ) {
			$adCreator = new DefaultAlternateDomainAdCreator();
			return $adCreator;
		}

		if ( $this->mIsMainPage ) {
			$adCreator = new DefaultMainPageAdCreator();
		} elseif ( $this->mTitle->inNamespace(NS_CATEGORY) ) {
			$adCreator = new DefaultCategoryPageAdCreator();
		} elseif ( $this->mDocViewer == true ) {
			$adCreator = new DefaultDocViewerAdCreator();
		} elseif ( $this->mSearchPage == true ) {
			$searchQuery = LSearch::getSearchQuery();
			$adCreator  = new DefaultSearchPageAdCreator( $searchQuery );
			if ( !$this->mEnglishSite ) {
				$adCreator = new DefaultInternationalSearchPageAdCreator( $searchQuery );
			}
		} else {
			$bucket = rand( 1, 20 );
			if ( $bucket == 20 ) {
				$extra = rand( 0, 4 );
				$bucket += $extra;
			}
			if ( $wgRequest && $wgRequest->getInt( 'bucket' ) ) {
				$reqBucket = $wgRequest->getInt( 'bucket' );
				if ( $reqBucket > 0 && $reqBucket <= 24 ) {
					$bucket = $reqBucket;
				}
			}
			$bucketId = sprintf( "%02d", $bucket );
			$adCreator = new DefaultAdCreator( $bucketId );

			if ( !$this->mEnglishSite ) {
				if ( $pageId % 4 == 1 ) {
					$adCreator = new DefaultInternationalAdCreatorAllAdsense();
				} else {
					$adCreator = new DefaultInternationalAdCreator();
				}
				if ( $this->mTitle->isSpecial( 'CategoryListing' ) ) {
					$adCreator = new DefaultIntlCategoryListingAdCreator();
				}
			}
		}

		return $adCreator;
	}

	/*
	 * uses php query to add ads to the body OF the page (which is the context
	 * in which php query is available when this function is called
	 * this function assumes php query has already been initialized on the body of the article
	 */
	public function addToBody() {
		if ( !$this->mActive ) {
			return;
		}
		$this->mAdCreator->insertAdsInBody();
	}

	public function getVideoAdsJavascriptFile() {
		if ( !$this->mActive ) {
			return '';
		}
		return __DIR__ . "/videoads.compiled.js";
	}

	public function getJavascriptFile() {
		if ( !$this->mActive ) {
			return '';
		}
		return __DIR__ . "/ads.js";
	}
}

