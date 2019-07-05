<?php

class DesktopAds {
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
		$this->mAdCreator->setupAdHtml();
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
		if ( wikihowAds::isExcluded( $this->mTitle ) ) {
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

		if ( Misc::isAltDomain() ) {
			$currentDomain = AlternateDomain::getCurrentRootDomain();
			if ( $currentDomain == 'wikihow.mom' ) {
				$this->mActive = false;
				return;
			}
			if ( $currentDomain == 'wikihow.health' ) {
				$this->mActive = false;
				return;
			}
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

	// more connecting code to wikihowAds that can be refactored out later
	private function getTipsDFPUnitParams( $position ) {
		$data = wikihowAds::getUnitParams( 1 );
		if ( $position == "left" ) {
			return $data[0];
		} elseif ( $position == "right" ) {
			return $data[1];
		}
	}

	/*
	 * @param int the righ rail ad 0, 1 or 2
	 * @return the html for right rail ad
	 */
	public function getRightRailAdHtml( $position ) {
		if ( isset( $this->mAdCreator->mAds['rightrail'.$position] ) ) {
			return $this->mAdCreator->mAds['rightrail'.$position]->mHtml;
		}
		return "";
	}

	public function modifyRightRailForAdTest( $html, $relatedWikihows ) {
		$pageId = $this->mTitle->getArticleID();

		$doc = phpQuery::newDocument( $html );
		if ( pq( '.rr_container' )->length < 3 ) {
			pq('#ratearticle_sidebar')->remove();
		}

		$rightRailHtml = $doc->htmlOuter();
		return $rightRailHtml;
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
	 * @return the html for related section ad
	 */
	public function getRelatedAdHtml() {
		$html = '';
		if ( isset( $this->mAdCreator->mAds['related'] ) && $this->mAdCreator->mAds['related'] ) {
			$html = $this->mAdCreator->mAds['related']->mHtml;
		}
		return $html;
	}
	/*
	 * @param int the doc viewer ad
	 * @return the html for doc viewer ad
	 */
	public function getDocViewerAdHtml( $position ) {
		if ( isset( $this->mAdCreator->mAds['docviewer'.$position] ) ) {
			return $this->mAdCreator->mAds['docviewer'.$position]->mHtml;
		}
		return "";
	}

	/*
	 * get the html of the banner ad if it is set
	 */
	public function getBannerAdHtml() {
		if ( $this->mAdCreator ) {
			return $this->mAdCreator->getPreContentAdHtml();
		}
	}

	/*
	 * get the html of the funding choices javscript if ads are active
	 * @return string the html of the funding choices code to go in the head of the page
	 */
	public function getFundingChoicesSnippet() {
		if ( !$this->mActive ) {
			return "";
		}
		if ( !$this->mFundingChoicesActive ) {
			return "";
		}
		$html = file_get_contents( __DIR__."/fundingchoices.html" );
		return $html;
	}

	/*
	 * get the html of the funding choices target div
	 * @return string the html of the funding choices target id
	 */
	public function getFundingChoicesTarget() {
		if ( !$this->mActive ) {
			return "";
		}
		if ( !$this->mFundingChoicesActive ) {
			return "";
		}
		return '<div id="ndcxng"></div>';
	}

	/*
	 * Hook from the WikihowTemplate class that lets us insert html before the action bar
	 */
	public static function onBeforeActionbar( $wgOut, $desktopAds ) {
		if ( $desktopAds ) {
			echo $desktopAds->getBannerAdHtml();
		}
	}

	/*
	 * Hook from the WikihowTemplate class that lets us insert html after the action bar
	 */
	public static function onAfterActionbar( $wgOut, $desktopAds ) {
		echo "";
	}

	/*
	 * determine which ad creator to use
	 */
	private function getAdCreator() {
		$pageId = $this->mTitle->getArticleID();
		if ( $this->mAlternateDomain == true ) {
			$adCreator = new AlternateDomainAdCreator();
			if ( (class_exists("TechLayout") && ArticleTagList::hasTag(TechLayout::CONFIG_LIST, $pageId)) ) {
				 $adCreator->mAdServices['intro'] = '';
			}
			$adCreator->mAdServices['step'] = '';
			$adCreator->setRefreshableRightRail( true );
			$adCreator->setStickyIntro( false );
			$adCreator->setShowRightRailLabel( true );
			$adCreator->setAdLabelVersion( 2 );
			$adCreator->setRightRailAdLabelVersion( 2 );
			return $adCreator;
		}

		if ( RequestContext::getMain()->getRequest()->getInt( "dfpad" ) == 1 ) {
			$adCreator  = new DeprecatedDFPAdCreator();
		} elseif ( $this->mIsMainPage ) {
			$adCreator = new MainPageAdCreator();
		} elseif ( $this->mTitle->inNamespace(NS_CATEGORY) ) {
			$adCreator = new CategoryPageAdCreator();
		} elseif ( $this->mDocViewer == true ) {
			$adCreator = new DocViewerAdCreatorVersion2();
			$adCreator->setRefreshableRightRail( true );
			$adCreator->setShowRightRailLabel( true );
			$adCreator->setAdLabelVersion( 2 );
			$adCreator->setRightRailAdLabelVersion( 2 );
		} elseif ( $this->mSearchPage == true ) {
			$searchQuery = LSearch::getSearchQuery();
			$adCreator  = new SearchPageAdCreator( $searchQuery );
			if ( !$this->mEnglishSite ) {
				$adCreator = new InternationalSearchPageAdCreator( $searchQuery );
			}
			$adCreator->setRefreshableRightRail( true );
			$adCreator->setShowRightRailLabel( true );
			$adCreator->setAdLabelVersion( 2 );
			$adCreator->setRightRailAdLabelVersion( 2 );
		} else {
			$adCreator = new MixedAdCreatorScrollTo();
			$adCreator->mAdServices['step'] = '';

			if ( $pageId % 100 < 95 ) {
				$adCreator = new TwoRightRailAdCreator();
				$adCreator->mAdServices['step'] = '';
			}
			if ( (class_exists("TechLayout") && ArticleTagList::hasTag(TechLayout::CONFIG_LIST, $pageId)) ) {
				 $adCreator->mAdServices['intro'] = '';
			}

			if ( ArticleTagList::hasTag( WikihowToc::CONFIG_LIST_NAME, $pageId ) ) {
				$adCreator->addAdsenseChannel( 4805470868 );
			} else {
				$adCreator->addAdsenseChannel( 3492389196 );
			}

			if ( !$this->mEnglishSite ) {
				if ( $pageId % 4 == 1 ) {
					$adCreator = new InternationalAdCreatorAllAdsense();
				} else {
					$adCreator = new InternationalAdCreator();
				}
				$adCreator->mAdServices['step'] = '';
			}
			// some settings that have become default over time
			// we can refactor them to be the default in the class at construction time
			$adCreator->setRefreshableRightRail( true );
			$adCreator->setStickyIntro( false );
			$adCreator->setShowRightRailLabel( true );
			$adCreator->setAdLabelVersion( 2 );
			$adCreator->setRightRailAdLabelVersion( 2 );
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
		// TODO compiled this js
		return __DIR__ . "/wikihowdesktopads.js";
	}
}

