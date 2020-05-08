<?php

class GoogleAmpAds {

	public static function insertAds() {
		self::insertAdsDefault();
	}

	public static function insertAdsDefault() {
		global $wgLanguageCode, $wgTitle, $wgRequest, $wgDFPAdBucket;
		$pageId = 0;
		if ( $wgTitle ) {
			$pageId = $wgTitle->getArticleID();
		}
		$intlSite = $wgLanguageCode != 'en';

		$intro = 1;
		$firstStep = 2;
		$fifthStep = 3;
		$method = 4;
		$related = 5;
		$tips = 7;
		$warnings = 8;
		$bottomOfPage = 9;

		$hasIntroAd = true;

		// for targeting dfp ads
		$bucket = $wgDFPAdBucket;

		if ( $wgRequest && $wgRequest->getInt( 'bucket' ) ) {
			$reqBucket = $wgRequest->getInt( 'bucket' );
			if ( $reqBucket > 0 && $reqBucket <= 25 ) {
				$bucket = $reqBucket;
			}
		}

		if ( $hasIntroAd == true ) {
			$adhtml = self::getAd( $intro, $pageId, $intlSite, $bucket );
			pq( "#intro" )->append( $adhtml );


			// put an ad after second step if there is more than 1 step in first method
			if ( pq( ".steps_list_2:first > li" )->length > 2 ) {
				$adhtml = self::getAd( $firstStep, $pageId, $intlSite, $bucket );
				pq(".steps_list_2:first > li:eq(1)")->append( $adhtml );
			}
		} else {
			// put an ad after first step if there is more than 1 step in first method
			if ( pq( ".steps_list_2:first > li" )->length > 1 ) {
				$adhtml = self::getAd( $firstStep, $pageId, $intlSite, $bucket );
				pq(".steps_list_2:first > li:eq(0)")->append( $adhtml );
			}
		}


		// put an ad after fifth step if there is more than 5 steps in first method
		if ( pq( ".steps_list_2:first > li" )->length > 5 ) {
			$adhtml = self::getAd( $fifthStep, $pageId, $intlSite, $bucket );
			pq(".steps_list_2:first > li:eq(4)")->append( $adhtml );
		}

		// ad in last step of each method
		$methodNumber = 1;
		foreach ( pq(".steps:not('.sample') .steps_list_2 > li:last-child") as $lastStep ) {
			$adhtml = self::getAd( $method, $pageId, $intlSite, $bucket, $methodNumber );
			pq( $lastStep )->append( $adhtml );
			$methodNumber++;
		}

		$relatedsname = RelatedWikihows::getSectionName();
		if ( pq("#{$relatedsname}")->length ) {
			$adhtml = self::getAd( $related, $pageId, $intlSite, $bucket );
			pq("#{$relatedsname}")->append($adhtml);
		} elseif ( pq("#relatedwikihows")->length ) {
			$adhtml = self::getAd( $related, $pageId, $intlSite, $bucket );
			pq("#relatedwikihows")->append($adhtml);
		}

		// tips
		$tipsTarget = 'div#' . mb_strtolower( wfMessage( 'tips' )->text() );
		if ( pq( $tipsTarget )->length ) {
			$adHtml = self::getAd( $tips, $pageId, $intlSite, $bucket );
			if ( $adHtml ) {
				pq( $tipsTarget )->append( $adHtml );
			}
		}

		// warnings
		$warningsTarget = 'div#' . mb_strtolower( wfMessage( 'warnings' )->text() );
		if ( pq( $warningsTarget )->length ) {
			$adHtml = self::getAd( $warnings, $pageId, $intlSite, $bucket );
			if ( $adHtml ) {
				pq( $warningsTarget )->append( $adHtml );
			}
		}

		// page bottom
		$adHtml = self::getAd( $bottomOfPage, $pageId, $intlSite, $bucket );
		if ( $adHtml && pq( '#article_rating_mobile' )->length > 0 ) {
			$bottomAdContainer = Html::element( 'div', ['id' => 'pagebottom'] );
			pq( '#article_rating_mobile' )->after( $bottomAdContainer );
			pq( '#pagebottom' )->append( $adHtml );
		}
	}

    private static function getAdSlotData( $pageId ) {
        $slotData = array(
            'en' => array(
                1 => 6567556784,
                2 => 8593674977,
                3 => 1175996171,
                4 => 4606524976,
                5 => 7559991377,
                6 => 6593572945,
                7 => 4795821799,
                8 => 1978086769,
                9 => 7093847927,
            ),
            'intl' => array(
                1 => 9341199379,
                2 => 1070408177,
                3 => 2652729373,
                4 => 1817932573,
                5 => 3294665778,
                7 => 1995224010,
                8 => 8549379291,
                9 => 8433829222,
            ),
        );

        // now let hooks alter it
		Hooks::run( 'GoogleAmpAfterGetSlotData', array( &$slotData ) );

        return $slotData;
    }

	//given the language code, ad number and page id, determine ad type
	private static function getAdType( $num, $pageId, $intl ) {
		// setup by language, then by ad number (0 is default) then by ad type (adsense or gpt)
		$testSetup = [
			'en' => [
				0 => ['gpt' => 100],
				1 => ['adsense' => 100],
			],
			'intl' => [
				0 => ['adsense' => 100],
				1 => ['adsense' => 100],
			]
		];

		$lang = 'en';
		if ( $intl ) {
			$lang = 'intl';
		}

		// default types for this lang
		$types = $testSetup[$lang][0];
		if ( isset( $testSetup[$lang][$num] ) ) {
			$types = $testSetup[$lang][$num];
		}

		$group = $pageId % 100;

		$total = 0;
		foreach ( $types as $adType => $split ) {
			$total += $split;
			if ( $group < $total ) {
				return $adType;
			}
		}
		return "";
	}

	public static function getAd( $num, $pageId, $intl, $bucket, $methodNumber = 0 ) {
		$adType = self::getAdType( $num, $pageId, $intl );

		if ( $adType == "adsense" ) {
			return self::getAdsenseAd( $num, $intl );
		}

		if ( $adType == 'gpt' ) {
			return self::getGPTAd( $num, $intl, $bucket, $methodNumber );
		}
	}

	public static function getGPTAdSlot( $num, $intl, $bucket, $methodNumber = 0 ) {
		global $wgLanguageCode;

		// no DFP ads for the intro
		if ( $num == 1 ) {
			return '';
		}

		$useEnAdNames = false;
		// for now use new ad names only for buckets 11-24 but in the future we will make this the default
		if ( AlternateDomain::onAlternateDomain() ) {
			$adSlots = [
				2 => '/10095428/altd/altd_gam_amp_step2',
				3 => '/10095428/altd/altd_gam_amp_step5',
				4 => '/10095428/altd/altd_gam_amp_meth1',
				5 => '/10095428/altd/altd_gam_amp_relat',
				7 => '/10095428/altd/altd_gam_amp_tipps',
				8 => '/10095428/altd/altd_gam_amp_warns',
				9 => '/10095428/altd/altd_gam_amp_bottm',
			];

			// method
			if ( $num == 4 ) {
				if ( $methodNumber > 0 ) {
					$slot = '/10095428/altd/altd_gam_amp_meth'.$methodNumber;
				}
			} else {
				$slot = $adSlots[$num];
			}
		} else if ( $wgLanguageCode == 'en' ) {
			$adSlots = [
				2 => '/10095428/engl/engl_gam_amp_step2',
				3 => '/10095428/engl/engl_gam_amp_step5',
				4 => '/10095428/engl/engl_gam_amp_meth1',
				5 => '/10095428/engl/engl_gam_amp_relat',
				7 => '/10095428/engl/engl_gam_amp_tipps',
				8 => '/10095428/engl/engl_gam_amp_warns',
				9 => '/10095428/engl/engl_gam_amp_bottm',
				10 => '/10095428/engl/engl_gam_amp_step8',
				11 => '/10095428/engl/engl_gam_amp_stp11',
				12 => '/10095428/engl/engl_gam_amp_stp14',
			];

			// method
			if ( $num == 4 ) {
				if ( $methodNumber > 0 ) {
					$slot = '/10095428/engl/engl_gam_amp_meth'.$methodNumber;
				}
			} else {
				$slot = $adSlots[$num];
			}
		} else {
			$adSlots = [
				2 => '/10095428/june19_amp_step',
				3 => '/10095428/june19_amp_step_2',
				4 => '/10095428/june19_amp_method_1',
				5 => '/10095428/matt_test_RwH_1',
				7 => '/10095428/AMP_DFP_Ad_for_Tips',
				8 => '/10095428/AMP_DFP_Ad_for_Warnings',
				9 => '/10095428/AMP_DFP_Ad_for_Bottom_of_Page',
			];

			// method
			if ( $num == 4 ) {
				if ( $methodNumber > 0 ) {
					$slot = '/10095428/june19_amp_method_'.$methodNumber;
				}
			} else {
				$slot = $adSlots[$num];
			}
		}

		return $slot;

	}

	public static function getGPTAd( $num, $intl, $bucket, $methodNumber = 0 ) {
		global $wgLanguageCode;
		$slot = self::getGPTAdSlot( $num, $intl, $bucket, $methodNumber );
		$whAdLabelBottom = Html::element( 'div', [ 'class' => 'ad_label_bottom' ], "Advertisement" );
		$whAdClass = "wh_ad wh_ad_steps";

		$dataLoadingStrategy = null;
		$whAdLabelBottom = "";
		$bucketId = sprintf( "%02d", $bucket );
		$format = 'amp';
		$ctx = RequestContext::getMain();
		if ( !GoogleAmp::hasAmpParam( $ctx->getRequest() ) ) {
			$format = 'mat';
		}
		$isCoppa = 'false';
		if ( AdCreator::isChildDirectedPage() ) {
			$isCoppa = 'true';
		}

		$targeting = ['targeting' => [
			'bucket' => $bucketId,
			'language' => $wgLanguageCode,
			'coppa' => $isCoppa,
			'format' => $format,
		]];

		$targeting = json_encode( $targeting );

		// width auto with will let the ad be centered
		// have to use multi size to request the 300x250 ad we want
		// setting multi size validation to false so the ad shows up on tablets
		$setSize = array(
			'width' => 300,
			'height' => 250,
			'type' => 'doubleclick',
			'data-slot' => $slot,
			'json' => $targeting,
		);

		$setSize['rtc-config'] = '{"vendors": {"aps":{"PUB_ID": "3271","PARAMS":{"amp":"1"}}}}';

		// this is a layout we never got working but
		// it has some interesting media queries worth remembering
		$noSize = array(
			'width' => 728,
			'height' => 250,
			'type' => 'doubleclick',
			'data-slot' => $slot,
			'data-multi-size' => '300x250,728x90',
			'sizes' => "(max-width: 600px) 300px, 100vw",
			'heights' => "(min-width:600px) 100px, 100%",
			'data-multi-size-validation'=>'false',
		);


		// the fluid ad would be great as it is described in documentation but it does not work..
		$fluid = array(
			'layout' => 'fluid',
			'height' => 'fluid',
			'type' => 'doubleclick',
			'data-slot' => '/10095428/AMP_Test_Fluid',
		);

		$adAttributes = $setSize;

		if ( $dataLoadingStrategy ) {
			$adAttributes['data-loading-strategy'] = $dataLoadingStrategy;
		}
		//$adAttributes['data-block-on-consent'] = "_till_responded";
		$adAttributes['data-block-on-consent'] = "";
		//$adAttributes['data-block-on-consent'] = "_auto_reject";

		$ad = Html::element( "amp-ad", $adAttributes );

		$content = $ad . $whAdLabelBottom;

		$whAd = Html::rawElement( "div", [ 'class' => $whAdClass ], $content );

		return $whAd;
	}

	public static function getAdsenseAd( $num, $intl ) {
		global $wgTitle;
		$pageId = 0;
		if ( $wgTitle ) {
			$pageId = $wgTitle->getArticleID();
		}
		// default values;
		$slot = null;
		$height = 120;
		$width = 'auto';
		$layout = 'fixed-height';
		$whAdClass = "wh_ad";
		$slotType = 'en';
        if ( $intl ) {
            $slotType = 'intl';
        }
        $slotData = self::getAdSlotData( $pageId );
        $slot = $slotData[$slotType][$num];


		// the class is called ad_label_mobile in our main code so leaving it the same for now
		$whAdLabelBottom = "";

		$adsenseChannel = array();
		if ( !ArticleTagList::hasTag( 'amp_disabled_pages', $pageId ) ) {
			$adsenseChannel[] = 4198383040;
		}

		$dataLoadingStrategy = 'prefer-viewability-over-views';

		// intro ad
		if ( $num == 1 ) {
			$height = 120;
			$whAdClass .= " wh_ad_intro";
		}

		// after first step ad
		if ( $num == 2 ) {
			$height = 120;
			$whAdClass .= " wh_ad_step";
		}

		// after fifth step ad
		if ( $num == 3 ) {
			$height = 120;
			$whAdClass .= " wh_ad_step";
		}

		// method ad
		if ( $num == 4 ) {
			$height = 280;
			$whAdClass .= " wh_ad_steps";
			$whAdLabelBottom = Html::element( 'div', [ 'class' => 'ad_label_bottom' ], "Advertisement" );
		}

		// inside related wikihows ad
		if ( $num == 5 ) {
			$height = 280;
			$whAdClass .= " wh_ad_related";
		}

		// test inside other steps ad
		if ( $num == 6 ) {
			$height = 120;
			$whAdClass .= " wh_ad_step";
		}

		if ( !$slot) {
			return "";
		}

		$adAttributes = array(
			'layout' => $layout,
			'width' => $width,
			'height' => $height,
			'type' => 'adsense',
			'data-ad-client' => 'ca-pub-9543332082073187',
			'data-ad-slot' => $slot,
		);

		if ( !empty( $adsenseChannel ) ) {
			$adAttributes['data-ad-channel'] = implode( ",", $adsenseChannel );
		}
		if ( $dataLoadingStrategy ) {
			$adAttributes['data-loading-strategy'] = $dataLoadingStrategy;
		}

		$ad = Html::element( "amp-ad", $adAttributes );

		$content = $ad . $whAdLabelBottom;

		$whAd = Html::rawElement( "div", [ 'class' => $whAdClass ], $content );

		return $whAd;
	}

}

