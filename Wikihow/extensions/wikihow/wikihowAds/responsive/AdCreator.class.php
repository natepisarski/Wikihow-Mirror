<?php

class Ad {
	var $mHtml;
	var $mType;

	public function __construct( $type ) {
		$this->mType = $type;
	}
}

/*
 * default setup for the ad creators
 */
abstract class AdCreator {
	var $mStickyIntro = false;
	var $mDFPKeyVals = array();
	var $mRefreshableRightRail = false;
	var $mAdsenseAutoAds = false;
	var $mAdCounts = array();
	var $mGptSlotDefines = array();
	var $mDFPData = array();
	var $mLateLoadDFP = false;

	// TODO figure out the channels
	var $mAdsenseChannels = array();

	public function getPreContentAdHtml() {
		return "";
	}

	private function getAdTargetId( $type ) {
		if ( !isset( $this->mAdCounts[$type] ) ) {
			$this->mAdCounts[$type] = 0;
		}

		$this->mAdCounts[$type] += 1;

		return $type . '_ad_' . $this->mAdCounts[$type];
	}

	protected function getNewAd( $type ) {

		$ad = new Ad( $type );

		if ( isset ($this->mAdSetupData[$type] ) ) {
			$ad->setupData = $this->mAdSetupData[$type];
		}

		$ad->mTargetId = $this->getAdTargetId( $type );

		return $ad;
	}

	/*
	 * intro sticky data attr (to be used client side)
	 * $param boolean
	 */
	public function setStickyIntro( $val ) {
		$this->mStickyIntro = $val;
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


	/*
	 *  adds the intro ad to the body using php query
	 * assumes the php query article is loaded
	 */
	protected function insertIntroAd() {
		$ad = $this->getBodyAd( 'intro' );
		if ( !$ad ) {
			return;
		}

		pq( "#intro" )->append( $ad->mHtml )->addClass( "hasad" );
	}

	/*
	 * adds the scrollto ads to the body using php query
	 * assumes the php query article is loaded
	 */
	protected function insertScrollToAd() {
		$ad = $this->getBodyAd( 'scrollto' );
		if ( !$ad ) {
			return;
		}
		pq( "#intro" )->after( $ad->mHtml );
	}

	/*
	 * adds the quiz ads to the body using php query
	 * assumes the php query article is loaded
	 */
	protected function insertQuizAds() {
		for ( $i = 0; $i < pq( '.qz_container' )->length; $i++ ) {
			$ad = $this->getBodyAd( 'quiz' );
			if ( !$ad ) {
				continue;
			}
			pq( '.qz_container' )->eq($i)->append( $ad->mHtml );
		}
	}

	protected function insertTocAd() {
		$ad = $this->getBodyAd( 'toc' );
		if ( !$ad ) {
			return;
		}

		if ( !pq( ".steps_list_2:first > li:first" )->length ) {
			return;
		}

		pq( ".steps_list_2:first > li:first" )->append( $ad->mHtml );
	}

	protected function insertRelatedAd() {
		$ad = $this->getBodyAd( 'related' );
		if ( !$ad ) {
			return;
		}

		$target = "#relatedwikihows";
		if ( pq( $target )->length < 1 ) {
			// if not found try to get related wikihows target name in another way
			$relatedsname = RelatedWikihows::getSectionName();
			$target = "#".$relatedsname;
		}

		if ( pq( $target )->length == 0 ) {
			return;
		}

		pq( $target )->append( $ad->mHtml );
	}

	protected function insertMobileRelatedAd() {
		$ad = $this->getBodyAd( 'mobilerelated' );
		if ( !$ad ) {
			return;
		}

		$target = "#relatedwikihows";
		if ( pq( $target )->length < 1 ) {
			// if not found try to get related wikihows target name in another way
			$relatedsname = RelatedWikihows::getSectionName();
			$target = "#".$relatedsname;
		}

		if ( pq( $target )->length == 0 ) {
			return;
		}

		pq( $target )->append( $ad->mHtml );
	}

	protected function insertMiddleRelatedAd() {
		$ad = $this->getBodyAd( 'middlerelated' );
		if ( !$ad ) {
			return;
		}
		$target = "#relatedwikihows";
		if ( pq( $target )->length < 1 ) {
			// if not found try to get related wikihows target name in another way
			$relatedsname = RelatedWikihows::getSectionName();
			$target = "#".$relatedsname;
		}

		if ( pq( $target )->length == 0 ) {
			return;
		}

		pq( $target )->find( '.related-article:eq(1)' )->after( $ad->mHtml );
	}

	protected function insertQAAd() {
		$ad = $this->getBodyAd( 'qa' );
		if ( !$ad ) {
			return;
		}
		$target = "#qa";
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertMobileQAAd() {
		$ad = $this->getBodyAd( 'mobileqa' );
		if ( !$ad ) {
			return;
		}
		$target = "#qa";
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertTipsAd() {
		$ad = $this->getBodyAd( 'tips' );
		if ( !$ad ) {
			return;
		}
		$target = strtolower( wfMessage( 'tips' )->text() );
		$target = "#".$target;
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertWarningsAd() {
		$ad = $this->getBodyAd( 'warnings' );
		if ( !$ad ) {
			return;
		}
		$target = strtolower( wfMessage( 'warnings' )->text() );
		$target = "#".$target;
		if ( pq( $target )->length == 0 ) {
			return;
		}
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertPageBottomAd() {
		$ad = $this->getBodyAd( 'pagebottom' );
		if ( !$ad ) {
			return;
		}
		$bottomAdContainer = Html::element( 'div', ['id' => 'pagebottom'] );
		if ( pq( '#article_rating_mobile' )->length == 0 ) {
			return;
		}

		pq('#article_rating_mobile')->after( $bottomAdContainer );
		$target = "#pagebottom";
		pq( $target )->append( $ad->mHtml );
	}

	protected function insertStepAd() {
		$ad = $this->getBodyAd( 'step' );
		if ( !$ad ) {
			return;
		}

		if ( !pq( ".steps_list_2 > li:eq(0)" )->length ) {
			return;
		}

		pq( ".steps_list_2 > li:eq(0)" )->append( $ad->mHtml );
	}

	protected function insertMobileMethodAds() {
		$count = pq( ".steps_list_2 > li:last-child" )->length;
		for ( $i = 0; $i < $count; $i++ ) {
			$ad = $this->getBodyAd( 'mobilemethod' );
			if ( !$ad ) {
				continue;
			}
			pq( ".steps_list_2:eq($i) > li:last-child" )->append( $ad->mHtml );
		}
	}

	/*
	 * adds the step and method ads to the body using php query
	 * assumes the php query article is loaded
	 */
	protected function insertMethodAd() {
		if ( pq( ".steps_list_2:first > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:first > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method' );
		if ( !$ad ) {
			return;
		}
		pq( ".steps_list_2:first > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethod2Ad() {
		$methodNumber = 2;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method2' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethod3Ad() {
		$methodNumber = 3;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method3' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethod4Ad() {
		$methodNumber = 4;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method4' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethod5Ad() {
		$methodNumber = 5;

		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods < $methodNumber ) {
			return;
		}

		// the index is zero based for the selectors below
		$methodNumber -= 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'method5' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	protected function insertMethodExtraAd() {
		$numMethods = pq( ".steps_list_2" )->length;

		// this ad only gets inserted if there are more than 6 methods
		if ( $numMethods <= 6 ) {
			return;
		}

		$start = 5;
		$end = $numMethods - 1;
		for ( $i = $start; $i < $end; $i++ ) {
			$ad = $this->getBodyAd( 'methodextra' );
			if ( !$ad ) {
				continue;
			}
			pq( ".steps_list_2:eq($i) > li:last-child" )->append( $ad->mHtml );
		}
	}

	protected function insertMethodLastAd() {
		$numMethods = pq( ".steps_list_2" )->length;
		if ( $numMethods <= 5 ) {
			return;
		}

		$methodNumber = $numMethods - 1;

		if ( pq( ".steps_list_2:eq($methodNumber) > li" )->length <= 2 ) {
			return;
		}

		if ( pq( ".steps_list_2:eq($methodNumber) > li:last-child)" )->length() == 0 ) {
			return;
		}

		$ad = $this->getBodyAd( 'methodlast' );

		if ( !$ad ) {
			return;
		}

		pq( ".steps_list_2:eq($methodNumber) > li:last-child" )->append( $ad->mHtml );
	}

	/*
	 * uses php query to put the ad html into the body of the page
	 */
	public function insertAdsInBody() {
		global $wgOut;

		// make sure we have php query object
		if ( !phpQuery::$defaultDocumentID )  {
			return;
		}

		// TODO this has not been tested yet
		if ( GoogleAmp::isAmpMode( $wgOut ) ) {
			GoogleAmp::insertAMPAds();
			return;
		}

		$this->insertIntroAd();
		$this->insertTocAd();
		$this->insertMethodAd();
		$this->insertMethod2Ad();
		$this->insertMethod3Ad();
		$this->insertMethod4Ad();
		$this->insertMethod5Ad();
		$this->insertMethodExtraAd();
		$this->insertMethodLastAd();
		$this->insertMobileMethodAds();
		$this->insertScrollToAd();
		$this->insertStepAd();
		$this->insertRelatedAd();
		$this->insertMobileRelatedAd();
		$this->insertMiddleRelatedAd();
		$this->insertQAAd();
		$this->insertMobileQAAd();
		$this->insertTipsAd();
		$this->insertWarningsAd();
		$this->insertPageBottomAd();
		$this->insertQuizAds();
	}

	public function __construct() {
		global $wgTitle;
		$this->mPageId = 0;
		if ( $wgTitle ) {
			$this->mPageId = $wgTitle->getArticleID();
		}
	}

	public function getAdsenseAutoAds() {
		return $this->mAdsenseAutoAds;
	}

	public function setAdsenseAutoAds( $value ) {
		$this->mAdsenseAutoAds = $value;
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

	public function getBodyAd( $type ) {
		$ad = $this->getNewAd( $type );

		if ( !isset( $this->mAdSetupData[$ad->mType] ) ) {
			return null;
		}

		// get the inner ad
		$innerClass = $ad->setupData['innerclass'] ?:'';
		$attributes = array(
			'id' => $ad->mTargetId,
			'class' => $innerClass,
		);
		$innerAdHtml = Html::element( 'div', $attributes );

		// now the wrapper
		$attributes = array(
			'class' => array( 'wh_ad_inner' ),
		);

		// all the settings for the ad come from the adSetupData
		foreach ( $this->mAdSetupData[$ad->mType] as $key => $val ) {
			if ( $key == 'class' ) {
				foreach ( $val as $classVal ) {
					$attributes['class'][] = $classVal;
				}
			} elseif ( $key == 'mobilelabel' ) {
				$label = $this->getAdLabelText();
				$innerAdHtml .= Html::rawElement( 'div', [ 'class' => 'ad_label_method' ], $label );
			} elseif ( $key == 'containerheight' ) {
				$attributes['style'] = "height:{$val}px";
			} else {
				if ( is_array( $val ) ) {
					$val = implode(' ', $val);
				}
				$adKey = 'data-'.$key;
				$attributes[$adKey] = $val;
			}
		}

		if ( !isset( $attributes['data-channels'] ) ) {
			$attributes['data-channels'] = array();
		}
		$attributes['data-channels'] += $this->mAdsenseChannels ?: [];
		$attributes['data-channels'] = implode( ',', $attributes['data-channels'] );

		if ( !isset( $attributes['data-mobilechannels'] ) ) {
			$attributes['data-mobilechannels'] = array();
		}
		$attributes['data-mobilechannels'] += $this->mMobileAdsenseChannels ?: [];
		$attributes['data-mobilechannels'] = implode( ',', $attributes['data-mobilechannels'] );
		$html = Html::rawElement( 'div', $attributes, $innerAdHtml );

		// TODO This adds the gpt script..sould be able to do this in js though
		$html .= $script;
		$html .= Html::inlineScript( "WH.ads.addBodyAd('{$ad->mTargetId}')" );
		$ad->mHtml = $html;

		if ( $ad->setupData['service'] == 'dfp' ) {
			$this->addToGPTDefines( $ad );
		}

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
	}

	// returns a javascript snippet of the ad unit path
	// instead of just a string to prevetn google bot from crawling these paths
	protected function getGPTAdSlot( $path ) {
		$adUnitPath = str_replace( "/", "|", $path);
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
		$html = Html::inlineScript("googletag.cmd.push(function(){WH.ads.apsFetchBids($apsSlots, $gptSlotIds, 2000);});");
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
	 * gets script for adsense and dfp
	 * @return string html for head
	 */
	public function getHeadHtml() {
		$addAdsense = false;
		$addDFP = false;
		$apsLoad = false;
		foreach ( $this->mAdSetupData as $adType => $adData ) {
			$service = $adData['service'];

			if ( $service == "adsense" ) {
				$addAdsense = true;
			}

			if ( $service == "dfp" ) {
				$addDFP = true;
			}
			$apsLoad = $apsLoad || $adData['apsLoad'];
		}

		$adsenseScript = "";
		if ( $addAdsense ) {
			$adsenseScript = file_get_contents( __DIR__."/../desktopAdsense.js" );
			$adsenseScript = Html::inlineScript( $adsenseScript );
		}

		$indexHeadScript = "";
		$dfpScript = "";
		if ( $addDFP ) {
			if ( rand( 1, 2 ) == 1 ) {
				$indexHeadScript = $this->getIndexHeadScript();
			}
			$dfpScript = '';
			if ( $this->mLateLoadDFP == false ) {
				//$dfpScript .= '<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>';
				$dfpInit = file_get_contents( __DIR__."/DFPinit.js" );
				$dfpScript .= Html::inlineScript( $dfpInit );
				if ( $apsLoad ) {
					$apsInit = file_get_contents( __DIR__."/APSinit.js" );
					$dfpScript .= Html::inlineScript( $apsInit );
				}
			}
		}

		$adLabelStyle = $this->getAdLabelStyle();

		return $indexHeadScript . $adsenseScript . $dfpScript . $adLabelStyle;
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

	protected function getIndexHeadScript() {
		$html =  '<script async src="//js-sec.indexww.com/ht/p/184011-188477170437417.js"></script>';
		return $html;
	}
	protected function addToGPTDefines( $ad ) {
		$adUnitPath = $ad->setupData['adUnitPath'];
		$adUnitPath = $this->getGPTAdSlot( $adUnitPath );
		$adSize = $ad->setupData['size'];
		$adId = $ad->mTargetId;
		$gpt = "gptAdSlots['$adId'] = googletag.defineSlot(".$adUnitPath.", $adSize, '$adId').addService(googletag.pubads());\n";
		$this->mGptSlotDefines[] = $gpt;
	}

	public function getGPTDefine() {
		global $wgIsDevServer;
		$dfpKeyVals = $this->getDFPKeyValsJSON();
		$gpt = "var gptAdSlots = [];\n";
		$gpt .= "var dfpKeyVals = $dfpKeyVals;\n";
		$gpt .= "var googletag = googletag || {};\n";
		$gpt .= "googletag.cmd = googletag.cmd || [];\n";
		$gpt .= "var gptRequested = gptRequested || false;\n";
		$gpt .= "function defineGPTSlots() {\n";
		// TODO in the future we can possibly define the GPT slot in js along with the new BodyAd call
		$gpt .= implode( $this->mGptSlotDefines );
		$gpt .= "googletag.pubads().enableSingleRequest();\n";
		$gpt .= "googletag.pubads().disableInitialLoad();\n";
		//if ( !$wgIsDevServer ) {
			$gpt .= "googletag.pubads().collapseEmptyDivs();\n";
		//}
		$gpt .= "googletag.enableServices();\n";

		$gpt .= "}\n";
		$result = Html::inlineScript( $gpt );
		return $result;
	}
}

class DefaultMainPageAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 6166713376,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
		);
	}
}

class DefaultCategoryPageAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 7643446578,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
		);
	}
}

class DefaultAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();

		if ( ArticleTagList::hasTag('ads_desktop_no_intro', $this->mPageId) ) {
			$this->mAdsenseChannels[] = 2001974826;
		} else {
			$this->mAdsenseChannels[] = 2385774741;
		}

		if ( ArticleTagList::hasTag( 'amp_disabled_pages', $this->mPageId ) ) {
			$this->mMobileAdsenseChannels[] = 8411928010;
		} else {
			$this->mMobileAdsenseChannels[] = 7928712280;
			// this group of pages have adsense on AMP, so we want to put a special channel to measure it
			// and we will put a corresponding channel on the adsense ads
			if ( $pageId % 100 < 10 ) {
				$this->mMobileAdsenseChannels[] = 9252820051;
			}
		}

		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => 7672188889,
				'width' => 728,
				'height' => 120,
				'mobileslot' => 8943394577,
				'mobileheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
				'type' => 'intro',
			),
			'method' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_lm_method_1',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'method2' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_m_method_2',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mediumonly' => 1
			),
			'method3' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_m_method_3',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mediumonly' => 1
			),
			'method4' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_m_method_4',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mediumonly' => 1
			),
			'method5' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_m_method_5',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mediumonly' => 1
			),
			'methodextra' => array(
				'service' => 'adsense',
				'slot' => 8674374823,
				'width' => 728,
				'height' => 90,
				'mediumonly' => 1
			),
			'methodlast' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_m_method_last',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'mediumonly' => 1
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => 4313551892,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 5490902193,
				'instantload' => 0,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'largeonly' => 1,
			),
			'rightrail1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_lm_right_rail_2',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true,
				'refreshable' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
				'largeonly' => 1,
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => 4177820525,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
				'largeonly' => 1,
			),
			'quiz' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_lm_quiz',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'class' => ['hidden'],
				'type' => 'quiz',
			),
			'related' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_lm_rwh',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'qa' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/dfp_responsive_lm_qa',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'mobileslot' => 7710650179,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'method',
			),
			'mobilerelated' => array(
				'service' => 'adsense',
				'mobileslot' => 9047782573,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'related'
			),
			'middlerelated' => array(
				'service' => 'adsense',
				'mobileslot' => 3859396687,
				'mobileheight' => 250,
				'type' => 'middlerelated',
			),
			'mobileqa' => array(
				'service' => 'adsense',
				'mobileslot' => 1240030252,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'qa'
			),
			'tips' => array(
				'service' => 'adsense',
				'mobileslot' => 8787347780,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'tips'
			),
			'warnings' => array(
				'service' => 'adsense',
				'mobileslot' => 3674621907,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'warnings'
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'mobileslot' => 3788982605,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'pagebottom'
			),
		);

		if ( !WikihowToc::isNewArticle() ) {
			unset( $this->mAdSetupData['toc'] );
		}

		if ( (class_exists("TechLayout") && ArticleTagList::hasTag(TechLayout::CONFIG_LIST, $this->mPageId)) ) {
			unset( $this->mAdSetupData['intro'] );
		}
	}
}

// TODO test this
class DefaultDocViewerAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();
		$this->mAdSetupData = array(
			'docviewer0' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/Image_Ad_Sample_Page',
				'size' => '[[300, 250], [300, 600]]',
				'apsLoad' => true,
				'refreshable' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container', 'ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'docviewer1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/Image_Ad_Sample_728x90',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'class' => ['ad_label', 'ad_label_dollar'],
			),
		);
	}
}

class DefaultInternationalAdCreator extends AdCreator {
	public function __construct() {
		parent::__construct();

		$this->mAdsenseChannels[] = 4819709854;

		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => 2583804979,
				'width' => 728,
				'height' => 120,
				'mobileslot' => 2831688978,
				'mobileheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
				'type' => 'intro',
			),
			'method' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/AllPages_Method_1_Intl_Desktop_All',
				'size' => '[728, 90]',
				'apsLoad' => true,
				'aps-timeout' => 2000,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => 8388669218,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 4060538172,
				'instantload' => 0,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'rightrail1' => array(
				'service' => 'dfp',
				'adUnitPath' => '/10095428/AllPages_RR_2_Intl_Desktop_All',
				'size' => '[[300, 250],[300, 600],[120,600],[160,600]]',
				'apsLoad' => true,
				'refreshable' => 1,
				'first-refresh-time' => 30000,
				'refresh-time' => 28000,
				'aps-timeout' => 800,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => 5411724845,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'mobileslot' => 6771527778,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'method',
			),
			'mobilerelated' => array(
				'service' => 'adsense',
				'mobileslot' => 9724994176,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'related'
			),
			'tips' => array(
				'service' => 'adsense',
				'mobileslot' => 8125162876,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'tips'
			),
			'warnings' => array(
				'service' => 'adsense',
				'mobileslot' => 4621387358,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'warnings'
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'mobileslot' => 3373074232,
				'mobileheight' => 250,
				'mobilelabel' => 1,
				'type' => 'pagebottom'
			),
		);
	}
}

class DefaultInternationalAdCreatorAllAdsense extends AdCreator {
	public function __construct() {
		parent::__construct();
		// TODO do we need all these channels
		if ( WikihowToc::isNewArticle() ) {
			unset( $this->mAdSetupData['toc'] );
			$this->mAdsenseChannels[] = 1412197323;
		} else {
			$this->mAdsenseChannels[] = 7466415884;
		}

		$this->mAdsenseChannels[] = 2193546513;
		$this->mAdSetupData = array(
			'intro' => array(
				'service' => 'adsense',
				'instantload' => 1,
				'slot' => 2583804979,
				'width' => 728,
				'height' => 120,
				'mobileslot' => 2831688978,
				'mobileheight' => 120,
				'class' => ['ad_label', 'ad_label_dollar'],
			),
			'method' => array(
				'service' => 'adsense',
				'slot' => 3315713030,
				'width' => 728,
				'height' => 90,
				'desktoponly' => 1
			),
			'toc' => array(
				'service' => 'adsense',
				'slot' => 8388669218,
				'width' => 728,
				'height' => 90,
				'type' => 'toc',
			),
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 4060538172,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'rightrail1' => array(
				'service' => 'adsense',
				'slot' => 7854380386,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'rightrail2' => array(
				'service' => 'adsense',
				'slot' => 8731034705,
				'width' => 300,
				'height' => 600,
				'containerheight' => 3300,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
			'scrollto' => array(
				'service' => 'adsense',
				'type' => 'scrollto',
				'slot' => 5411724845,
				'maxsteps' => 2,
				'maxnonsteps' => 0,
				'width' => 728,
				'height' => 90,
			),
			'mobilemethod' => array(
				'service' => 'adsense',
				'mobileslot' => 6771527778,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'related' => array(
				'service' => 'adsense',
				'mobileslot' => 9724994176,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'tips' => array(
				'service' => 'adsense',
				'mobileslot' => 8125162876,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'warnings' => array(
				'service' => 'adsense',
				'mobileslot' => 4621387358,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
			'pagebottom' => array(
				'service' => 'adsense',
				'mobileslot' => 3373074232,
				'mobileheight' => 250,
				'mobilelabel' => 1,
			),
		);

		if ( WikihowToc::isNewArticle() ) {
			unset( $this->mAdSetupData['toc'] );
		}
	}

}

class DefaultSearchPageAdCreator extends AdCreator {
	public function __construct( $query = '' ) {
		parent::__construct();
		global $wgLanguageCode;

		if ( $wgLanguageCode == 'zh' ) {
			return;
		}

		if ( SearchAdExclusions::isExcluded( $query ) ) {
			return;
		}

		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 2504442946,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
		);
	}
}

class DefaultInternationalSearchPageAdCreator extends AdCreator {
	public function __construct($query = '') {
		parent::__construct();
		global $wgLanguageCode;

		if ( $wgLanguageCode == 'zh' ) {
			return;
		}

		if ( SearchAdExclusions::isExcluded( $query ) ) {
			return;
		}

		$this->mAdSetupData = array(
			'rightrail0' => array(
				'service' => 'adsense',
				'slot' => 8601670711,
				'instantload' => 1,
				'width' => 300,
				'height' => 600,
				'containerheight' => 2000,
				'class' => ['rr_container'],
				'innerclass' => ['ad_label', 'ad_label_dollar'],
				'type' => 'rightrail',
			),
		);
	}
}
