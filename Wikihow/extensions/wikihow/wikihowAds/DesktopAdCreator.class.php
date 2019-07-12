<?php

class Ad {
	var $mHtml;
	var $mType;
	var $mBodyAd;
	var $mLabel;
	var $service = '';

	public function __construct( $type, $showRightRailLabel = false, $labelExtra = '' ) {
		$this->mType = $type;

		if ( strstr( $this->mType, "rightrail" ) ) {
			$this->mBodyAd = false;
		} else {
			$this->mBodyAd = true;
		}

		$this->mLabel = "";
		if ( $this->mBodyAd || $showRightRailLabel ) {
			$this->mLabel = "ad_label";
			if ( $labelExtra ) {
				$this->mLabel .= " ".$labelExtra;
			}
		}

	}

	public function getLabel() {
		return $this->mLabel;
	}
}

/*
 * default setup for the ad creators
 */
abstract class DesktopAdCreator {
	var $mAds = array();
	var $mShowRightRailLabel = false;
	var $mAdLabelVersion = 1;
	var $mRightRailAdLabelVersion = 1;
	var $mStickyIntro = false;
	var $mDFPKeyVals = array();
	var $mRefreshableRightRail = false;
	var $mAdsenseAutoAds = false;

	public function getPreContentAdHtml() {
		return "";
	}

	protected function getNewAd( $type ) {
		$labelExtra = "";
		$showRRLabel = $this->mShowRightRailLabel;

		if ( strstr( $type, "rightrail" ) ) {
			if ( $this->mRightRailAdLabelVersion > 1 ) {
				$labelExtra = "ad_label_dollar";
			}
		} elseif ( strstr( $type, "intro" ) ) {
			if ( $this->mAdLabelVersion == 2 ) {
				$labelExtra = "ad_label_dollar";
			} elseif ( $this->mAdLabelVersion == 3 ) {
				$labelExtra = "ad_label_none";
			}
		} else {
			if ( $this->mAdLabelVersion > 1 ) {
				$labelExtra = "ad_label_dollar";
			}
		}
		$ad = new Ad( $type, $showRRLabel, $labelExtra );

		return $ad;
	}

	/*
	 * does the right rail have a label
	 * $param boolean
	 */
	public function setShowRightRailLabel( $val ) {
		$this->mShowRightRailLabel = $val;
	}

	/*
	 * intro sticky data attr (to be used client side)
	 * $param boolean
	 */
	public function setStickyIntro( $val ) {
		$this->mStickyIntro = $val;
	}

	/*
	 * extra key value to send in dfp
	 */
	public function setDFPKeyValue( $slot, $key, $val ) {
		$this->mDFPKeyVals[$slot][$key] = $val;
	}

	/*
	 * get json string of the dfp key vals for use in js
	 */
	public function getDFPKeyValsJSON() {
		$dfpKeyVals = $this->mDFPKeyVals;

		// the default value of this always present key val pair
		if ( $this->mRefreshableRightRail ) {
			$dfpKeyVals['/10095428/RR3_Test_32']['refreshing'] = '1';
			$dfpKeyVals['/10095428/Refreshing_Ad_RR1_Test']['refreshing'] = '1';
			$dfpKeyVals['/10095428/RR3_DFP_Test']['refreshing'] = '1';
		} else {
			$dfpKeyVals['/10095428/RR3_Test_32']['refreshing'] = 'not';
			$dfpKeyVals['/10095428/Refreshing_Ad_RR1_Test']['refreshing'] = 'not';
			$dfpKeyVals['/10095428/RR3_DFP_Test']['refreshing'] = '1';
		}

		$dfpKeyVals = json_encode( $dfpKeyVals );
		return $dfpKeyVals;
	}

	public function getSticky( $ad ) {
		if ( $ad->mType == 'intro' && $this->mStickyIntro == true ) {
			return true;
		}

		return false;
	}

	public function setRefreshableRightRail( $val ) {
		$this->mRefreshableRightRail = $val;
	}

	public function getRefreshable( $ad ) {
		if ( $ad->service == 'dfp' && strstr( $ad->mType, "rightrail2" ) && $this->mRefreshableRightRail ) {
			return true;
		}
		return false;
	}

	public function getRenderRefresh( $ad ) {
		if ( $ad->service == 'dfp' && strstr( $ad->mType, "rightrail2" ) && $this->mRefreshableRightRail ) {
			return true;
		}
		return false;
	}

	public function getViewableRefresh( $ad ) {
		return false;
	}

	public function getIsLastAd( $ad ) {
		if ( $ad->mType == "rightrail2" ) {
			return true;
		}
		return false;
	}

	/*
	 * what type of ad label to appear above ads
	 * $param integer version of ad label
	 */
	public function setAdLabelVersion( $type ) {
		$this->mAdLabelVersion = $type;
	}

	/*
	 * what type of ad label to appear above right rail ads
	 * $param integer version of ad label
	 */
	public function setRightRailAdLabelVersion( $type ) {
		$this->mRightRailAdLabelVersion = $type;
	}

	public function setTopCategory( $topCategory ) {

	}

	/*
	 * gets the ad data for all ads on the page
	 * also requires php query to be initalized for the body of the page
	 */
	abstract public function setupAdHtml();

	/*
	 * uses php query to put the ad html into the body of the page
	 */
	abstract public function insertAdsInBody();

	/*
	 *	any html or js that goes in the head of our page to support this ad setup
	 */
	abstract public function getHeadHtml();
}


/*
 * default desktop ad creator extends from a base class which is used also by older code
 * or else this would be the true base class and when we remove that code this will be
 */
abstract class DefaultDesktopAdCreator extends DesktopAdCreator {
	public function __construct() {
	}


	/*
	 * gets the ad data for all ads on the page
	 * also requires php query to be initalized for the body of the page
	 * or else it will not do anything
	 * we only get the intro and right rail units
	 */
	public function setupAdHtml() {
		if ( !phpQuery::$defaultDocumentID )  {
			return;
		}
		$this->mAds['intro'] = $this->getIntroAd();
		// todo iterate over mAdSetupData here instead of for loop
		for ( $i = 0; $i < 10; $i++ ) {
			if ( $i <= 2 || isset( $this->mAdSetupData['rightrail'.$i] ) ) {
				$this->mAds['rightrail'.$i] = $this->getRightRailAd( $i );
			}
		}
		$this->mAds['step'] = $this->getStepAd();
		$this->mAds['method'] = $this->getMethodAd();
		$this->mAds['method2'] = $this->getMethod2Ad();
		$this->mAds['method3'] = $this->getMethod3Ad();
		for ( $i = 0; $i < pq('.qz_container')->length; $i++ ) {
			$this->mAds['quiz'.$i] = $this->getQuizAd( $i ); //taking ads off quizzes temporairily
		}
		$this->mAds['related'] = $this->getRelatedAd();
		$this->mAds['scrollto'] = $this->getScrollToAd();

	}

	/*
	 * creates the related Ad
	 */
	abstract public function getScrollToAd();

	/*
	 * creates the related Ad
	 */
	abstract public function getRelatedAd();

	/*
	 * creates the quiz Ads
	 */
	abstract public function getQuizAd( $num );

	/*
	 * creates the intro Ad
	 */
	abstract public function getIntroAd();

	/*
	 * creates the step Ad
	 */
	abstract public function getStepAd();

	/*
	 * creates a right rail ad based on the right rail position for this ad implementation
	 * @param Integer the right rail number or position on the page usually 0 1 or 2
	 * @return Ad an ad for the right rail
	 */
	abstract public function getRightRailAd( $num );

	/*
	 * uses php query to put the ad html into the body of the page
	 */
	public function insertAdsInBody() {
		// make sure we have php query object
		if ( !phpQuery::$defaultDocumentID )  {
			return;
		}

		$stepAd = $this->mAds['step']->mHtml;
		if ( $stepAd && pq( ".steps_list_2 > li:eq(0)" )->length() ) {
			pq( ".steps_list_2 > li:eq(0)" )->append( $stepAd );
		}

		$methodAd = $this->mAds['method']->mHtml;
		if ( $methodAd ) {
			if ( pq( ".steps_list_2:first > li" )->length > 2 && pq( ".steps_list_2:first > li:last-child)" )->length() ) {
				pq( ".steps_list_2:first > li:last-child" )->append( $methodAd );
			} else {
				$this->mAds['method']->notInBody = true;
			}
		}

		$method2Ad = $this->mAds['method2']->mHtml;
		if ( $method2Ad ) {
			$count = pq( ".steps_list_2" )->length;
			if ( $count < 2 ) {
				$this->mAds['method2']->notInBody = true;
			} else {
				$count = 2;
			}
			for ( $i = 1; $i < $count; $i++ ) {
				if ( pq( ".steps_list_2:eq($i) > li" )->length > 2 && pq( ".steps_list_2:eq($i) > li:last-child)" )->length() ) {
					$method2Ad = "<div id='tocad_wrap'>".$method2Ad."</div>";
					pq( ".steps_list_2:eq($i) > li:last-child" )->append( $method2Ad );
				}
			}
		}
		$method3Ad = $this->mAds['method3']->mHtml;
		if ( $method3Ad ) {
			$count = pq( ".steps_list_2" )->length;
			for ( $i = 1; $i < $count; $i++ ) {
				if ( pq( ".steps_list_2:eq($i) > li" )->length > 2 && pq( ".steps_list_2:eq($i) > li:last-child)" )->length() ) {
					pq( ".steps_list_2:eq($i) > li:last-child" )->append( $method3Ad );

				}
			}
		}

		$introHtml = $this->mAds['intro']->mHtml;
		if ( $introHtml ) {
			pq( "#intro" )->append( $introHtml )->addClass( "hasad" );
		}

		for ( $i = 0; $i < pq( '.qz_container' )->length; $i++ ) {
			$quizHtml = $this->mAds['quiz'.$i]->mHtml;
			if ( $quizHtml ) {
				pq( '.qz_container' )->eq($i)->append( $quizHtml );
			}
		}

		$scrollToHtml = $this->mAds['scrollto']->mHtml;
		if ($scrollToHtml) {
			pq( "#intro" )->after( $scrollToHtml );
		}

	}
}

class MixedAdCreator extends DefaultDesktopAdCreator {
	var $mAdsenseSlots = array();
	var $mDFPData = array();
	var $mRightRailLabel = false;
	var $mLateLoadDFP = false;
	var $mAdsenseChannels = array();

	public function __construct() {
		$this->mAdsenseSlots = array(
			'intro' => 7862589374,
			'rightrail0' => 4769522171,
		);
		$this->mAdServices = array(
			'intro' => 'adsense',
			'rightrail0' => 'adsense',
			'rightrail1' => 'dfp',
			'rightrail2' => 'dfp'
		);
	}

	/*
	 * required by any dfp classes to set the ad unit paths
	 */
	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'rightrail1' => array(
				'adUnitPath' => '/10095428/RR2_Test_32',
				'size' => '[300, 600]',
				'apsLoad' => false
			),
			'rightrail2' => array(
				'adUnitPath' => '/10095428/RR3_Test_32',
				'size' => '[300, 600]',
				'apsLoad' => false
			),
			'quiz' => array(
				'adUnitPath' => '/10095428/AllPages_Quiz_English_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => false
			),
		);
	}

	public function enableLateLoadDFP() {
		$this->mLateLoadDFP = true;
	}

	/*
	 * @param Ad
	 * @return int the adsense slot for this ad
	 */
	protected function getAdsenseSlot( $ad ) {
		if ( !isset( $this->mAdsenseSlots ) ) {
			return '';
		}
		if ( !isset( $this->mAdsenseSlots[$ad->mType] ) ) {
			return '';
		}
		return $this->mAdsenseSlots[$ad->mType];
	}

	/*
	 * @param Ad
	 * @return string the dfp light path for this ad
	 */
	protected function getDFPLightAdUnitPath( $ad ) {
		if ( !isset( $this->mDFPAdUnitPaths ) ) {
			return '';
		}
		return $this->mDFPAdUnitPaths[$ad->mType];
	}

	/*
	 * @param Ad
	 * @return string or int channels to be used when creating adsense ad
	 */
	protected function getAdsenseChannels( $ad ) {
		return implode( ',', $this->mAdsenseChannels );
	}

	public function getAdsenseAutoAds() {
		return $this->mAdsenseAutoAds;
	}

	public function setAdsenseAutoAds( $value ) {
		$this->mAdsenseAutoAds = $value;
	}

	// TODO this function should be removed and then we required the channels to be set
	// at the beginning of the constructor instead. that way we can define the channels
	// for the ads in the mAdSetupData
	public function addAdsenseChannel( $channel ) {
		$this->mAdsenseChannels[] = $channel;
	}

	/*
	 * @param Ad
	 * @return string or int channels to be used when creating adsense ad
	 */
	protected function getAdClient( $ad ) {
		return 'ca-pub-9543332082073187';
	}

	/*
	 * return the abg snippet, not wrapped in a script tag
	 * @return string a snippet of javasript used to insert an adsense ad on an <ins> element
	 */
	protected function getAdsByGoogleJS( $ad ) {
		$channels = $this->getAdsenseChannels( $ad );
		$adsenseAutoAds = $this->getAdsenseAutoAds();
		$script = "(adsbygoogle = window.adsbygoogle || []).push({";
		if ( $channels ) {
			$script .= "params: {google_ad_channel: '$channels'}";
		}
		if ( $adsenseAutoAds ) {
			$script .= "google_ad_client: \"ca-pub-9543332082073187\",\n";
			$script .= "enable_page_level_ads: true";
		}
		$script .= "});";
		return $script;
	}

	protected function getIntroAdDFP() {
		$ad = $this->getNewAd( 'intro' );
		$ad->targetId = 'introad';
		$ad->outerId = 'introad-outer';
		$ad->service = "dfp";
		$ad->width = 728;
		$ad->height = 90;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	protected function getIntroAdDFPLight() {
		$ad = $this->getNewAd( 'intro' );
		$ad->targetId = 'introad-outer';
		$ad->outerId = 'introad-outer';
		$ad->adClass = $ad->getLabel();
		$ad->service = "dfplight";
		$ad->width = 728;
		$ad->height = 90;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	protected function getIntroAdAdsense() {
		$ad = $this->getNewAd( 'intro' );
		if ( $this->mAdLabelVersion == 3 ) {
			$ad->adClass = "wh_ad_inner_nolabel";
		}
		$ad->targetId = 'introad';
		$ad->outerId = 'introad-outer';
		$ad->service = "adsense";
		$ad->width = 728;
		$ad->height = 120;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}
	/*
	 * creates the intro Ad
	 */
	public function getIntroAd() {
		$ad = $this->getNewAd( 'intro' );
		if ( $this->mAdServices['intro'] == "adsense" ) {
			$ad = $this->getIntroAdAdsense();
		} elseif ( $this->mAdServices['intro'] == "dfp" ) {
			$ad = $this->getIntroAdDFP();
		} elseif ( $this->mAdServices['intro'] == "dfplight" ) {
			$ad = $this->getIntroAdDFPLight();
		} else {
			return $ad;
		}
		$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addIntroAd('{$ad->outerId}')" );
		return $ad;
	}

	protected function getStepAdAdsense() {
		$ad = $this->getNewAd( 'step' );
		$ad->mLabel = '';
		$ad->service = "adsense";
		$ad->adClass = "step_ad";
		$ad->width = 728;
		$ad->height = 90;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	protected function getMethodAdAdsense() {
		$ad = $this->getNewAd( 'method' );
		$ad->mLabel = '';
		$ad->service = "adsense";
		$ad->adClass = "step_ad";
		$ad->width = 728;
		$ad->height = 90;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	protected function getMethodAdDFP() {
		$ad = $this->getNewAd( 'method' );
		$ad->mLabel = '';
		$ad->service = "dfp";
		$ad->adClass = "step_ad";
		$ad->targetId = "methodad";
		$ad->width = 728;
		$ad->height = 90;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	protected function getMethod2AdAdsense() {
		$ad = $this->getNewAd( 'method2' );
		$ad->mLabel = '';
		$ad->service = "adsense";
		$ad->adClass = "step_ad";
		$ad->targetId = "tocad";
		$ad->outerId = "tocad_outer";
		$ad->width = 728;
		$ad->height = 90;
		$ad->initialLoad = false;
		$ad->lateLoad = true;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	protected function getMethod2AdDFP() {
		$ad = $this->getNewAd( 'method2' );
		$ad->mLabel = '';
		$ad->service = "dfp";
		$ad->adClass = "step_ad";
		$ad->targetId = "method2ad";
		$ad->width = 728;
		$ad->height = 90;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	/*
	 * creates the step Ad
	 */
	public function getStepAd() {
		$ad = $this->getNewAd( 'step' );
		if ( $this->mAdServices['step'] == "adsense" ) {
			$ad = $this->getStepAdAdsense();
		}
		return $ad;
	}

	/*
	 * creates the method Ad
	 */
	public function getMethodAd() {
		$ad = $this->getNewAd( 'method' );
		if ( $this->mAdServices['method'] == "adsense" ) {
			$ad = $this->getMethodAdAdsense();
		} elseif ( $this->mAdServices['method'] == "dfp" ) {
			$ad = $this->getMethodAdDFP();
		}
		if ( $ad->targetId ) {
			$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addBodyAd('{$ad->targetId}')" );
		}
		return $ad;
	}

	/*
	 * creates the method2 Ad
	 */
	public function getMethod2Ad() {
		$ad = $this->getNewAd( 'method2' );
		if ( $this->mAdServices['method2'] == "adsense" ) {
			$ad = $this->getMethod2AdAdsense();
		} elseif ( $this->mAdServices['method2'] == "dfp" ) {
			$ad = $this->getMethod2AdDFP();
		}
		if ( $ad->targetId ) {
			$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addTOCAd('{$ad->targetId}')" );
		}
		return $ad;
	}

	/*
	 * creates the method2 Ad
	 */
	public function getMethod3Ad() {
		$ad = $this->getNewAd( 'method3' );
		if ( !isset( $this->mAdServices['method3'] ) ) {
			return $ad;
		}
		if ( $this->mAdServices['method3'] == "adsense" ) {
			$ad = $this->getMethodAdAdsense();
		} elseif ( $this->mAdServices['method3'] == "dfp" ) {
			$ad = $this->getMethodAdDFP();
		}
		if ( $ad->targetId ) {
			$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addBodyAd('{$ad->targetId}')" );
		}
		return $ad;
	}

	/*
	 * @return Ad an ad for the first right rail
	 */
	protected function getRightRailFirstAdsense() {
		global $wgTitle;
		$pageId = 0;
		if ( $wgTitle ) {
			$pageId = $wgTitle->getArticleID();
		}
		$ad = $this->getNewAd( 'rightrail0' );
		$ad->service = "adsense";
		$ad->targetId = $ad->mType;
		$ad->containerHeight = 2000;
		$ad->initialLoad = true;
		if ( $pageId % 20 == 0 ) {
			$ad->initialLoad = false;
		}
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	/*
	 * @return Ad an ad for the second right rail
	 */
	protected function getRightRailSecondAdsense() {
		$ad = $this->getNewAd( 'rightrail1' );
		$ad->service = "adsense";
		$ad->targetId = $ad->mType;
		$ad->containerHeight = 3300;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	/*
	 * @return Ad an ad for the third right rail
	 */
	protected function getRightRailThirdAdsense() {
		$ad = $this->getNewAd( 'rightrail2' );
		$ad->service = "adsense";
		$ad->targetId = $ad->mType;
		$ad->containerHeight = 2000;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	/*
	 * @return Ad an ad for the third right rail
	 */
	protected function getRightRailTopAdsense() {
		$ad = $this->getNewAd( 'rightrail3' );
		$ad->service = "adsense";
		$ad->targetId = $ad->mType;
		$ad->containerHeight = 250;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 250;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	/*
	 * @return Ad a dfp ad for the first right rail
	 */
	protected function getRightRailFirstDFP() {
		$ad = $this->getNewAd( 'rightrail0' );
		$ad->service = "dfp";
		$ad->targetId = 'div-gpt-ad-1492454101439-0';
		$ad->containerHeight = 2000;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	/*
	 * gets the html fo the second right rail ad using dfp
	 * @return Ad an ad for the second right rail
	 */
	protected function getRightRailSecondDFP() {
		$ad = $this->getNewAd( 'rightrail1' );
		$ad->service = "dfp";
		$ad->targetId = 'div-gpt-ad-1492454171520-0';
		$ad->containerHeight = 3300;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	/*
	 * @return Ad an ad for the third right rail
	 */
	protected function getRightRailThirdDFP() {
		$ad = $this->getNewAd( 'rightrail2' );
		$ad->service = "dfp";
		$ad->targetId = 'div-gpt-ad-1492454222875-0';
		$ad->containerHeight = 2000;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	/*
	 * @return Ad an ad for the third right rail
	 */
	protected function getRightRailNumDFP( $num ) {
		$ad = $this->getNewAd( 'rightrail'.$num );
		$ad->service = "dfp";
		$ad->targetId = 'rightrail-ad-target'.$num;
		$ad->containerHeight = 2000;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}

	protected function getDFPInnerHtml( $ad ) {
		$script = "";
		if ( $ad->lateLoad == false ) {
			$script = "googletag.cmd.push(function() { googletag.display('$ad->targetId'); });";
			$script = Html::inlineScript( $script );
		}
		$class = array();
		if ( $ad->getLabel() ) {
			$class[] = $ad->getLabel();
		}
		$attributes = array(
			'id' => $ad->targetId,
			'class' => $class
		);
		$html = Html::element( 'div', $attributes );
		return $html . $script;
	}

	protected function getAdsenseInnerHtml( $ad ) {
		// only get the inner html if initial load is true
		if ( $ad->initialLoad == false ) {
			if ($ad->targetId != "tocad") {
				return "";
			}
			$attributes = array(
				'id' => $ad->targetId,
				'class' => $ad->adClass
			);
			$adTargetDiv = Html::element( "div", $attributes );
			return $adTargetDiv;
		}
		$class = array( 'adsbygoogle' );
		if ( $ad->getLabel() ) {
			$class[] = $ad->getLabel();
		}

		$attributes = array(
			'class' => $class,
			'style' => "display:inline-block;width:".$ad->width."px;height:".$ad->height."px;",
			'data-ad-client' => $this->getAdClient( $ad ),
			'data-ad-slot' => $this->getAdsenseSlot( $ad )
		);
		$ins = Html::element( "ins", $attributes );
		$script = $this->getAdsByGoogleJS( $ad );
		$script = Html::inlineScript( $script );
		return $ins . $script;
	}

	protected function getAdInnerHtml( $ad ) {
		if ( $ad->service == "dfp" ) {
			return $this->getDFPInnerHtml( $ad );
		} elseif ( $ad->service == "adsense" ) {
			return $this->getAdsenseInnerHtml( $ad );
		} else {
			return "";
		}
	}

	/*
	 * get the html of a body  ad. works for both dfp and adsense
	 *
	 * @param Ad
	 * @return string html of the body ad
	 */
	protected function getBodyAdHtml( $ad ) {
		// we may have inner ad html depending on the ad loading setting
		$innerAdHtml = $this->getAdInnerHtml( $ad );

		$attributes = array(
			'class' => array( 'wh_ad_inner' ),
			'data-service' => $ad->service,
			'data-adtargetid' => $ad->targetId,
			'data-loaded' => $ad->initialLoad ? 1 : 0,
			'data-lateload' => $ad->lateLoad ? 1 : 0,
			'data-adsensewidth' => $ad->width,
			'data-adsenseheight' => $ad->height,
			'data-slot' => $this->getAdsenseSlot( $ad ),
			'data-adunitpath' => $this->getDFPLightAdUnitPath( $ad ),
			'data-channels' => $this->getAdsenseChannels( $ad ),
			'data-sticky' => $this->getSticky( $ad ),
			'data-refreshable' => $this->getRefreshable( $ad ),
			'data-renderrefresh' => $this->getRenderRefresh( $ad ),
			'data-viewablerefresh' => $this->getViewableRefresh( $ad ),
			'data-apsload' => $this->getApsLoad( $ad ),
			'data-aps-timeout' => 2000,
		);
		if ( isset( $ad->outerId ) ) {
			$attributes['id'] = $ad->outerId;
		}
		if ( isset ($this->mAdSetupData[$ad->mType] ) ) {
			$extras = $this->mAdSetupData[$ad->mType];
			if ( $extras ) {
				foreach ( $extras as $key => $val ) {
					$adKey = 'data-'.$key;
					$attributes[$adKey] = $val;
				}
			}
		}
		if ( isset( $ad->adClass ) && $ad->adClass ) {
			$attributes['class'][] = $ad->adClass;
		}
		$elem = 'div';
		if ( isset( $ad->wrapElement ) && $ad->wrapElement ) {
			$elem = $ad->wrapElement;
		}
		$html = Html::rawElement( $elem, $attributes, $innerAdHtml );
		if ( $elem == 'div' ) {
			$html .= Html::element( 'div', ['class' => 'clearall adclear'] );
		}
		return $html;
	}

	/*
	 * get the html of the right rail ad. works for both dfp and adsense
	 *
	 * @param Ad an ad with service target id and initial load etc defined
	 * @return string html of the  right rail ad
	 */
	protected function getRightRailAdHtml( $ad ) {
		// we may have inner ad html depending on the ad loading setting
		$innerAdHtml = $this->getAdInnerHtml( $ad );

		$attributes = array(
			'class' => 'whad',
			'data-service' => $ad->service,
			'data-adtargetid' => $ad->targetId,
			'data-loaded' => $ad->initialLoad ? 1 : 0,
			'data-lateload' => $ad->lateLoad ? 1 : 0,
			'data-adsensewidth' => $ad->width,
			'data-adsenseheight' => $ad->height,
			'data-slot' => $this->getAdsenseSlot( $ad ),
			'data-channels' => $this->getAdsenseChannels( $ad ),
			'data-refreshable' => $this->getRefreshable( $ad ),
			'data-renderrefresh' => $this->getRenderRefresh( $ad ),
			'data-viewablerefresh' => $this->getViewableRefresh( $ad ),
			'data-apsload' => $this->getApsLoad( $ad ),
			'data-aps-timeout' => 2000,
			'data-lastad' => $this->getIsLastAd( $ad ),
		);


		// add any extra data attributes defined for this adCreator instance
		if ( isset( $this->mAdSetupData ) && isset( $this->mAdSetupData[$ad->mType] ) ) {
			$extras = $this->mAdSetupData[$ad->mType];
			if ( $extras ) {
				foreach ( $extras as $key => $val ) {
					$adKey = 'data-'.$key;
					$attributes[$adKey] = $val;
				}
			}
		}

		$html = Html::rawElement( 'div', $attributes, $innerAdHtml );

		$containerAttributes = array(
			'id' => $ad->mType,
			'style' => "height:{$ad->containerHeight}px",
			'class' => 'rr_container',
			'data-position' => 'aftercontent'
		);
		return Html::rawElement( 'div', $containerAttributes, $html );
	}

	/*
	 * @return Ad an ad for the first right rail
	 */
	public function getRightRailFirst() {
		$ad = $this->getNewAd( 'rightrail0' );
		// for now only adsense supported for intro
		if ( $this->mAdServices['rightrail0'] == "adsense" ) {
			$ad = $this->getRightRailFirstAdsense();
		} elseif ( $this->mAdServices['rightrail0'] == "dfp" ) {
			$ad = $this->getRightRailFirstDFP();
		}
		return $ad;
	}

	/*
	 * @return Ad an ad for the first right rail
	 */
	public function getRightRailSecond() {
		$ad = $this->getNewAd( 'rightrail1' );
		if ( $this->mAdServices['rightrail1'] == "adsense" ) {
			$ad = $this->getRightRailSecondAdsense();
		} elseif ( $this->mAdServices['rightrail1'] == "dfp" ) {
			$ad = $this->getRightRailSecondDFP();
		}
		return $ad;
	}

	/*
	 * @return Ad an ad for the third right rail
	 */
	public function getRightRailThird() {
		$ad = $this->getNewAd( 'rightrail2' );
		if ( $this->mAdServices['rightrail2'] == "adsense" ) {
			$ad = $this->getRightRailThirdAdsense();
		} elseif ( $this->mAdServices['rightrail2'] == "dfp" ) {
			$ad = $this->getRightRailThirdDFP();
		}
		return $ad;
	}

	public function getRightRailNum( $num ) {
		$ad = $this->getNewAd( 'rightrail'.$num );
		if ( $this->mAdServices['rightrail'.$num] == "adsense" ) {
			$ad = $this->getRightRailThirdAdsense();
		} elseif ( $this->mAdServices['rightrail'.$num] == "dfp" ) {
			$ad = $this->getRightRailNumDFP( $num );
		}
		return $ad;
	}

	/*
	 * @return Ad an ad for the top right rail
	 */
	public function getRightRailTop() {
		$ad = $this->getNewAd( 'rightrail3' );
		if ( !isset( $this->mAdServices ) || !isset( $this->mAdServices['rightrail3'] ) ) {
			return $ad;
		}
		if ( $this->mAdServices['rightrail3'] == "adsense" ) {
			$ad = $this->getRightRailTopAdsense();
		}
		return $ad;
	}

	/*
	 * creates a right rail ad based on the right rail position for this ad implementation
	 * @param Integer the right rail number or position on the page usually 0 1 or 2
	 * @return Ad an ad for the right rail but no html
	 */
	public function getRightRailAd( $num ) {
		$type = "rightrail".$num;
		$ad = $this->getNewAd( $type );
		if ( $num == 0 ) {
			$ad = $this->getRightRailFirst();
		} elseif ( $num == 1 ) {
			$ad = $this->getRightRailSecond();
		} elseif ( $num == 2 ) {
			$ad = $this->getRightRailThird();
		} elseif ( $num >= 3 ) {
			$ad = $this->getRightRailNum( $num );
		}
		// now ad the js snippet to add the ad to the js sroll handler
		if ( $ad && $ad->mHtml ) {
			$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addRightRailAd('{$ad->mType}')" );
		}

		return $ad;
	}

	/*
	 * creates the scrollto Ad
	 */
	public function getScrollToAd() {
		return "";
	}

	/*
	 * creates the related Ad
	 */
	public function getRelatedAd() {
		return "";
	}

	/*
	 * creates the related Ad
	 */
	public function getRelatedAdDFP() {
		$ad = $this->getNewAd( 'related' );
		$ad->service = "dfp";
		$ad->targetId = 'related_ad';
		$ad->adClass = "related-article";
		$ad->mLabel = "";
		$ad->width = 342;
		$ad->height = 184;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		return $ad;
	}

	/*
	 * creates the quiz Ad
	 */
	public function getQuizAd( $num ) {
		$ad = $this->getNewAd( 'quiz' );
		$ad->service = "dfp";
		$ad->targetId = 'quizad'.$num;
		$ad->initialLoad = false;
		$ad->lateLoad = false;
		$ad->adClass = "hidden";
		$ad->mHtml = $this->getBodyAdHtml( $ad );
		$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addQuizAd('{$ad->targetId}')" );
		return $ad;
	}

	protected function getInitialRefreshSnippetGPT() {
		//get initial ad refresh slots snippet to request them both in one call
		$refreshSlots = array();
		foreach ( $this->mAds as $type => $ad ) {
			if (  !( $ad ) ) {
				continue;
			}
			if ( $ad->service != 'dfp' ) {
				continue;
			}
			if ( !$ad->initialLoad ) {
				continue;
			}
			if ( $this->getApsLoad( $ad ) ) {
				continue;
			}
			$id = $ad->targetId;
			$refreshSlots[] = "gptAdSlots['$id']";
		}
		if ( !count( $refreshSlots ) ) {
			return "";
		}
		$refreshParam = implode( ",", $refreshSlots );
		$dfpTargeting = '';
		foreach ( $refreshSlots as $slot ) {
			$dfpTargeting .= 'setDFPTargeting('.$slot.', dfpKeyVals);';
		}
		return Html::inlineScript("googletag.cmd.push(function() {{$dfpTargeting}googletag.pubads().refresh([$refreshParam]);});");
	}

	// returns a javascript snippet of the ad unit path
	// instead of just a string to prevetn google bot from crawling these paths
	protected function getGPTAdSlot( $ad ) {
		$adUnitPath = $this->mDFPData[$ad->mType]['adUnitPath'];
		$adUnitPath = str_replace( "/", "|", $adUnitPath);
		$adUnitPath = "'".$adUnitPath . "'.replace(/\|/g,'/')";
		return $adUnitPath;
	}

	private function getGPTAdSize( $ad ) {
		$adSize = $this->mDFPData[$ad->mType]['size'];
		return $adSize;
	}

	protected function getApsLoad( $ad ) {
		if ( !isset( $this->mDFPData[$ad->mType]['apsLoad'] ) ) {
			return false;
		}
		return $this->mDFPData[$ad->mType]['apsLoad'];
	}

	/*
	 * get js snippet to refresh the first set of ads in a single call
	 */
	protected function getInitialRefreshSnippetApsLoad() {
		$slotIds = array();
		$apsSlots = array();
		foreach ( $this->mAds as $type => $ad ) {
			if ( !$ad ) {
				continue;
			}
			if ( $ad->service != 'dfp' ) {
				continue;
			}
			if ( !$ad->initialLoad ) {
				continue;
			}
			if ( !$this->getApsLoad( $ad ) ) {
				continue;
			}
			$slotId = $ad->targetId;
			$slotIds[] = "'".$slotId."'";
			$slotName = $this->getGPTAdSlot( $ad );
			$slotSizes = $this->getGPTAdSize( $ad );
			$apsSlots[] = "{slotID: '$slotId', slotName: $slotName, sizes: $slotSizes}";
		}
		if ( !count( $slotIds ) ) {
			return "";
		}
		$apsSlots = "[" . implode( ",", $apsSlots ) . "]";
		$gptSlotIds = "[" . implode( ",", $slotIds ) . "]";
		// TODO get the timeout time from the ad itself
		$html = Html::inlineScript("googletag.cmd.push(function(){WH.desktopAds.apsFetchBids($apsSlots, $gptSlotIds, 2000);});");
		return $html;
	}

	/*
	 * get js snippet to refresh the first set of ads in a single call
	 */
	protected function getInitialRefreshSnippet() {
		$html = $this->getInitialRefreshSnippetApsLoad();
		$html .= $this->getInitialRefreshSnippetGPT();
		return $html;
	}

	/*
	 * set up the ads html and data
	 */
	public function setupAdHtml() {
		$this->setDFPAdUnitPaths();

		parent::setupAdHtml();

		if ( $this->mAds ) {
			// after the first right rail ad, we append the initial refresh all to DFP ads
			$this->mAds['rightrail0']->mHtml .= $this->getInitialRefreshSnippet();
		}
	}

	/*
	 * gets script for adsense and dfp
	 * @return string html for head
	 */
	public function getHeadHtml() {
		$addAdsense = false;
		$addDFP = false;
		foreach ( $this->mAds as $ad ) {
			if ( !$ad ) {
				continue;
			}

			if ( $ad->service == "adsense" ) {
				$addAdsense = true;
			}

			if ( $ad->service == "dfp" ) {
				$addDFP = true;
			}
		}

		$adsenseScript = "";
		if ( $addAdsense ) {
			$adsenseScript = file_get_contents( __DIR__."/desktopAdsense.js" );
			$adsenseScript = Html::inlineScript( $adsenseScript );
		}

		$dfpScript = "";
		if ( $addDFP ) {
			$dfpScript = $this->getGPTDefine();
			if ( $this->mLateLoadDFP == false ) {
				$dfpScript .= '<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>';
				$dfpInit = file_get_contents( __DIR__."/desktopDFP.js" );
				$dfpScript .= Html::inlineScript( $dfpInit );

				$apsLoadOk = false;
				foreach ( $this->mAds as $type => $ad ) {
					if ( $this->getApsLoad( $ad ) ) {
						$apsLoadOk = true;
						break;
					}
				}
				if ( $apsLoadOk ) {
					$apsInit = file_get_contents( __DIR__."/desktopAPSInit.js" );
					$dfpScript .= Html::inlineScript( $apsInit );
				}
			}
		}

		$adLabelStyle = $this->getAdLabelStyle();

		return $adsenseScript . $dfpScript . $adLabelStyle;
	}

	protected function getAdLabelStyle() {
		// get any extra css for ad labels.. the text or the font size etc
		$labelClassName = ".ad_label";
		$labelText = $this->getAdLabelText();
		$css = "{$labelClassName}:before{content:'$labelText';}";
		$css = Html::inlineStyle( $css );
		return $css;
	}

	protected function getAdLabelText() {
		$labelText = wfMessage( 'ad_label' );
		return $labelText;
	}

	protected function getGPTDefine() {
		$dfpKeyVals = $this->getDFPKeyValsJSON();
		$gpt = "var gptAdSlots = [];\n";
		$gpt .= "var dfpKeyVals = $dfpKeyVals;\n";
		$gpt .= "var googletag = googletag || {};\n";
		$gpt .= "googletag.cmd = googletag.cmd || [];\n";
		$gpt .= "var gptRequested = false;\n";
		$gpt .= "function defineGPTSlots() {\n";

		// define all the slots up front
		foreach ( $this->mAds as $type => $ad ) {
			if ( !$ad ) {
				continue;
			}
			if ( $ad->service != 'dfp' ) {
				continue;
			}
			if ( isset( $ad->notInBody ) && $ad->notInBody ) {
				continue;
			}
			$adUnitPath = $this->getGPTAdSlot( $ad );
			$adSize = $this->getGPTAdSize( $ad );
			$adId = $ad->targetId;
			$gpt .= "gptAdSlots['$adId'] = googletag.defineSlot(".$adUnitPath.", $adSize, '$adId').addService(googletag.pubads());\n";
		}

		$gpt .= "googletag.pubads().enableSingleRequest();\n";
		$gpt .= "googletag.pubads().disableInitialLoad();\n";
		$gpt .= "googletag.pubads().collapseEmptyDivs();\n";
		$gpt .= "googletag.enableServices();\n";

		$gpt .= "}\n";
		$result = Html::inlineScript( $gpt );
		return $result;
	}
}

class MainPageAdCreator extends MixedAdCreator {
	public function __construct() {
		$this->mAdsenseSlots = array(
			'rightrail0' => 6166713376,
		);
		$this->mAdServices = array(
			'rightrail0' => 'adsense',
		);
	}

	public function setupAdHtml() {
		// this ad setup only has a single right rail ad
		$this->mAds['rightrail0'] = $this->getRightRailAd( 0 );
	}

	/*
	 * @return Ad an ad for the first right rail
	 */
	protected function getRightRailFirstAdsense() {
		$ad = $this->getNewAd( 'rightrail0' );
		$ad->service = "adsense";
		$ad->targetId = $ad->mType;
		$ad->containerHeight = 600;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}
}

class CategoryPageAdCreator extends MixedAdCreator {
	public function __construct() {
		$this->mAdsenseSlots = array(
			'rightrail0' => 7643446578,
		);
		$this->mAdServices = array(
			'rightrail0' => 'adsense',
		);
	}

	public function setupAdHtml() {
		// this ad setup only has a single right rail ad
		$this->mAds['rightrail0'] = $this->getRightRailAd( 0 );
	}

	/*
	 * @return Ad an ad for the first right rail
	 */
	protected function getRightRailFirstAdsense() {
		$ad = $this->getNewAd( 'rightrail0' );
		$ad->service = "adsense";
		$ad->targetId = $ad->mType;
		$ad->containerHeight = 600;
		$ad->initialLoad = true;
		$ad->lateLoad = false;
		$ad->width = 300;
		$ad->height = 600;
		$ad->mHtml = $this->getRightRailAdHtml( $ad );
		return $ad;
	}
}

// TODO remove this if no longer used
class MixedAdCreatorVersion1 extends MixedAdCreator {
	public function __construct() {
		$this->mAdsenseSlots = array(
			'intro' => 7862589374,
			'rightrail0' => 9646625139,
		);
		$this->mAdServices = array(
			'intro' => 'adsense',
			'rightrail0' => 'adsense',
			'rightrail1' => 'dfp',
			'rightrail2' => 'dfp'
		);
	}

	/*
	 * required by any dfp classes to set the ad unit paths
	 */
	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'rightrail1' => array(
				'adUnitPath' => '/10095428/RR2_AdX',
				'size' => '[300, 600]',
				'apsLoad' => false
			),
			'rightrail2' => array(
				'adUnitPath' => '/10095428/RR3_AdX',
				'size' => '[300, 600]',
				'apsLoad' => false
			),
			'quiz' => array(
				'adUnitPath' => '/10095428/AllPages_Quiz_English_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => false
			),
		);
	}

	/*
	 * @param Ad
	 * @return string or int channels to be used when creating adsense ad
	 */
	protected function getAdClient( $ad ) {
		if ($ad->mType == 'intro' ) {
			return 'ca-pub-9543332082073187';
		}
		return 'ca-pub-5462137703643346';
	}
}
class MixedAdCreatorVersion2 extends MixedAdCreator {
	public function __construct() {
		$this->mAdSetupData = array(
			'rightrail2' => array(
				'aps-timeout' => 800
			)
		);
		$this->mAdsenseSlots = array(
			'intro' => 7862589374,
			'step' => 1652132604,
			'rightrail0' => 4769522171,
		);
		$this->mAdServices = array(
			'intro' => 'adsense',
			'step' => 'adsense',
			'method' => 'dfp',
			'rightrail0' => 'adsense',
			'rightrail1' => 'dfp',
			'rightrail2' => 'dfp',
			'quiz' => 'dfp'
		);
	}
	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'method' => array(
				'adUnitPath' => '/10095428/Testing_Method1_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			),
			'rightrail1' => array(
				'adUnitPath' => '/10095428/RR2_Test_32',
				'size' => '[[300, 250],[300, 600]]',
				'apsLoad' => true
			),
			'rightrail2' => array(
				'adUnitPath' => '/10095428/RR3_Test_32',
				'size' => '[[300, 250],[300, 600]]',
				'apsLoad' => true
			),
			'quiz' => array(
				'adUnitPath' => '/10095428/AllPages_Quiz_English_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			)
		);
	}


	public function getIsLastAd( $ad ) {
		if ( $ad->mType == "rightrail2" ) {
			return true;
		}
		return false;
	}

	public function getRefreshable( $ad ) {
		if ( $ad->service == 'dfp' && strstr( $ad->mType, "rightrail2") && $this->mRefreshableRightRail ) {
			return true;
		}
		return false;
	}
	public function getRenderRefresh( $ad ) {
		return false;
	}

	public function getViewableRefresh( $ad ) {
		return true;
	}

	protected function getAdsenseChannels( $ad ) {
		return implode( ',', $this->mAdsenseChannels );
	}
}

class MixedAdCreatorVersion3 extends MixedAdCreatorVersion2 {
	public function __construct() {
		$this->mAdsenseSlots = array(
			'intro' => 7862589374,
		);
		$this->mAdServices = array(
			'intro' => 'adsense',
			'rightrail0' => 'dfp',
		);
	}
	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'rightrail0' => array(
				'adUnitPath' => '/10095428/Refreshing_Ad_RR1_Test',
				'size' => '[[300, 250],[300, 600]]',
				'apsLoad' => false
			),
		);
	}
	public function getRefreshable( $ad ) {
		if ( $ad->service == 'dfp' && strstr( $ad->mType, "rightrail0") && $this->mRefreshableRightRail ) {
			return true;
		}
		return false;
	}
}

class MixedAdCreatorScrollTo extends MixedAdCreatorVersion2 {
	public function __construct() {
		global $wgTitle, $wgRequest;
		$pageId = 0;
		if ( $wgTitle ) {
			$pageId = $wgTitle->getArticleID();
		}
		// right now this data will be added to each ad as data attributes
		// however we can use it in the future to define almost everything about each ad
		$this->mAdsenseChannels[] = 8837922476;
		if ( $pageId == 223933 ) {
			$this->mAdsenseChannels[] = 9756424883;
		}

		// adsense channel for not initial load rr0 ad
		if ( $pageId % 20 == 0 ) {
			$this->mAdsenseChannels[] = 8177814015;
		} else {
			$this->mAdsenseChannels[] = 6429618073;
		}

		if ( ArticleTagList::hasTag('ads_desktop_no_intro', $pageId) ) {
			$this->mAdsenseChannels[] = 2001974826;
		} else {
			$this->mAdsenseChannels[] = 2385774741;
		}

		$this->mAdSetupData = array(
			'rightrail2' => array(
				'refreshable' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800
			),
			'scrollto' => array(
				'id' => 'scrolltoad',
				'type' => 'scrollto',
				'slot' => 6515934903,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'adsensewidth' => 728,
				'adsenseheight' => 90,
				'channels' => implode( ',', $this->mAdsenseChannels )
			)
		);

		$this->mAdsenseSlots = array(
			'intro' => 7862589374,
			'step' => 1652132604,
			'rightrail0' => 4769522171,
		);

		$this->mAdServices = array(
			'intro' => 'adsense',
			'step' => 'adsense',
			'method' => 'dfp',
			'rightrail0' => 'adsense',
			'rightrail1' => 'dfp',
			'rightrail2' => 'dfp',
			'quiz' => 'dfp'
		);

		if ( WikihowToc::isNewArticle() ) {
			$this->mAdsenseSlots['method2'] = 3356467874;
			$this->mAdServices['method2'] = 'adsense';
		}

		if ( ArticleTagList::hasTag('ads_desktop_no_intro', $pageId) ) {
			unset( $this->mAdsenseSlots['intro'] );
			unset( $this->mAdServices['intro'] );
			$this->mAdsenseSlots['method3'] = 7750426266;
			$this->mAdServices['method3'] = 'adsense';
			$this->mAdSetupData['scrollto']['maxsteps'] = 0;
		}
	}

	/*
	 * creates the scroll to Ad
	 */
	public function getScrollToAd() {
		$ad = $this->getNewAd( 'scrollto' );
		$setupData = $this->mAdSetupData[$ad->mType];
		$attributes = array(
			'id' => $setupData['id']
		);
		foreach ( $setupData as $key => $val ) {
			$adKey = 'data-'.$key;
			$attributes[$adKey] = $val;
		}
		$ad->mHtml = Html::element( 'div', $attributes );
		$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addScrollToAd('{$attributes['id']}');" );
		return $ad;
	}

	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'method' => array(
				'adUnitPath' => '/10095428/Testing_Method1_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			),
			'rightrail1' => array(
				'adUnitPath' => '/10095428/RR2_Test_32',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true
			),
			'rightrail2' => array(
				'adUnitPath' => '/10095428/RR3_Test_32',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true
			),
			'quiz' => array(
				'adUnitPath' => '/10095428/AllPages_Quiz_English_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			)
		);
	}
}

class TwoRightRailAdCreator extends MixedAdCreatorScrollTo {
	public function __construct() {
		global $wgTitle, $wgRequest;
		$pageId = 0;
		if ( $wgTitle ) {
			$pageId = $wgTitle->getArticleID();
		}
		// right now this data will be added to each ad as data attributes
		// however we can use it in the future to define almost everything about each ad
		$this->mAdsenseChannels[] = 8837922476;
		if ( $pageId == 223933 ) {
			$this->mAdsenseChannels[] = 9756424883;
		}

		// adsense channel for not initial load rr0 ad
		if ( $pageId % 20 == 0 ) {
			$this->mAdsenseChannels[] = 8177814015;
		} else {
			$this->mAdsenseChannels[] = 6429618073;
		}

		$this->mAdSetupData = array(
			'rightrail1' => array(
				'instantload' => 1,
			),
			'rightrail1' => array(
				'refreshable' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800
			),
			'scrollto' => array(
				'id' => 'scrolltoad',
				'type' => 'scrollto',
				'slot' => 6515934903,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'adsensewidth' => 728,
				'adsenseheight' => 90,
				'channels' => implode( ',', $this->mAdsenseChannels )
			)
		);

		$this->mAdsenseSlots = array(
			'intro' => 7862589374,
			'step' => 1652132604,
			'rightrail0' => 4769522171,
		);

		$this->mAdServices = array(
			'intro' => 'adsense',
			'step' => 'adsense',
			'method' => 'dfp',
			'rightrail0' => 'adsense',
			'rightrail1' => 'dfp',
			'quiz' => 'dfp'
		);

		if ( WikihowToc::isNewArticle() ) {
			$this->mAdsenseSlots['method2'] = 3356467874;
			$this->mAdServices['method2'] = 'adsense';
		}
	}

	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'method' => array(
				'adUnitPath' => '/10095428/Testing_Method1_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			),
			'rightrail1' => array(
				'adUnitPath' => '/10095428/RR2_Refreshing_Instruct',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true
			),
			'quiz' => array(
				'adUnitPath' => '/10095428/AllPages_Quiz_English_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			)
		);
	}
}

class MixedAdCreatorExtraRightRail extends MixedAdCreatorScrollTo {
	public function __construct() {
		global $wgTitle, $wgRequest;
		$pageId = 0;
		if ( $wgTitle ) {
			$pageId = $wgTitle->getArticleID();
		}
		if ( $pageId ) {
			$this->numMethods = pq( '.section.steps' )->length;
		}

		$this->mExtendedRightRail = true;
		// right now this data will be added to each ad as data attributes
		// however we can use it in the future to define almost everything about each ad
		$this->mAdsenseChannels[] = 2464085811;
		if ( $pageId == 223933 ) {
			$this->mAdsenseChannels[] = 9756424883;
		}
		$this->mAdSetupData = array(
			'rightrail0' => array(
				'notfixedposition' => 1,
				'section' => 0,
			),
			'rightrail1' => array(
				'notfixedposition' => 1,
				'section' => 0,
			),
			'rightrail2' => array(
				'refreshable' => 1,
				'first-refresh-time' => 35000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'notfixedposition' => 1,
				'section' => 0,
			),
			'rightrail3' => array(
				'notfixedposition' => 1,
				'section' => 0,
			),
			'scrollto' => array(
				'id' => 'scrolltoad',
				'type' => 'scrollto',
				'slot' => 6515934903,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'adsensewidth' => 728,
				'adsenseheight' => 90,
				'channels' => implode( ',', $this->mAdsenseChannels )
			)
		);

		$this->mAdsenseSlots = array(
			'intro' => 7862589374,
			'step' => 1652132604,
			'rightrail0' => 4769522171,
		);

		$this->mAdServices = array(
			'intro' => 'adsense',
			'step' => 'adsense',
			'method' => 'dfp',
			'rightrail0' => 'adsense',
			'rightrail1' => 'dfp',
			'rightrail2' => 'dfp',
			'rightrail3' => 'dfp',
			'quiz' => 'dfp'
		);
		for ( $i = 0; $i < $this->numMethods - 1; $i++ ) {
			$num = 4 + $i;
			$rrName = "rightrail".$num;
			$this->mAdSetupData[$rrName] = [ 'notfixedposition' => 1, 'section' => $i+1 ];
			$this->mAdServices[$rrName] = 'dfp';
		}

		if ( WikihowToc::isNewArticle() ) {
			$this->mAdsenseSlots['method2'] = 3356467874;
			$this->mAdServices['method2'] = 'adsense';
		}
	}

	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'method' => array(
				'adUnitPath' => '/10095428/Testing_Method1_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			),
			'rightrail1' => array(
				'adUnitPath' => '/10095428/HL_RR_Test',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true
			),
			'rightrail2' => array(
				'adUnitPath' => '/10095428/HL_RR_Test',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true
			),
			'rightrail3' => array(
				'adUnitPath' => '/10095428/HL_RR_Test',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true
			),
			'quiz' => array(
				'adUnitPath' => '/10095428/AllPages_Quiz_English_Desktop',
				'size' => '[728, 90]',
				'apsLoad' => true
			)
		);
		for ( $i = 0; $i < $this->numMethods - 1; $i++ ) {
			$num = 4 + $i;
			$rrName = "rightrail".$num;
			$this->mDFPData[$rrName] = array(
					'adUnitPath' => '/10095428/HL_RR_Test',
					'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
					'apsLoad' => true
					);
		}
	}

	public function getIsLastAd( $ad ) {
		if ( $ad->mType == "rightrail3" ) {
			return true;
		}
		return false;
	}
}

class MixedAdCreatorVersion4 extends MixedAdCreatorVersion2 {
	public function __construct() {
		$this->mAdsenseSlots = array(
		);
		$this->mAdServices = array(
			'intro' => 'dfp',
			'rightrail0' => 'dfp',
		);
	}
	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'intro' => array(
				'adUnitPath' => '/10095428/Intro_DFP_Test',
				'size' => '[728, 90]',
				'apsLoad' => false,
			),
			'rightrail0' => array(
				'adUnitPath' => '/10095428/Refreshing_Ad_RR1_Test',
				'size' => '[[300, 250],[300, 600]]',
				'apsLoad' => false,
			),
		);
	}
	public function getRefreshable( $ad ) {
		if ( $ad->service == 'dfp' && strstr( $ad->mType, "rightrail0") && $this->mRefreshableRightRail ) {
			return true;
		}
		return false;
	}
}

class AlternateDomainAdCreator extends MixedAdCreatorVersion3 {
	public function __construct() {
		global $domainName;
		if ( strstr( $domainName, "howyougetfit.com" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 2258884570,
			);
		} elseif ( strstr( $domainName, "wikihow.tech" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 8305418177,
			);
		} elseif ( strstr( $domainName, "wikihow.pet" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 3009706573,
			);
		} elseif ( strstr( $domainName, "howyoulivelife.com" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 4845456904,
			);
		} elseif ( strstr( $domainName, "wikihow.life" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 3917364520,
			);
		} elseif ( strstr( $domainName, "wikihow.fitness" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 1291201186,
			);
		} elseif ( strstr( $domainName, "wikihow.health" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 0,
			);
		} elseif ( strstr( $domainName, "wikihow.mom" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 1099629495,
			);
		} else if ( strstr( $domainName, "wikihow-fun.com" ) ) {
			$this->mAdsenseSlots = array(
				'intro' => 7741774671,
			);
		}
		$this->mAdServices = array(
			'intro' => 'adsense',
			'method' => 'dfp',
			'rightrail0' => 'dfp',
		);
	}

	protected function setDFPAdUnitPaths() {
		global $domainName;
		if ( strstr( $domainName, "howyougetfit.com" ) ) {
			$adUnitPath = 'AllPages_RR_1_HowYouGetFit_Desktop_All';
		} elseif ( strstr( $domainName, "wikihow.tech" ) ) {
			$adUnitPath = 'AllPages_RR_1_WikiHowTech_Desktop_All';
		} elseif ( strstr( $domainName, "wikihow.pet" ) ) {
			$adUnitPath = 'AllPages_RR_1_WikiHowPet_Desktop_All';
		} elseif ( strstr( $domainName, "howyoulivelife.com" ) ) {
			$adUnitPath = 'AllPages_RR_1_HowYouLifeLife_Desktop_All';
		} elseif ( strstr( $domainName, "wikihow.life" ) ) {
			$adUnitPath = 'AllPages_RR_1_wikiHowLife_Desktop_All';
		} elseif ( strstr( $domainName, "wikihow.fitness" ) ) {
			$adUnitPath = 'AllPages_RR_1_wikiHowFit_Desktop_All';
		} elseif ( strstr( $domainName, "wikihow.health" ) ) {
			$adUnitPath = 'AllPages_RR_1_wikiHowHealth_Desktop_All';
		} elseif ( strstr( $domainName, "wikihow.mom" ) ) {
			$adUnitPath = 'AllPages_RR_1_wikiHowMom_Desktop_All';
		} else if ( strstr( $domainName, "wikihow-fun.com" ) ) {
			$adUnitPath = 'WH-Fun-RR';
		}
		$this->mDFPData = array(
			'method' => array(
				'adUnitPath' => '/10095428/Method_1_Alt_Domain',
				'size' => '[728, 90]',
				'apsLoad' => true
			),
			'rightrail0' => array(
				'adUnitPath' => '/10095428/' . $adUnitPath,
				'size' => '[[300, 250], [300, 600]]',
				'apsLoad' => true

			),
		);
	}
	public function getQuizAd( $num ) {
		return "";
	}
}

class DocViewerAdCreator extends MixedAdCreator {

	public function __construct() {
		$this->mAdsenseSlots = array(
			'docviewer1' => 4591712179,
		);
	}

	/*
	 * @param Ad
	 * @return string or int channels to be used when creating adsense ad
	 */
	protected function getAdClient( $ad ) {
		return 'ca-pub-9543332082073187';
	}

	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'docviewer0' => array(
				'adUnitPath' => '/10095428/Image_Ad_Sample_Page',
				'size' => '[[300, 250], [300, 600]]',
				'apsLoad' => true
			)
		);
	}

	protected function getAdsenseChannels( $ad ) {
		return "";
	}

	/*
	 * gets the ad data for all ads on the page
	 */
	public function setupAdHtml() {
		$this->setDFPAdUnitPaths();
		// after the first right rail ad, we append the initial refresh all to DFP ads
		for ( $i = 0; $i < 2; $i++ ) {
			$this->mAds['docviewer'.$i] = $this->getDocViewerAd( $i );
		}
		$this->mAds['docviewer0']->mHtml .= $this->getInitialRefreshSnippet();
	}

	public function getDocViewerAd( $num ) {
		$type = "docviewer".$num;
		$ad = $this->getNewAd( $type );
		if ( $num == 0 ) {
			$ad->service = "dfp";
			$ad->targetId = 'div-gpt-ad-1354818302611-0';
			$ad->containerHeight = 600;
			$ad->initialLoad = true;
			if ( $this->mShowRightRailLabel == false ) {
				$ad->mLabel = "";
			}
			$ad->mHtml = $this->getRightRailAdHtml( $ad );
			$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addRightRailAd('{$ad->mType}')" );
		} elseif ( $num == 1 ) {
			$ad->service = "adsense";
			$ad->targetId = $ad->mType;
			$ad->initialLoad = true;
			$ad->adClass = "docviewad";
			$ad->width = 728;
			$ad->height = 90;
			$ad->mHtml = $this->getBodyAdHtml( $ad );
		}
		return $ad;
	}

	public function getRefreshable( $ad ) {
		if ( $ad->service == 'dfp' && strstr( $ad->mType, "docviewer0" ) && $this->mRefreshableRightRail ) {
			return true;
		}
		return false;
	}
	public function getRenderRefresh( $ad ) {
		return false;
	}

	public function getViewableRefresh( $ad ) {
		return true;
	}
}

class DocViewerAdCreatorVersion2 extends DocViewerAdCreator {
	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'docviewer0' => array(
				'adUnitPath' => '/10095428/Image_Ad_Sample_Page',
				'size' => '[[300, 250], [300, 600]]',
				'apsLoad' => true,
			),
			'docviewer1' => array(
				'adUnitPath' => '/10095428/Image_Ad_Sample_728x90',
				'size' => '[728, 90]',
				'apsLoad' => true,
			)
		);
	}

	/*
	 * gets the ad data for all ads on the page
	 */
	public function setupAdHtml() {
		$this->setDFPAdUnitPaths();
		// after the first right rail ad, we append the initial refresh all to DFP ads
		for ( $i = 0; $i < 2; $i++ ) {
			$this->mAds['docviewer'.$i] = $this->getDocViewerAd( $i );
		}
		$this->mAds['docviewer1']->mHtml .= $this->getInitialRefreshSnippet();
	}

	public function getDocViewerAd( $num ) {
		$type = "docviewer".$num;
		$ad = $this->getNewAd( $type );
		if ( $num == 0 ) {
			$ad->service = "dfp";
			$ad->targetId = 'div-gpt-ad-1354818302611-0';
			$ad->containerHeight = 600;
			$ad->initialLoad = true;
			if ( $this->mShowRightRailLabel == false ) {
				$ad->mLabel = "";
			}
			$ad->mHtml = $this->getRightRailAdHtml( $ad );
			$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addRightRailAd('{$ad->mType}')" );
		} elseif ( $num == 1 ) {
			$ad->service = "dfp";
			$ad->targetId = $ad->mType;
			$ad->initialLoad = true;
			$ad->adClass = "docviewad";
			$ad->width = 728;
			$ad->height = 90;
			$ad->mHtml = $this->getBodyAdHtml( $ad );
		}
		return $ad;
	}

	public function getRefreshable( $ad ) {
		if ( $ad->service == 'dfp' && strstr( $ad->mType, "docviewer0" ) && $this->mRefreshableRightRail ) {
			return true;
		}
		return false;
	}
}
class InternationalAdCreator extends MixedAdCreatorVersion2 {
	public function __construct() {
		$this->mAdsenseSlots = array(
			'intro' => 2583804979,
			'rightrail0' => 4060538172,
		);
		$this->mAdServices = array(
			'intro' => 'adsense',
			'method' => 'dfp',
			'rightrail0' => 'adsense',
			'rightrail1' => 'dfp',
			'rightrail2' => 'dfp'
		);

		$this->mAdSetupData = array(
			'scrollto' => array(
				'id' => 'scrolltoad',
				'type' => 'scrollto',
				'slot' => 5411724845,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'adsensewidth' => 728,
				'adsenseheight' => 90,
				'channels' => implode( ',', $this->mAdsenseChannels )
			)
		);

		if ( WikihowToc::isNewArticle() ) {
			$this->mAdsenseSlots['method2'] = 8388669218;
			$this->mAdServices['method2'] = 'adsense';
			$this->mAdsenseChannels[] = 1412197323;
		} else {
			$this->mAdsenseChannels[] = 7466415884;
		}
		$this->mAdsenseChannels[] = 4819709854;
	}

	/*
	 * creates the scroll to Ad
	 */
	public function getScrollToAd() {
		$ad = $this->getNewAd( 'scrollto' );
		$setupData = $this->mAdSetupData[$ad->mType];
		$attributes = array(
			'id' => $setupData['id']
		);
		foreach ( $setupData as $key => $val ) {
			$adKey = 'data-'.$key;
			$attributes[$adKey] = $val;
		}
		$ad->mHtml = Html::element( 'div', $attributes );
		$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addScrollToAd('{$attributes['id']}');" );
		return $ad;
	}

	protected function setDFPAdUnitPaths() {
		$this->mDFPData = array(
			'method' => array(
				'adUnitPath' => '/10095428/AllPages_Method_1_Intl_Desktop_All',
				'size' => '[728, 90]',
				'apsLoad' => false
			),
			'rightrail1' => array(
				'adUnitPath' => '/10095428/AllPages_RR_2_Intl_Desktop_All',
				'size' => '[[300, 250], [300, 600]]',
				'apsLoad' => false
			),
			'rightrail2' => array(
				'adUnitPath' => '/10095428/AllPages_RR_3_Intl_Desktop_All',
				'size' => '[[300, 250], [300, 600]]',
				'apsLoad' => false
			),
		);
	}
	public function getQuizAd( $num ) {
		return "";
	}
}

class InternationalAdCreatorAllAdsense extends MixedAdCreatorVersion2 {
	public function __construct() {
		$this->mAdsenseSlots = array(
			'intro' => 2583804979,
			'method' => 3315713030,
			'rightrail0' => 4060538172,
			'rightrail1' => 7854380386,
			'rightrail2' => 8731034705,
		);
		$this->mAdServices = array(
			'intro' => 'adsense',
			'method' => 'adsense',
			'rightrail0' => 'adsense',
			'rightrail1' => 'adsense',
			'rightrail2' => 'adsense'
		);

		$this->mAdSetupData = array(
			'scrollto' => array(
				'id' => 'scrolltoad',
				'type' => 'scrollto',
				'slot' => 5411724845,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'adsensewidth' => 728,
				'adsenseheight' => 90,
				'channels' => implode( ',', $this->mAdsenseChannels )
			)
		);

		if ( WikihowToc::isNewArticle() ) {
			$this->mAdsenseSlots['method2'] = 8388669218;
			$this->mAdServices['method2'] = 'adsense';
			$this->mAdsenseChannels[] = 1412197323;
		} else {
			$this->mAdsenseChannels[] = 7466415884;
		}
		$this->mAdsenseChannels[] = 2193546513;
	}
	/*
	 * creates the scroll to Ad
	 */
	public function getScrollToAd() {
		$ad = $this->getNewAd( 'scrollto' );
		$setupData = $this->mAdSetupData[$ad->mType];
		$attributes = array(
			'id' => $setupData['id']
		);
		foreach ( $setupData as $key => $val ) {
			$adKey = 'data-'.$key;
			$attributes[$adKey] = $val;
		}
		$ad->mHtml = Html::element( 'div', $attributes );
		$ad->mHtml .= Html::inlineScript( "WH.desktopAds.addScrollToAd('{$attributes['id']}');" );
		return $ad;
	}

	public function getQuizAd( $num ) {
		return "";
	}
}

class SearchPageAdCreator extends MixedAdCreator {

	public function __construct($query = '') {
		global $wgLanguageCode;

		if ($wgLanguageCode == 'zh') {
			return;
		}

		if (SearchAdExclusions::isExcluded($query)) {
			return;
		}

		$this->mAdsenseSlots = array(
			'rightrail0' => 2504442946
		);
		$this->mAdServices = [ 'rightrail0' => 'adsense' ];
	}

	public function setupAdHtml() {
		$this->mAds['rightrail0'] = $this->getRightRailAd( 0 );
		parent::setupAdHtml();
	}

	public function getQuizAd( $num ) {
		return '';
	}
}
class InternationalSearchPageAdCreator extends MixedAdCreator {

	public function __construct($query = '') {
		global $wgLanguageCode;

		if ($wgLanguageCode == 'zh') {
			return;
		}

		if (SearchAdExclusions::isExcluded($query)) {
			return;
		}

		$this->mAdsenseSlots = array(
			'rightrail0' => 8601670711
		);
		$this->mAdServices = [ 'rightrail0' => 'adsense' ];
	}

	public function setupAdHtml() {
		$this->mAds['rightrail0'] = $this->getRightRailAd( 0 );
		parent::setupAdHtml();
	}

	public function getQuizAd( $num ) {
		return '';
	}
}
