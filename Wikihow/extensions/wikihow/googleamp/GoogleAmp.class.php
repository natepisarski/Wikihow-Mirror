<?php

class GoogleAmp {

	const RELATED_IMG_WIDTH = 360;
	const RELATED_IMG_HEIGHT = 231;
	const QUERY_STRING_PARAM = "amp";

	private static $isAmpMode  = null;

	public static function isAmpMode( $out ) {
		if ( self::$isAmpMode !== null  ) {
			return self::$isAmpMode;
		}
		$amp = $out->getRequest()->getVal( self::QUERY_STRING_PARAM ) == 1;

		$t = $out->getTitle();
		if (self::isAmpSpeedTest($t)) {
			$amp = true;
		}

		// Don't enable AMP mode for certain android app requests
		if (class_exists('AndroidHelper')
			&& AndroidHelper::isAndroidRequest()
			&& (!$out->getTitle()->inNamespace(NS_MAIN) || $out->getTitle()->isMainPage())) {
			$amp = false;
		}
		self::$isAmpMode = $amp;
		return $amp;
	}

	// Jordan: Setting up an amp test for a list of articles
	public static function isAmpSpeedTest($t) {
		// this is disabled for now
		return false;
		//global $wgLanguageCode, $wgUser;

		//$isSpeedTest = false;
		//if (in_array($wgLanguageCode, ["pt", "es", "en"])
			//&& $wgUser->isAnon()
			//&& Misc::isMobileMode()
			//&& $t->inNamespace(NS_MAIN)
			//&& ArticleTagList::hasTag( 'amp_speed_test', $t->getArticleID()) ) {
			//$isSpeedTest = true;
		//}

		//return $isSpeedTest;
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
		if (class_exists('ArticleQuizzes') && ArticleQuizzes::isEligibleForQuizzes()) {
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

		// Trevor, 8/16/19 - Disable AMP going to VideoBrowser for now

//		if ( Misc::isAltDomain() ) {
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
//		}

		// // Trevor - use link to VideoBrowser
		// $href = "{$wgCanonicalServer}/Video/" . str_replace( ' ', '-', $wgTitle->getText() );
		// return Html::rawElement( 'div',
		// 	[ 'class' => 'summary-video' ],
		// 	Html::rawElement( 'a',
		// 		[
		// 			'class' => 'click-to-play-overlay',
		// 			'role' => 'button',
		// 			'href' => $href,
		// 			'tabindex' => 0
		// 		],
		// 		WHVid::getSummaryIntroOverlayHtml( $sectionName, $wgTitle ) .
		// 			Html::element( 'amp-img',
		// 				[
		// 					'class' => 'video-poster-image',
		// 					'layout' => 'fill',
		// 					'src' => $video->attr( 'data-poster' ),
		// 				]
		// 			)
		// 	)
		// );
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

	public static function getConsentElement() {
		global $wgLanguageCode, $wgRequest, $wgTitle, $wgIsDevServer;

		$config = [
			'ISOCountryGroups' => [
				'eea' => ["preset-eea", "unknown"],
				"us" => ["us"]
			]
		];

		// disable ccpa for non EN for now
		if ( $wgLanguageCode != 'en' ) {
			$config = [
				'ISOCountryGroups' => [
					'eea' => ["preset-eea", "unknown"],
					"us" => []
				]
			];
		}

		// for testings
		$eu = $wgRequest->getVal( "EUtest" ) == 1;
		if ( $eu ) {
			$config = [
				'ISOCountryGroups' => [
					'eea' => ["preset-eea", "unknown", "us", "jp"],
					"us" => ["us"]
				]
			];
		}

		$ccpaTest = $wgRequest->getVal( "ccpatest" ) >= 1;
		if ( $ccpaTest ) {
			$config = [
				'ISOCountryGroups' => [
					'eea' => ["preset-eea", "unknown"],
					"us" => ["us", "jp"]
				]
			];
		}

		$jsonObject = json_encode( $config, JSON_PRETTY_PRINT );
		$scriptElement = Html::element( 'script', [ 'type' => 'application/json' ], $jsonObject );
		$groupsHtml = Html::rawElement( 'amp-geo', ['layout'=>'nodisplay'], $scriptElement );

		$messageName = "gdpr_message";

		$messageInner = wfMessage( $messageName )->text();
		if ( strpos( $messageInner, '[[' ) !== FALSE ) {
			$messageInner = wfMessage( $messageName )->parse();
		}

		$ccpaFooterMessage = wfMessage( 'footer_ccpa' )->text();

		$ccpaEndpoint = '/x/amp-consent-ccpa';
		if ( $ccpaTest ) {
			$ccpaEndpoint = '/Special:CCPA';
		}
		if ( $wgIsDevServer ) {
			// check if url has "cached" in it.. if it does not then we will use the fallback endpoint
			$host = $_SERVER['HTTP_HOST'];
			if ( $host && strpos( 'cached', $host ) === false ) {
				$ccpaEndpoint = '/Special:CCPA';
			}
		}
		if ( $wgRequest->getVal( "ccpatest" ) == 2 ) {
			$ccpaEndpoint = '/x/amp-consent-ccpa';
		}
		$vars = array(
			'ccpa_message' => $ccpaFooterMessage,
			'ccpa_endpoint' => $ccpaEndpoint,
			'gdpr_message' => $messageInner,
			'gdpr_accept' => wfMessage("gdpr_accept")->text()
		);

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader( __DIR__ )
		]);
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'amp_consent.mustache', $vars );
		return $groupsHtml . $html;
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

		$domain = wfCanonicalDomain( $wgLanguageCode, Misc::isMobileModeLite() );

		$config = [
			'requests' => [
				'pageview' => 'https://' . $domain . '/x/amp-view?url=${sourcePath}'
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
		$useMobileDomainUrl = false;
		$serverUrl =  Misc::getLangBaseURL( $languageCode, $useMobileDomainUrl );
		$ampUrl = wfExpandUrl( $serverUrl . '/' . $out->getTitle()->getPrefixedURL(), PROTO_CANONICAL );
		$ampUrl =  $ampUrl . "?amp=1";

		$out->addLink( array( 'rel' => 'amphtml', 'href' => $ampUrl ) );
	}

	public static function insertAMPAds() {
		GoogleAmpAds::insertAds();
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
			preg_match('/background-image:\s?(.*)/', pq($badge)->attr('style'), $m);
			if (!empty($m[1])) {
				//we could have multiple bg images, just use the first one
				$ampImg = explode('url(', $m[1])[1];
				$ampImg = preg_replace('/\)(,|$)/i', '', $ampImg);

				$ampImg = self::getAmpArticleImg($ampImg, 80, 80);
				pq($badge)->html($ampImg);
				pq($badge)->removeAttr('style');
			}
		}
	}

	private static function modifyVideoSection() {
		global $wgTitle;

		$videoSection = pq('.embedvideo:first')->parents('.section_text:first');
		if (!pq($videoSection)->length) {
			return;
		}

		// make sure the src is a youtube video
		$src = pq( $videoSection )->find( '.embedvideo:first' )->attr( 'data-src' );
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

		// Only consider adding schema on pages tagged with youtube_wikihow_videos
		if ( ArticleTagList::hasTag( Misc::YT_WIKIHOW_VIDEOS, $wgTitle->getArticleID() ) ) {
			$videoSchema = SchemaMarkup::getYouTubeVideo( $wgTitle, $videoId );
			// Only videos from our own channel will have publisher information
			if ( $videoSchema && array_key_exists( 'publisher', $videoSchema ) ) {
				$element .= SchemaMarkup::getSchemaTag( $videoSchema );
			}
		}

		$first = true;
		foreach ( pq( $videoSection )->children() as $child ) {
			if ( $first ) {
				pq($child)->replaceWith( $element );
				$first = false;
			} else {
				pq($child)->remove();
			}
		}
	}

	// replaces math images with amp versions
	private static function mathImage( $elem ) {
		$img = pq($elem)->find( 'img:first' );
		pq($elem)->find( 'span' )->remove();
		$style = $img->attr( 'style' );
		$split = explode( ';', $style );
		$height = null;
		$width = null;
		$src = $img->attr( 'src' );
		foreach ( $split as $rule ) {
			if ( strstr( $rule, 'width:' ) ) {
				$width = str_replace( "width:", '', $rule );
				$width = str_replace( "ex", '', $width );
			}
			if ( strstr( $rule, 'height:' ) ) {
				$height = str_replace( "height:", '', $rule );
				$height = str_replace( "ex", '', $height );
			}
		}
		$attr = [
			'src' => $src,
			'width' => $width * 8.36,
			'height' => $height * 8.36,
			'layout' => 'fixed',
			'class' => 'math-img'
		];
		$ampImg = Html::element( "amp-img", $attr );
		pq($elem )->find( 'img:first' )->after( $ampImg );
	}

	public static function modifyDom() {
		global $wgOut;
		self::formatQABadges();
		self::modifyVideoSection();
		foreach ( pq( 'script' ) as $script ) {
			if ( pq( $script )->attr( 'type' ) !== 'application/ld+json' ) {
				pq( $script )->remove();
			}
		}
		pq( 'mo' )->remove();
		pq( 'annotation' )->remove();
		pq( 'video' )->remove();
		pq( '.img-whvid' )->remove();
		pq( '.vid-whvid' )->remove();
		pq( '.image_details' )->remove();
		pq( '.aritem' )->removeAttr( 'pageid' );
		pq( '#info_link' )->removeAttr( 'aid');
		$referenceText = pq("#info_link")->text();
		pq("#references_second")->parent()->attr("id", "aii_references_second")->attr("hidden", "")->removeClass("aidata");
		pq("#info_link")->replaceWith("<span id='info_link' on='tap:aiinfo.hide, aii_references_second.show' role='button' tabindex='0'>" . $referenceText . "</span>");
		pq( '.mh-method-thumbs-template' )->remove();
		pq( 'input:not(".qz_radio, .amp_input")' )->remove();
		pq( '#tab_admin')->removeAttr('submenuname')->remove();

		pq( '.image' )->attr( 'href', '#' );

		// TODO use youtube of amp which is one of our embed iframes
		pq( 'iframe' )->remove();

		// TODO remove the rmd_container from the code base it's no longer used
		pq( '#rmb_container' )->remove();

		pq( '.edit-page' )->remove();
		pq( '#qa_submit_button' )->remove();

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
		pq( '.qa_staff_info' )->remove();
		pq( '#qa_flag_options' )->remove();
		pq( '#qa_answer_flag_options' )->remove();

		// for some reason some articles use this font-size 0 inline style..removing it for now

		// this does not work without javascript atm
		pq( '.expert_hint_box' )->remove();

		pq( '#social_proof_mobile .sp_box' )->addClass('sp_fullbox');

		//get "Updated:" date (we usually do this via js)
		//self::getUpdated();

		//star ratings
		pq( '.sp_helpful_box' )->remove();
		pq( '#sp_helpful_new' )->remove();

		// remove the "difficult" warning
		pq( '.sp_difficult_box' )->remove();

		// remove any font tags and unwrap them (unwrap does not exist in php query so use this)
		// wrap in while loop in case there is a font tag within a font tag
		while ( pq( 'font' )->length > 0 ) {
			foreach ( pq( 'font' ) as $elem ) {
				pq( $elem )->replaceWith( pq( $elem )->html() );
			}
		}

		foreach ( pq( '.mwe-math-element' ) as $elem ) {
			self::mathImage( $elem );
		}

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

		// remove weird new "Retrieved from ... url" message
		pq( '.printfooter' )->remove();

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

		// Only add this ping if it's not an amp speed test page to not double count visits
		if (!self::isAmpSpeedTest(RequestContext::getMain()->getTitle())) {
			self::addFastlyPing();
		}
		self::addNewAnchortags();

		// do this last to make sure we don't add any inline styles or js, and that the doc is as
		// small as possible when we iterate over everything
		pq( '*' )
			->removeAttr( 'style' )
			->removeAttr('onload')
			->removeAttr('onclick')
			->removeAttr('clear');
	}

	static function addNewAnchortags() {
		if ( class_exists('MobileTabs') && pq(".summarysection .summary-video")->length > 0 ) {
			//add a new anchor tag so it will jump to the right place
			$anchor = WHVid::getVideoAnchor( RequestContext::getMain()->getTitle() );
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
	static function mathHook( $parser, $renderer, &$renderedMath ) {
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
		$fullSiteLink = SkinMinervaWikihow::getMobileTOSLink( );
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

	public static function getSearchBar( $submitId, $placeholderText = "" ) {

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


		$inputAttr = [
			"type" => "hidden",
			"name" => "searchdefault",
			"value" => $placeholderText,
		];
		$inputDefault = Html::rawElement( "input", $inputAttr );

		$formContents = $input.$inputDefault.$button;

		$formAttr  = [
			"method" => "get",
			"target" => "_top",
			"action" => "/wikiHowTo",
		];
		$form = Html::rawElement( "form", $formAttr, $formContents );

		return $form;
	}

	public static function onShowArticleTabs( &$showTabs ) {
		if (self::isAmpMode( RequestContext::getMain() )) $showTabs = false;
	}
}

