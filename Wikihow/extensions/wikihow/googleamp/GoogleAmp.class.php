<?php

class GoogleAmp {

	const RELATED_IMG_WIDTH = 360;
	const RELATED_IMG_HEIGHT = 231;
	const QUERY_STRING_PARAM = "amp";

	public static function isAmpMode( $out ) {
		$amp = $out->getRequest()->getVal( self::QUERY_STRING_PARAM ) == 1;

		// Don't enable AMP mode for certain android app requests
		if (class_exists('AndroidHelper')
			&& AndroidHelper::isAndroidRequest()
			&& (!$out->getTitle()->inNamespace(NS_MAIN) || $out->getTitle()->isMainPage())) {
			$amp = false;
		}

		return $amp;
	}
	public static function hasAmpParam( $request ) {
		$amp = $request->getVal( self::QUERY_STRING_PARAM ) == 1;
		return $amp;
	}
	public static function getAmpUrl( $title ) {
		return $title->getFullURL()."?".self::QUERY_STRING_PARAM."=1";
	}

	public static function addAmpStyle( $style, $out ) {
		// remove any important tags which are not valid in amp
		$style = str_replace( "!important", "", $style );
		if (class_exists('ArticleQuizzes')) {
			global $wgTitle;
			$articleQuizzes = new ArticleQuizzes($wgTitle->getArticleID());
			if (count($articleQuizzes::$quizzes) > 0) {
				$style .= Misc::getEmbedFile('css', __DIR__ . '/../quiz/quiz.css');
			}
		}
		//remove data urls from the style_top since they are very large
		$style = preg_replace("@background-image:url\(\"*data[^\)]*\);@", "", $style );
		$style .= Misc::getEmbedFile('css', __DIR__ . '/ampstyle.css');
		$style .= Misc::getEmbedFile('css', __DIR__ . '/../socialproof/mobilesocialproof.css');

		if (class_exists('SocialFooter')) {
			$style .= Misc::getEmbedFile('css', __DIR__ . '/../SocialFooter/assets/social_footer.css');
		}

		// If this is an android app request, add the android styles
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$style .= str_replace("!important", "", Misc::getEmbedFile('css', __DIR__ . '/../android_helper/android_helper.css'));
		}

		// Strip CSS comments (containing ResourceLoader debug info)
		$style = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!' , '' , $style );

		$style = HTML::inlineStyle($style);
		$style = str_replace( "<style>", "<style amp-custom>", $style);
		$out->addHeadItem('topcss', $style);
	}

	/*
	 * get amp video element given our video html element
	 * @param string video the video element made by WHVid
	 * @return string http for amp video and possibly a wrapper for controls
	 */
	public static function getAmpVideo( $video, $summaryIntroHeadingText )  {
		$result = '';

		$isSummaryVideo = $video->attr( 'data-summary' ) == true;
		$width = 460;
		$height = 260;
		if ( $isSummaryVideo ) {
			$result = GoogleAmp::getAmpSummaryVideo( $video, $width, $height, $summaryIntroHeadingText );
		} else {
			$result = GoogleAmp::getAmpArticleVideo( $video, $width, $height );
		}
		return $result;
	}

	/*
	 * get amp video element given a video url
	 * @param pq object video
	 * @return string http amp-video element
	 */
	public static function getAmpArticleVideo( $video, $width, $height )  {
		$src = $video->attr( 'data-src' );
		$poster = $video->attr( 'data-poster' );
		$src = WH_CDN_VIDEO_ROOT . $src;
		// this is the dev url but we only need it if the video was also run on fred
		//$src  = 'https://d2mnwthlgvr25v.cloudfront.net'.$src;
		$attr = [
			'src' => $src,
			'width' => $width,
			'height' => $height,
			'layout' => 'responsive',
			'poster' => $poster,
			'class' => 'm-video',
			'autoplay',
			'loop'
		];
		$video = Html::element( "amp-video", $attr );
		return $video;
	}
	/*
	 * get amp video element given a video url for a summary video
	 * @param pq object video
	 * @return string http amp-video element
	 */
	private static function getAmpSummaryVideo( $video, $width, $height, $sectionName )  {
		global $wgTitle, $wgCanonicalServer;

		if ( Misc::isAltDomain() ) {
			// Use original inline video
			$src = $video->attr( 'data-src' );
			$poster = $video->attr( 'data-poster' );
			$id = $video->attr( 'id' );
			$overlayId = $id . '-overlay';
			$src = WH_CDN_VIDEO_ROOT . $src;
			// this is the dev url but we only need it if the video was also run on fred
			//$src  = 'https://d2mnwthlgvr25v.cloudfront.net'.$src;
			$attr = [
				'id' => $id,
				'src' => $src,
				'width' => $width,
				'height' => $height,
				'layout' => 'responsive',
				'class' => 'm-video',
				'poster' => $poster,
				'controls',
			];
			$ampVideo = Html::element( "amp-video", $attr );
			$overlayContents = WHVid::getSummaryIntroOverlayHtml( $sectionName, $wgTitle );
			$posterAttributes = array(
				'class' => 'video-poster-image',
				'layout' => 'fill',
				'src' => $poster,
			);
			$poster = Html::element( 'amp-img', $posterAttributes );
			$overlayContents = $overlayContents . $poster;
			$overlay = Html::rawElement(
				'div',
				[
					'id' => $overlayId,
					'role' => 'button',
					'on' => "tap:$overlayId.hide, $id.play",
					'tabindex' => 0
				],
				$overlayContents
			);
			$videoPlayer = Html::rawElement( 'div', [ 'class' => 'summary-video' ], $ampVideo . $overlay );
			return $videoPlayer;
		}

		// Trevor - use link to VideoBrowser
		$href = "{$wgCanonicalServer}/Video/" . str_replace( ' ', '-', $wgTitle->getText() );
		return Html::rawElement( 'div',
			[ 'class' => 'summary-video' ],
			Html::rawElement( 'a',
				[
					'class' => 'click-to-play-overlay',
					'role' => 'button',
					'href' => $href,
					'tabindex' => 0
				],
				WHVid::getSummaryIntroOverlayHtml( $sectionName, $wgTitle ) .
					Html::element( 'amp-img',
						[
							'class' => 'video-poster-image',
							'layout' => 'fill',
							'src' => $video->attr( 'data-poster' ),
						]
					)
			)
		);
	}

	// creates the amp-img that will be placed in the main article
	// it is different from getAmpImg because it will use srcset
	// and does not take in an "img" html object as it's argument
	public static function getAmpArticleImg( $src, $width, $height, $srcSet = null, $layout = "responsive" )  {
		$attr = [
			'src' => $src,
			'width' => $width,
			'height' => $height,
			'layout' => $layout,
		];
		if ( $srcSet ) {
			$attr['srcset'] = $srcSet;
		}
		$img = Html::element( "amp-img", $attr );
		return $img;

	}

	public static function getAmpImg( $node, $layout = 'responsive' )  {
		$img = pq( $node );
		$ampImg = pq( '<amp-img></amp-img>' );

		$src = $img->attr( 'src' );
		$ampImg->attr( 'src', $src );

		$width = $img->attr( "width" );
		$height = $img->attr( "height" );
		$ampImg->attr( 'width', $width );
		$ampImg->attr( 'height', $height );

		$ampImg->attr( 'layout', $layout );
		$ampImg->attr( 'alt', $img->attr( 'alt' ) );

		return $ampImg;
	}

	// gets the img tag for amp images
	public static function makeRelatedAmpImg( $image ) {
		return self::makeAmpImg( $image, self::RELATED_IMG_WIDTH, self::RELATED_IMG_HEIGHT );
	}

	public static function makeAmpImg( $image, $width, $height, $pageId = null ) {
		$thumb = $image->getThumbnail( $width, $height );
		return self::makeAmpImgElement($thumb->getUrl(), $thumb->getWidth(), $thumb->getHeight());
	}

	public static function makeAmpImgElement( $image_path, $width, $height ) {
		return Html::element(
			"amp-img",
			[
				'layout'=>'responsive',
				"src" => wfGetPad( $image_path ),
				"width" => $width,
				"height" => $height
			]
		);
	}

	public static function addRelatedWikihows( $related_boxes ) {
		pq("#relatedwikihows *")->remove();
		$relatedwikihows = pq("#relatedwikihows")->addClass( 'related_wh_amp' );
		$currentBox = null;
		$relatedwikihows->prepend('<div class="related_boxes"></div>');
		$currentBox = pq("#relatedwikihows .related_boxes:last");
		foreach ($related_boxes as $box) {
			$ampImg = self::makeRelatedAmpImg( $box->imgFile );
			$url = $box->url;
			$currentBox->append("<a href='$url' class='related_box_amp'>".$ampImg."<p><span>How to </span>$box->name</p></a>");
		}
		$currentBox->append( '<div class="clearall"></div>' );
	}

	public static function addHeadItems( $out ) {
		$out->addHeadItem( 'ampboilerplate',
			'<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>' );
		$out->addHeadItem( 'ampscript', '<script async src="https://cdn.ampproject.org/v0.js"></script>' );
		$out->addHeadItem( 'ampadscript', '<script async custom-element="amp-ad" src="https://cdn.ampproject.org/v0/amp-ad-0.1.js"></script>' );
		$out->addHeadItem( 'ampanalytics',
			'<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>' );
		$out->addHeadItem( 'ampsidebar',
			'<script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>' );
		$out->addHeadItem( 'ampform',
			'<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>' );
		$out->addHeadItem( 'ampconsent', '<script async custom-element="amp-consent" src="https://cdn.ampproject.org/v0/amp-consent-0.1.js"></script>' );
		$out->addHeadItem( 'ampgeo', '<script async custom-element="amp-geo" src="https://cdn.ampproject.org/v0/amp-geo-0.1.js"></script>' );

		// check for wikivideo
		foreach ( $out->getTemplateIds() as $template ) {
			if ( is_array( $template ) ) {
				foreach ( $template as $key => $val ) {
					if ( strcasecmp( $key, "whvid" ) == 0 ) {
						$out->addHeadItem( 'ampvideo',
							'<script async custom-element="amp-video" src="https://cdn.ampproject.org/v0/amp-video-0.1.js"></script>' );
					}
				}
			}
		}
	}

	public static function onAmpEmbedVideoParserOutputHook( $outputPage, $parserOutput ) {
		if ( !self::isAmpMode( $outputPage ) ) {
			return;
		}
		$outputPage->addHeadItem( 'amp-youtube',
			'<script async custom-element="amp-youtube" src="https://cdn.ampproject.org/v0/amp-youtube-0.1.js"></script>' );
		$outputPage->addHeadItem( 'amp-accordion',
			'<script async custom-element="amp-accordion" src="https://cdn.ampproject.org/v0/amp-accordion-0.1.js"></script>' );
	}

	private static function isValidLink(DOMElement $link): bool {
		/* Examples of real, valid links

		1. No href
			<a id="qa_edit" class="editsection">Edit</a>
			<a name="step_1_5" class="stepanchor"></a>
			<a name="Preventing_Further_Injury_sub" class="anchor"></a>
			<a class="qa_edit_submitted button secondary">Answer this question</a>

		2. Relative href
			<a href='/Paint-a-Bike'><img alt="Paint a Bike" [...]</a>
			<a href="#/Image:Hug-Step-3.jpg" class="image lightbox" [...]</a>
			<a href="#Questions_and_Answers_sub" id="qa_toc" >Community Q&amp;A</a>
			<a href="#_note-3">[3]</a>

		3. '#' href
			<a href="#" id="tab_admin" submenuname="AdminOptions">[...]</a>
			<a href="#" data-option="answered">Already answered</a>

		4. Protocol-relative href
			<a href="//es.wikihow.com/dar-un-abrazo">dar un abrazo</a>

		5. Absolute href
			<a href="http://es.wikihow.com/dar-un-abrazo">dar un abrazo</a>
		 */

		$href = $link->getAttribute('href');
		$name = $link->getAttribute('name');
		$id = $link->getAttribute('id');
		$class = $link->getAttribute('class');

		if (!$href) {
			return !empty($name . $id . $class); // 1. No href
		}

		$firstChar = substr($href, 0, 1);
		$relativePath = in_array($firstChar, ['/', '#']); // 2/3. Relative or '#' href
		if ($relativePath) {
			$relativeProtocol = substr($href, 0, 2) == '//'; // 4. Protocol-relative href
			if (!$relativeProtocol) {
				return true;
			} else {
				$href = 'https://' . substr($href, 2); // For validation purposes
			}
		}
		return preg_match('/^https?:/', $href) // 4/5. Protocol-relative or absolute URL
			&& filter_var($href, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Fix broken <a> tags
	 */
	private static function fixBrokenLinks() {
		foreach (pq('a') as $link) {
			if (!self::isValidLink($link, true)) {
				pq($link)->replaceWith($link->textContent);
			}
		}

	}

	/**
	 * Add an <amp-analytics> element to the <body>
	 *
	 * @param $attribs Element attributes
	 * @param $config  AMP configuration
	 */
	private static function addAnalyticsElement( array $attribs, array $config ) {
		$jsonObject = json_encode( $config, JSON_PRETTY_PRINT );
		$scriptElement = Html::element( 'script', [ 'type' => 'application/json' ], $jsonObject );
		$ampElement = Html::rawElement( 'amp-analytics', $attribs, $scriptElement );
		// for some reason if you put this way down in the page it doesn't work in my testing
		pq( 'div:first' )->after( $ampElement );
	}

	private static function addConsentElement() {
		$config = [
			'ISOCountryGroups' => [
				'eu' => ["al", "ad", "am", "at", "by", "be", "ba", "bg", "ch", "cy", "cz", "de", "dk", "ee", "es", "fo", "fi", "fr", "gb", "ge", "gi", "gr", "hu", "hr", "ie", "is", "it", "lt", "lu", "lv", "mc", "mk", "mt", "no", "nl", "po", "pt", "ro", "ru", "se", "si", "sk", "sm", "tr", "ua", "uk", "va"]
			]
		];


		global $wgRequest;
		// for testings
		$eu = $wgRequest->getVal( "EU" ) == 1;
		if ( $eu ) {
			$config = [
				'ISOCountryGroups' => [
					'eu' => ["us", "jp", "al", "ad", "am", "at", "by", "be", "ba", "bg", "ch", "cy", "cz", "de", "dk", "ee", "es", "fo", "fi", "fr", "gb", "ge", "gi", "gr", "hu", "hr", "ie", "is", "it", "lt", "lu", "lv", "mc", "mk", "mt", "no", "nl", "po", "pt", "ro", "ru", "se", "si", "sk", "sm", "tr", "ua", "uk", "va"]
				]
			];
		}
		$jsonObject = json_encode( $config, JSON_PRETTY_PRINT );
		$scriptElement = Html::element( 'script', [ 'type' => 'application/json' ], $jsonObject );
		$ampElement = Html::rawElement( 'amp-geo', ['layout'=>'nodisplay'], $scriptElement );
		pq( 'div:first' )->after( $ampElement );

		$messageInner = wfMessage("gdpr_message")->text();
		if ( strpos( $messageInner, '[[' ) !== FALSE ) {
			$messageInner = wfMessage("gdpr_message")->parse();
		}
		$vars = array(
			'gdpr_message' => $messageInner,
			'gdpr_accept' => wfMessage("gdpr_accept")->text()
		);
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader( __DIR__ )
		]);
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'amp_consent.mustache', $vars );
		pq( 'div:first' )->after( $html );
	}

	private static function addGoogleAnalytics( $id, $num ) {
		$config = [
			'vars'=> [ 'account' => $id ],
			'triggers' => [
				'defaultPageview' => [
					'on' => 'visible',
					'request' => 'pageview',
				]
			]
		];
		$attribs = [ 'type' => 'googleanalytics', 'id' => 'analytics' . $num ];
		self::addAnalyticsElement( $attribs, $config );
	}

	/**
	 * Add a snippet to ping Fastly. This way we can track page views on Titus.
	 */
	private static function addFastlyPing() {
		global $wgIsDevServer, $wgLanguageCode;

		if ($wgIsDevServer) {
			return;
		}

		$domain = wfCanonicalDomain( $wgLanguageCode, true );

		$config = [
			'requests' => [
				'pageview' => '//' . $domain . '/x/amp-view?url=${sourcePath}'
			],
			'triggers' => [
				'trackPageview' => [
					'on' => 'visible',
					'request' => 'pageview',
				]
			]
		];

		self::addAnalyticsElement( [ 'id' => 'fastly-amp-ping' ], $config );
	}

	/**
	 * Adjusted bounce rate (https://moz.com/blog/adjusted-bounce-rate)
	 */
	private static function addAdjustedBounceRatePing(string $account, array $abrCnf) {
		$attribs = [
			'type' => 'googleanalytics',
			'id' => 'abr-amp-ping'
		];
		$config = [
			"vars" => [ "account" => $account ],
			"triggers" => [
				"pageTimer" => [
					"on" => "timer",
					"timerSpec" => [
						"immediate" => false,
						"interval" => $abrCnf['timeout'] * 2, // prevents triggering twice
						"maxTimerLength" => $abrCnf['timeout'],
					],
					"request" => "event",
					"vars" => [
						"eventCategory" => $abrCnf['eventCategory'],
						"eventAction" => $abrCnf['eventAction']
					]
				]
			]
		];

		self::addAnalyticsElement($attribs, $config);
	}

	public static function addAmpHtmlLink( $out, $languageCode ) {
		// check a tag list to turn off amp
		if ( ArticleTagList::hasTag( 'amp_disabled_pages', $out->getTitle()->getArticleID() ) ) {
			return;
		}
		if ( self::isAmpMode( $out ) || !WikihowSkinHelper::shouldShowMetaInfo($out) ) {
			return;
		}
		$serverUrl =  Misc::getLangBaseURL( $languageCode, true );
		$ampUrl = wfExpandUrl( $serverUrl . '/' . $out->getTitle()->getPrefixedURL(), PROTO_CANONICAL );
		$ampUrl =  $ampUrl . "?amp=1";

		$out->addLink( array( 'rel' => 'amphtml', 'href' => $ampUrl ) );
	}

	public static function insertAMPAds() {
		global $wgLanguageCode, $wgTitle;
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
		$testStep = 6;
		$tips = 7;
		$warnings = 8;
		$bottomOfPage = 9;

		$hasIntroAd = true;

		if ( $hasIntroAd == true ) {
			$adhtml = wikihowAds::rewriteAdCloseTags( self::getAd( $intro, $pageId, $intlSite ) );
			pq( "#intro" )->append( $adhtml );

			// put an ad after second step if there is more than 1 step in first method
			if ( pq( ".steps_list_2:first > li" )->length > 1 ) {
				$adhtml = wikihowAds::rewriteAdCloseTags( self::getAd( $firstStep, $pageId, $intlSite ) );
				pq(".steps_list_2:first > li:eq(1)")->append( $adhtml );
			}
		} else {
			// put an ad after first step if there is more than 1 step in first method
			if ( pq( ".steps_list_2:first > li" )->length > 1 ) {
				$adhtml = wikihowAds::rewriteAdCloseTags( self::getAd( $firstStep, $pageId, $intlSite ) );
				pq(".steps_list_2:first > li:eq(0)")->append( $adhtml );
			}
		}


		// put an ad after fifth step if there is more than 5 steps in first method
		if ( pq( ".steps_list_2:first > li" )->length > 5 ) {
			$adhtml = wikihowAds::rewriteAdCloseTags( self::getAd( $fifthStep, $pageId, $intlSite ) );
			pq(".steps_list_2:first > li:eq(4)")->append( $adhtml );
		}

		// ad in last step of each method
		$methodNumber = 1;
		foreach ( pq(".steps:not('.sample') .steps_list_2 > li:last-child") as $lastStep ) {
			$adhtml = wikihowAds::rewriteAdCloseTags( self::getAd( $method, $pageId, $intlSite, $methodNumber ) );
			pq( $lastStep )->append( $adhtml );
			$methodNumber++;
		}

		$relatedsname = RelatedWikihows::getSectionName();
		if ( pq("#{$relatedsname}")->length ) {
			$adhtml = wikihowAds::rewriteAdCloseTags( GoogleAmp::getAd( $related, $pageId, $intlSite ) );
			pq("#{$relatedsname}")->append($adhtml);
		} elseif ( pq("#relatedwikihows")->length ) {
			$adhtml = wikihowAds::rewriteAdCloseTags( GoogleAmp::getAd( $related, $pageId, $intlSite ) );
			pq("#relatedwikihows")->append($adhtml);
		}

		// tips
		$tipsTarget = '#' . strtolower( wfMessage( 'tips' )->text() );
		if ( pq( $tipsTarget )->length ) {
			$adHtml = wikihowAds::rewriteAdCloseTags( GoogleAmp::getAd( $tips, $pageId, $intlSite ) );
			if ( $adHtml ) {
				pq( $tipsTarget )->append( $adHtml );
			}
		}

		// warnings
		$warningsTarget = '#' . strtolower( wfMessage( 'warnings' )->text() );
		if ( pq( $warningsTarget )->length ) {
			$adHtml = wikihowAds::rewriteAdCloseTags( GoogleAmp::getAd( $warnings, $pageId, $intlSite ) );
			if ( $adHtml ) {
				pq( $warningsTarget )->append( $adHtml );
			}
		}

		// page bottom
		$adHtml = wikihowAds::rewriteAdCloseTags( GoogleAmp::getAd( $bottomOfPage, $pageId, $intlSite ) );
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
				0 => ['adsense' => 1, 'gpt' => 99],
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

	public static function getAd( $num, $pageId, $intl, $methodNumber = 0 ) {
		$adType = self::getAdType( $num, $pageId, $intl );

		if ( $adType == "adsense" ) {
			return self::getAdsenseAd( $num, $intl );
		}

		if ( $adType == 'gpt' ) {
			return self::getGPTAd( $num, $intl, $methodNumber );
		}
	}

	public static function getGPTAd( $num, $intl, $methodNumber = 0 ) {
		global $wgLanguageCode, $wgTitle;
		$pageId = 0;
		if ( $wgTitle ) {
			$pageId = $wgTitle->getArticleID();
		}
		$intlSite = $wgLanguageCode != 'en';
		$whAdClass = "wh_ad";
		$whAdLabelBottom = "";
		$dataLoadingStrategy = null;
		$slot = '/10095428/AMP_Test_1';

		if ( $intlSite ) {
			$slot = '/10095428/AMP_Test_2';
		}
		// no DFP ads for the intro
		if ( $num == 1 ) {
			// leaving this here in case we add DFP to intro
			//$slot = '/10095428/june19_amp_intro';
			return '';
		}

		if ( $num == 2 ) {
			$slot = '/10095428/june19_amp_step';
		}
		if ( $num == 3 ) {
			$slot = '/10095428/june19_amp_step_2';
		}
		// method
		if ( $num == 4 ) {
			// figure out which method
			$slot = '/10095428/june19_amp_method_1';
			if ( $methodNumber > 0 ) {
				$slot = '/10095428/june19_amp_method_'.$methodNumber;
			}
		}
		if ( $num == 5 ) {
			$slot = '/10095428/matt_test_RwH_1';
		}

		if ( $num == 7 ) {
			$slot = '/10095428/AMP_DFP_Ad_for_Tips';
		}
		if ( $num == 8 ) {
			$slot = '/10095428/AMP_DFP_Ad_for_Warnings';
		}
		if ( $num == 9 ) {
			$slot = '/10095428/AMP_DFP_Ad_for_Bottom_of_Page';
		}

		$whAdLabelBottom = Html::element( 'div', [ 'class' => 'ad_label_bottom' ], "Advertisement" );
		$whAdClass .= " wh_ad_steps";

		// width auto with will let the ad be centered
		// have to use multi size to request the 300x250 ad we want
		// setting multi size validation to false so the ad shows up on tablets
		$setSize = array(
			'width' => 300,
			'height' => 250,
			'type' => 'doubleclick',
			'data-slot' => $slot,
		);

		if ( $num == 7 || $num == 8 || $num == 9 ) {
			$setSize['rtc-config'] = '{"vendors": {"aps":{"PUB_ID": "3271","PARAMS":{"amp":"1"}}}}';
		}

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

	public static function fixSampleImages() {
		foreach ( pq( '.sd_thumb img' ) as $img ) {
			// Trevor, 6/18/18 - Now that sample images are deferred they need to be treated the
			// same way article images are, specifically that the src is stored in data-src to be
			// loaded when scrolled into view
			$img = pq( $img );
			$img->attr( 'src', $img->attr( 'data-src' ) );
			$ampImg = self::getAmpImg( $img, 'responsive' );
			$imgWrap = Html::rawElement( 'div',  ['class' => 'sd_img_wrap'], $ampImg );
			pq( $img )->replaceWith( $imgWrap );
		}
	}

	function getImageCredits( $imageCreators ) {
		if ( !$imageCreators ) {
			return "";
		}

		//get image credits
		$uploadedBy = Html::element( 'span', ['class' => 'info_label'], "Uploaded by: " );
		$licenseBy = Html::element( 'span', ['class' => 'info_label'], "License: " );
		$listItems = '';
		foreach ( $imageCreators as $uploader => $image ) {
			if ( $uploader == "Wikivisual" ) {
				$uploader .= " (wikiHow)";
				$license = 'cc-by-sa-nc-3.0-self';
			} else {
				//look up license in the longer way
				$wikiPage = WikiPage::factory($image);
				$wikitext = ContentHandler::getContentText( $wikiPage->getContent() );
				if (preg_match('@{{(cc-by[^}]+)}}@', $wikitext, $m)) {
					$license = $m[1];
					// From http://creativecommons.org/licenses/
					$licenseTexts = array(
						'cc-by-sa-nc-2.5-self' => 'Creative Commons Share-Alike Non-Commercial Attribution',
					);
					if (isset($licenseTexts[ $license ])) {
						$license = $licenseTexts[$license];
					}
				} elseif (preg_match('@{{flickr[^}]+\|([^|}]+)}}@', $wikitext, $m)) {
					$user = $m[1] . ' (Flickr)';
				}
			}
			$listItems .= Html::rawElement( 'li', [], $uploadedBy . $uploader );
			if ( $license ) {
				$listItems .= Html::rawElement( 'li', [], $licenseBy . $license );
			}
		}
		$iaHtml = Html::rawElement( 'ul', [], $listItems );
		$headline = Html::rawElement( 'span', ['class' => 'mw-headline'], wfMessage( 'image-attribution' )->text() );
		$headline = Html::rawElement( 'h2', [], $headline );
		$sectionText =  Html::rawElement( 'div', ['class' => 'section_text' ], $iaHtml );
		$data = Html::rawElement( 'div', ['class' => 'aidata section' ], $headline.$sectionText );
		return $data;
	}

	private static function getAuthors( $title ) {
		// get authors
		$authors = ArticleAuthors::getAuthors( $title->getArticleID() );
		$authorList = Html::element( 'span', ['class'=>'info_label'], wfMessage( 'thanks_to_all_authors' )->text() );
		$authorList .= "<br>";
		$authorList .= implode( ", ", array_keys( $authors ) );
		$authorHtml = Html::rawElement( 'ol', [], $authorList );
		$headline = Html::rawElement( 'h2', [] );
		$sectionText =  Html::rawElement( 'div', ['class' => 'section_text' ], $authorHtml );
		$data = Html::rawElement( 'div', ['class' => 'aidata section' ], $headline.$sectionText );
		return $data;
	}

	private static function formatQABadges() {

		$badges = pq('.qa_expert, .qa_person_circle');

		foreach ( $badges as $badge ) {
			preg_match("@background-image:\s?url\((.*)\)@", pq($badge)->attr('style'), $m);
			if (!empty($m[1])) {
				$ampImg = self::getAmpArticleImg($m[1], 80, 80);
				pq($badge)->html($ampImg);
				pq($badge)->removeAttr('style');
			}
		}
	}

	private static function modifyVideoSection() {
		$videoSelector = "#video";
		if ( !pq( $videoSelector )->length ) {
			return;
		}
		// make sure the src is a youtube video
		$src = pq( $videoSelector )->find( '.embedvideo:first' )->attr( 'data-src' );
		if ( strstr( $src, "www.youtube.com" ) === false ) {
			return;
		}
		$videoId = end( explode ( '/', $src ) );

		// remove any trailing ?
		$videoId = explode( '?', $videoId );
		$videoId = array_shift( $videoId );
		if (!$videoId) {
			return;
		}
		$attributes = array(
			'width' => 16,
			'height' => 9,
			'layout' => 'responsive',
			'data-videoid' => $videoId,
		);
		$element = Html::element( 'amp-youtube', $attributes );
		$first = true;
		foreach ( pq( $videoSelector )->children() as $child ) {
			if ( $first ) {
				pq($child)->replaceWith( $element );
				$first = false;
			} else {
				pq($child)->remove();
			}
		}
	}

	public static function modifyDom() {

		self::formatQABadges();
		self::modifyVideoSection();
		pq( 'script' )->remove();
		pq( 'mo' )->remove();
		pq( 'annotation' )->remove();
		pq( 'video' )->remove();
		pq( '.img-whvid' )->remove();
		pq( '.vid-whvid' )->remove();
		pq( '.image_details' )->remove();
		pq( '.ar_box_vote' )->removeAttr( 'pageid' );
		pq( '#info_link' )->removeAttr( 'aid');
		pq( '.mh-method-thumbs-template' )->remove();
		pq( 'input:not(".qz_radio, .amp_input")' )->remove();

		pq( '.image' )->attr( 'href', '#' );

		// TODO use youtube of amp which is one of our embed iframes
		pq( 'iframe' )->remove();

		// TODO remove the rmd_container from the code base it's no longer used
		pq( '#rmb_container' )->remove();

		pq( '.edit-page' )->remove();
		pq( '#qa_submit_button' )->remove();
		pq( '.addTipElement' )->remove();

		pq( '#qa_submit_button' )->remove();
		pq( '#qa_edit' )->remove();
		pq( '#qa_edit_done' )->remove();
		pq( '#qa_submitted_questions_container' )->remove();
		pq( '#qa_ask_heading' )->remove();
		pq( '#qa_submitted_question_form' )->remove();
		pq( '#qa_aq_search_heading' )->remove();
		pq( '#qa_add_curated_question' )->remove();
		pq( '#qa_aq_search_container' )->remove();
		pq( '#qa_admin' )->remove();
		pq( '#qa_editor' )->remove();
		pq( '#qa_show_more_submitted' )->remove();
		pq( '.wh_vote_container' )->remove();
		pq( '.qa_editor_tools' )->remove();
		if ( empty( trim( pq( '#qa' )->text() ) ) ) {
			pq( '.qa.section' )->remove();
		}
		pq( '#qa_show_more_answered' )->remove();

		// for some reason some articles use this font-size 0 inline style..removing it for now

		// this does not work without javascript atm
		pq( '.expert_hint_box' )->remove();

		pq( '#social_proof_mobile .sp_box' )->addClass('sp_fullbox');

		//get "Updated:" date (we usually do this via js)
		//self::getUpdated();

		//star ratings
		pq( '#sp_helpful_box' )->remove();
		pq( '#sp_helpful_new' )->remove();

		// remove the "difficult" warning
		pq( '#sp_difficult_box' )->remove();

		// remove any font tags and unwrap them (unwrap does not exist in php query so use this)
		// wrap in while loop in case there is a font tag within a font tag
		while ( pq( 'font' )->length > 0 ) {
			foreach ( pq( 'font' ) as $elem ) {
				pq( $elem )->replaceWith( pq( $elem )->html() );
			}
		}

		foreach ( pq( '.mwe-math-mathml-inline' ) as $elem ) {
			$text = pq( $elem )->attr( 'data-original-text' );
			$text = htmlspecialchars( $text );
			pq($elem)->after( $text );
		}
		pq( '.mwe-math-mathml-inline' )->remove();
		pq( '.mwe-math-fallback-image-inline' )->remove();

		// this would be hidden and can have inline styles so just remove it for now
		pq( '.template_top' )->remove();

		self::fixSampleImages();

		// TODO log any images so we can fix them later??
		pq( 'img' )->remove();
		pq( 'map' )->remove();

		// we don't want these classes on the images, they mess up the amp images and
		// cause them not to show up at all
		pq( '.mwimg-caption-image' )->removeClass( 'mwimg-caption-image' );
		pq( '.mwimg-caption-mobile' )->removeClass( 'mwimg-caption-mobile' );

		// make sure any table border attribute has value 0 or 1
		foreach ( pq( 'table[border]' ) as $elem ) {
			$pqElem = pq($elem);
			$border = $pqElem->attr( 'border' );
			if ( $border != '0' && $border != '1' ) {
				$pqElem->attr( 'border', '0' );
			}
		}

		self::fixBrokenLinks();
		$i = 1;

		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			self::addGoogleAnalytics( WH_GA_ID_ANDROID_APP, $i++ );
		} else {
			self::addGoogleAnalytics( WH_GA_ID, $i++ );
			self::addConsentElement();
		}

		$gaCnf = Misc::getGoogleAnalyticsConfig();
		foreach ( $gaCnf['extraPropertyIds'] as $gaId => $name ) {
			self::addGoogleAnalytics( $gaId, $i++ );

			$abrCnf = $gaCnf['adjustedBounceRate'];
			if ( $abrCnf && in_array( $gaId, $abrCnf['accounts'] ) ) {
				self::addAdjustedBounceRatePing( $gaId, $gaCnf['adjustedBounceRate'] );
			}
		}

		self::changeGDPRInfoLabel();
		self::addFastlyPing();
		self::addNewAnchortags();

		// do this last to make sure we don't add any inline styles or js, and that the doc is as
		// small as possible when we iterate over everything
		pq( '*' )->removeAttr( 'style' )->removeAttr('onload')->removeAttr('clear');
	}

	static function addNewAnchortags() {
		if ( class_exists('MobileTabs') && pq(".summarysection .summary-video")->length > 0 ) {
			//add a new anchor tag so it will jump to the right place
			$anchor = MobileTabs::getSummarySectionAnchorName();
			pq('.summarysection')->attr("id",'')->parent()->attr("id", $anchor);
		}
	}

	static function changeGDPRInfoLabel() {
		foreach ( pq(' .embedvideo_gdpr_label ') as $info ) {
			// make sure the parent of the label is one of the allowed amp accordion types
			if ( !pq( $info )->parent()->is( 'h1, h2, h3, h4, h5, h6, header' ) ) {
				break;
			}
			$message = wfMessage( 'embedvideo-gdpr' )->text();
			$message = Html::element( 'p', ['class' => 'embedvideo_gdpr_p'], $message );
			$p = Html::rawElement( 'span', ['class' => 'embedvideo_gdpr_text'], $message );
			pq( $info )->parent()->addClass( "embedvideo_gdpr_headline" );
			pq( $info )->parent()->wrap( "<amp-accordion>" );
			pq( $info )->parent()->wrap( "<section>" );
			pq( $info )->parent()->after( $p );
		}
		pq( '.embedvideo_gdpr_message' )->remove();
		pq( '.embedvideo_gdpr_label' )->remove();
	}

	// a hook to the math extension to add the original text inside the math tag
	// so we can take it out later for use by amp code
	// does not need to be marked as display none because the content in the math tag
	// is already wrapped in display none
	static function mathHook( $parser, &$renderer, &$renderedMath ) {
		$doc = phpQuery::newDocument( $renderedMath );
		pq( '.mwe-math-mathml-inline' )->attr( 'data-original-text', $renderer->getTex() );
		$renderedMath = $doc->htmlOuter();
		return true;
	}

	// this will add the amp version of a pages url to
	// the list of urls which will be cached
	public static function onTitleSquidURLsPurgeVariants( $title, &$urls ) {
		global $wgLanguageCode;

		if ( $title && $title->exists() && $title->inNamespace(NS_MAIN) ) {
			$ampUrl = $title->getInternalURL();
			$partialUrl = preg_replace("@^(https?:)?//[^/]+/@", "/", $ampUrl );
			$mobile = true;
			$domain = wfCanonicalDomain( $wgLanguageCode, $mobile );
			$ampUrl = "https://" . $domain . $partialUrl . "?" . self::QUERY_STRING_PARAM . "=1";
			$urls[] = $ampUrl;
		}
		return true;
	}

	public static function getAmpSidebar( $items ) {
		// add link to regular mobile version of site
		global $wgTitle;
		$titlePath = $wgTitle->getFullURL();
		$mobileCanonical = $titlePath;

		// extra seperator for gdpr only
		$otherSectionName = wfMessage( 'mobile-frontend-view-other' )->text();
		$items .= Html::element( 'li', ['class' => 'side_header gdpr_only_display'], $otherSectionName );

		// bottom menu search
		$fullSiteText = wfMessage( 'mobile-frontend-view-full-site-wh' )->escaped();
		$fullSiteLink = SkinMinervaWikihow::getMobileMenuFullSiteLink( $fullSiteText, $mobileCanonical );
		$fullSiteLink = Html::rawElement( 'li', [], $fullSiteLink );
		$items .= $fullSiteLink;

		// add the sidebar search
		// $sidebarSearch = self::getSearchBar( "sidebar_search" );
		// $item = Html::rawElement( 'li', [], $sidebarSearch );
		// $items = $item . $items;

		$sidebarContents = Html::rawElement('ul', [], $items );
		$sidebarAttr = [ 'id' => 'top-sidebar', 'layout'=>'nodisplay' ];
		$sidebar = Html::rawElement('amp-sidebar', $sidebarAttr, $sidebarContents );
		return $sidebar;
	}

	public static function getHeaderSidebarButton() {
		$toggle = Html::rawElement( 'div', [ 'id' => 'mw-mf-main-menu-button' ] );
		$toggleAttr = [
			'id' => 'menu-button-wrap',
			'on' => 'tap:top-sidebar.toggle',
			'tabindex' => 0,
			'role' => 'button',
		];
		$toggleWrap = Html::rawElement( 'div', $toggleAttr, $toggle );
		return $toggleWrap;
	}

	private static function getSearchBar( $submitId, $placeholderText = "" ) {

		$inputAttr = [
			"type" => "submit",
			"value" => "",
			"id" => $submitId,
			"class" => 'search_button'
		];
		$button = Html::rawElement( "input", $inputAttr );

		$inputAttr = [
			"type" => "text",
			"class" => "search_text",
			"name" => "search",
			"value" => "",
			"placeholder" => $placeholderText,
			"aria-label" => wfMessage('aria_search')->showIfExists()
		];
		$input = Html::rawElement( "input", $inputAttr );

		$formContents = $input.$button;

		$formAttr  = [
			"method" => "get",
			"target" => "_top",
			"action" => "/wikiHowTo",
		];
		$form = Html::rawElement( "form", $formAttr, $formContents );

		return $form;
	}

	public static function renderFooter( $data ) {
		$creature = MinervaTemplateWikihow::getFooterCreatureArray()[rand(0,count(MinervaTemplateWikihow::getFooterCreatureArray())-1)];
		$textPath = Html::rawElement( "textPath", [ "xlink:href" => "#textPath", "startoffset" => "22%" ], wfMessage('surprise-me-footer')->plain() );
		$creatureTextCurved = Html::rawElement( "text", [ "class" => "creature_text" ], $textPath );
		$creature = str_replace( '[[creature_text_curved]]', $creatureTextCurved, $creature );
		$creature = str_replace( '[[creature_text_flat]]', '', $creature );

		$creatureLink = Html::rawElement( "a", [ "href" => "/Special:Randomizer" ], $creature );
		$footerRandom = Html::rawElement( "div", [ "id" => "amp_footer_random_button", "role" => "button" ], $creatureLink);
		$footerSearch = self::getSearchBar( "footer_search", wfMessage('footer-search-placeholder')->text() );

		$contentsMain = $footerSearch;
		if (class_exists('SocialFooter')) $contentsMain .= SocialFooter::getSocialFooter();
		$footerMain = Html::rawElement( "div", [ "id" => "footer_main" ], $contentsMain);

		$contents = $footerRandom . $footerMain;
		$footerHtml = Html::rawElement( "div", [ "id" => "footer", "role" => "navigation" ], $contents);
		echo $footerHtml;
	}

	public static function onArticleFromTitle(&$title, &$article) {
		if ( !$title ) {
			return true;
		}

		$checkFixedRevision = false;
		// we only fix revisions in mobile mode for amp or if optimizely is disabled
		if ( Misc::isMobileMode() ) {
			$ctx = MobileContext::singleton();
			$amp = $ctx->getRequest()->getVal( self::QUERY_STRING_PARAM ) == 1;
			if ( $amp ) {
				$checkFixedRevision = true;
			}
			if ( !OptimizelyPageSelector::isArticleEnabled( $title ) ) {
				$checkFixedRevision = true;
			}
		}


		// if optimizely is enabled, then we do not have to change the revision
		if ( !$checkFixedRevision ) {
			return true;
		}

		// see if this title is set to a fixed revision
		$revision = null;
		$urls = explode( "\n", ConfigStorage::dbGetConfig( "mobilefixedrevision" ) );
		foreach ( $urls as $url ) {
			$paramString = parse_url( $url, PHP_URL_QUERY );
			$params = array();
			parse_str( $paramString, $params );
			if ( !$params || !isset( $params['title'] ) || !isset( $params['oldid'] ) ) {
				continue;
			}
			if ( $title->getDBKey() == $params['title'] ) {
				$revision = $params['oldid'];
				break;
			}
		}

		if ( !$revision ) {
			return true;
		}

		$article = new Article( $title, $revision );

		return true;
	}
}

