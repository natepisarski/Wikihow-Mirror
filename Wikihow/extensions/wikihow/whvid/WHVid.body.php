<?php

class WHVid {

	const NUM_DIR_LEVELS = 2;
	static $titleHasSummaryVideo = null;
	static $titleHasYTVideo = null;

	const YT_VIDEOS_LIKE_SUMMARY_LIST = "youtube_wikihow_videos";

	public static function setParserFunction () {
		# Setup parser hook
		global $wgParser;
		$wgParser->setFunctionHook( 'whvid', 'WHVid::parserFunction' );
		return true;
	}

    public static function languageGetMagic( &$magicWords ) {
		$magicWords['whvid'] = array( 0, 'whvid' );
        return true;
    }

	public static function getVidFullPathForAmp( $vidSrc ) {
		$root = WH_CDN_VIDEO_ROOT;
		return $root . $vidSrc;
	}

	// checks if a gif name exists as a title and file
	public static function gifExists( $gifName ) {
		$gifTitle = Title::newFromText( $gifName, NS_IMAGE );
		$gifFile = RepoGroup::singleton()->findFile( $gifTitle );
		if ( $gifFile && $gifFile->exists() ) {
			return true;
		}
		return false;
	}

	/**
	 * we want our default images to be 16:9 but some default images
	 * in our system are 4:3 ratio..if this is the case, then chose the
	 * preview image if it is available since this will be at the correct aspect ratio
	 * @param $img String which is an image name
	 * @return true if the image exists and has a close to 4:3 aspect ratio
	 */
	public static function isLargeAspectRatio( $img ) {
		if ( !$img ) {
			return false;
		}

		$imgTitle = Title::newFromText( $img, NS_IMAGE );
		$imgFile = RepoGroup::singleton()->findFile( $imgTitle );

		if ( !$imgFile || !$imgFile->exists() ) {
			return false;
		}

		$width = $imgFile->getWidth();
		$height = $imgFile->getHeight();

		if ( !$width || $width <= 0 ) {
			return false;
		}

		$ratio = $height / $width;

		// ratio of 0.56 is 16:9 aspect ratio
		// ratio of 0.66 is 4:3 aspect ratio
		if ( $ratio > 0.62 ) {
			return true;
		}

		return false;
	}


	/**
	 * Get img and url for the default image for use in the whvid template
	 * we will try to use the static image but fallback to the previewImg
	 *
	 * @param string $staticImg : name of Image to show before video loads
	 * @param string $previewImg name of Image which is part of mp4 video.
	 *        it was the old default but now is a fallback
	 * @return array returns the name of the default image and url to large and small thumbs of it
	 */
	private static function getDefaultImg( $staticImg, $previewImg ) {
		global $wgLanguageCode;

		$previewFallback = false;

		$img = null;
		$imgFile = null;
		$defaultImg = null;

		$largeRatio = self::isLargeAspectRatio( $staticImg );
		if ( !$staticImg || ( $largeRatio && $previewImg ) ) {
			$previewFallback = true;
		}

		if ( $previewFallback == false ) {
			$img = Title::newFromText( $staticImg, NS_IMAGE);
			$imgFile = RepoGroup::singleton()->findFile( $img );
			if ( $imgFile && $imgFile->getMediaType() != "ARCHIVE" && ( $wgLanguageCode != "en" || $img->exists() ) ) {
				$defaultImg = $staticImg;
			} else {
				$previewFallback = true;
			}
		}

		if ( $previewFallback == true ) {
			$img = Title::newFromText( $previewImg, NS_IMAGE);
			$imgFile = RepoGroup::singleton()->findFile( $img );
			if ( $imgFile && $imgFile->getMediaType() != "ARCHIVE" && ( $wgLanguageCode != "en" || $img->exists() ) ) {
				$defaultImg = $previewImg;
			}
		}

		$smallImgUrl = '';
		$largeImgUrl = '';
		if ( !$imgFile ) {
			return array( $defaultImg, $smallImgUrl, $largeImgUrl );
		}

		$params = array(
			'width' => 550,
			'height' => 309,
			WatermarkSupport::NO_WATERMARK => true,
		);
		$flags = 0;
		$thumb = $imgFile->transform( $params, $flags );
		$largeImgUrl = $thumb->getUrl();

		$thumb = $imgFile->getThumbnail( 300 );
		$smallImgUrl = $thumb->getUrl();

		return array( $defaultImg, $smallImgUrl, $largeImgUrl );

	}

	private static function getSummaryImageThumbUrl( $image, $width ) {
		$params = array(
			'width' => $width,
			WatermarkSupport::NO_WATERMARK => true,
		);
		$flags = 0;
		$thumb = $image->transform( $params, $flags );
		return $thumb->getUrl();
	}

	/**
	 *
	 * @param string $image the string name of the MW image file
	 */
	public static function getSummaryImage( $image ) {
		$result = null;

		$img = Title::newFromText( $image, NS_IMAGE);
		if ( !$img )  {
			return $result;
		}
		$imgFile = RepoGroup::singleton()->findFile( $img );
		if ( !$imgFile )  {
			return $result;
		}
		return $imgFile;
	}
	/**
	 * Parser function for whvid template
	 *
	 * @param Parser $parser the parser object we will be using
	 * @param string $vid the mp4 video name
	 * @param string $previewImg name of Image which is part of mp4 video.
	 *        it was the old default but now is a fallback
	 * @param string $staticImg (Optional): name of Image to show before video loads
	 * @param string $gif (Optional): name of gif image for use on mobile
	 * @param string $gifFirst (Optional): name of first frame of gif image to show before gif loads
	 * @return string Parser tag which will be replaced in the output plus any wikitext for fallbacks
	 *         see Parser.php insertStripItem for details
	 */
	public static function parserFunction( $parser, $vid = null, $previewImg = null, $staticImg = null, $gif = null, $gifFirst = null ) {
		global $wgTitle;
		if ( $vid === null || $previewImg === null ) {
			return '<div class="errorbox">'.wfMessage('missing-params').'</div>';
		}

		$isSummaryVideo = false;
		$watermark = true;
		$summaryVideo = false;
		$summaryIntroImage = null;
		$summaryOutroImage = null;
		if ( strstr( $vid, ' Step 0.' ) || strstr( $vid, ' Step 0 ' ) )  {
			$isSummaryVideo = true;
			$watermark = false;
			$summaryIntroImage = self::getSummaryImage( $previewImg );
			$summaryOutroImage = self::getSummaryImage( $staticImg );
		}

		// we use the static image as the default image if it is available
		list ( $defaultImg, $smallImgUrl, $largeImgUrl ) = self::getDefaultImg( $staticImg, $previewImg );

		if ( !$defaultImg ) {
			return "";
		}

		if ( $parser->getTitle() && WatermarkSupport::isRestricted( $parser->getTitle()->getArticleID() ) ) {
			$watermark = false;
		}

		$divId = "whvid-" . md5( $vid . mt_rand( 1,1000 ) );
		$vidUrl = self::getVidUrl( $vid );

		list( $gifUrl, $gifSize ) = self::getGifInfo( $gif );
		list( $gifFirstUrl ) = self::getGifInfo( $gifFirst );
		$html = self::getVideoHtml( $vidUrl, $largeImgUrl, $gifUrl, $gifFirstUrl, $watermark, $isSummaryVideo, $summaryIntroImage, $summaryOutroImage );
		$parserItem = $parser->insertStripItem( $html );
		$wt = "[[Image:$defaultImg]]";

		return $parserItem.$wt;
    }

	/*
	 * create the html5 video element, with poster image and data attributes
	 * used by mobile and desktop
	 */
	private static function getVideoHtml( $vidUrl, $defaultImg, $gifSrc, $gifFirstSrc, $watermark, $isSummaryVideo, $summaryIntroImage, $summaryOutroImage ) {
		global $wgTitle;
		$id = 'mvid-' . wfRandomString(10);
		$attr = array(
			'playsinline' => '',
			'webkit-playsinline' => '',
			'class' => 'm-video',
			'id' => $id,
			'data-poster' => $defaultImg,
			'data-src' => $vidUrl,
			'data-gifsrc' => $gifSrc,
			'data-giffirstsrc' => $gifFirstSrc,
			'controlsList' => 'nodownload',
			'data-watermark' => $watermark,
			'data-controls' => true,
		);

		if ( $isSummaryVideo ) {
			$attr['data-summary'] = true;
			$attr['preload'] = 'none';
			if ( !$wgTitle || !ArticleTagList::hasTag( 'summary-video-no-overlay', $wgTitle->getArticleID() ) ) {
				$attr['class'] = 'm-video m-video-summary';
			}
			$attr['oncontextmenu'] = "return false;";
		} else {
			$attr['muted'] = '';
			$attr['loop'] = '';
		}

		if ( $wgTitle && ArticleTagList::hasTag( 'inline-video-no-poster', $wgTitle->getArticleID() ) ) {
			$attr['data-no-poster-images'] = true;
			$attr['data-poster'] = '';
		}

		if ( $summaryIntroImage ) {
			$attr['data-poster'] = self::getSummaryImageThumbUrl( $summaryIntroImage, 1000 );
			$attr['data-poster-mobile'] = self::getSummaryImageThumbUrl( $summaryIntroImage, 460 );
		}

		if ( $summaryOutroImage ) {
			$attr['data-summary-outro'] = self::getSummaryImageThumbUrl( $summaryOutroImage, 728 );
			$attr['data-summary-outro-mobile'] = self::getSummaryImageThumbUrl( $summaryOutroImage, 460 );
		}

		$element = Html::element( 'video', $attr );

		// the script which adds the video to js for loading
		$script = "<script>if (WH.video)WH.video.add(document.getElementById('$id'));</script>";
//		$script = "";
		return $element.$script;
	}

	public static function getVidDirPath($filename) {
		return FileRepo::getHashPathForLevel($filename, self::NUM_DIR_LEVELS);
	}

	public static function getVidFilePath($filename) {
		return self::getVidDirPath($filename) . $filename;
	}

	public static function getVidUrl($filename) {
		return '/' . self::getVidFilePath($filename);
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::hasSummaryVideo($out->getTitle())) $out->addModules(array('ext.wikihow.wikivid'));
	}

	/**
	 * Get a gif url and file size from the gif file name
	 * @param $gif String which is the gif name
	 * @return array the gif url and file size if the gif exists
	 */
	private static function getGifInfo( $gif ) {
		$result = array( null, null );
		if ( !$gif ) {
			// this optional line will try to look for a gif based on the mp4 name
			// if one is not provided to the function arguments it was originally
			// how the gifs were designed to work and might be replaced again in the future..
			// it's just for reference now
			//$gif = str_replace( "mp4", "gif", $vid );

			return $result;
		}

		$gifTitle = Title::newFromText( $gif, NS_IMAGE );

		$gifFile = RepoGroup::singleton()->findFile( $gifTitle );

		if ( !$gifFile || !$gifFile->exists() ) {
			return $result;
		}

		// get the url of the gif to put into html
		$gifUrl = $gifFile->getUrl();

		// get size in MB of the gif so it's available to js on the page
		$gifSize = round( $gifFile->getSize() / 1024 / 1024, 3 );

		return array( $gifUrl, $gifSize );
	}

	/*
	 * used by desktop to make a fake watermark on video
	 */
	public static function getVideoWatermarkHtml( $title ) {
		$innerHtml = Html::element( 'img', ['class' => 'm-video-wm-img', 'src' => '/skins/WikiHow/images/WH_logo.svg'] );
		$text =  "to " . $title->getText();
		if ( Misc::isIntl() ) {
			$text = '';
		}
		$titleText = Html::element( 'span', ['class' => 'wm-title'], $text );
		$attr = ['class' => 'm-video-wm'];
		$attr['data-wm-title-text'] = $text;
		$wrap = Html::rawElement( 'div', $attr, $innerHtml );
		return $wrap;
	}

	public static function getVideoControlsSummaryHtml( $introText ) {
		global $wgTitle;
		$html = self::getSummaryIntroOverlayHtml( $introText, $wgTitle );
		$wrap = Html::rawElement( 'div', ['class' => 'm-video-controls', 'oncontextmenu' => 'return false;' ], $html );
		return $wrap;
	}

	public static function getSummaryIntroOverlayHtml( $sectionName, $title ) {
		$watch = wfMessage( 'summary_video_watch' )->text();
		$watch = Html::element( 'span', ['class' => 'm-video-play-text'], $watch );
		$playButtonInner = Html::element('div', ['class' => 'm-video-play-count-triangle']) . " ". $watch;

		$playButtonAttributes = array(
			'class' => 'm-video-play',
		);

		$playButton = Html::rawElement( "div", $playButtonAttributes, $playButtonInner );

		$mVideoIntroOver = Html::rawElement( 'div', ['class' => 'm-video-intro-over'], $playButton );
		if ( $title && ArticleTagList::hasTag( 'summary-video-no-overlay', $title->getArticleID() ) ) {
			$mVideoIntroOver = $playButton;
		}
		return $mVideoIntroOver;
	}

	public static function getVideoControlsHtml() {
		$playButton = Html::rawElement( 'div', ['class' => 'm-video-play-old'] );
		$controls = Html::rawElement( 'div', ['class' => 'm-video-controls'], $playButton );
		return $controls;
	}

	public static function getVideoControlsHtmlPlayButtonTest() {
		$playContents= Html::element( 'div', ['class' => 'm-video-play-count-triangle' ] );
		$playButton = Html::rawElement( 'div', ['class' => 'm-video-play'], $playContents );
		$contents = $playButton;
		$wrap = Html::rawElement( 'div', ['class' => 'm-video-controls'], $contents );
		return $wrap;
	}

	public static function getVideoControlsHtmlMobile() {
		$playContents= Html::element( 'div', ['class' => 'm-video-play-count-triangle' ] );
		$playButton = Html::rawElement( 'div', ['class' => 'm-video-play'], $playContents . $playCount );
		$contents = $playButton;
		$wrap = Html::rawElement( 'div', ['class' => 'm-video-controls'], $contents );
		return $wrap;
	}

	public static function getVideoReplayHtml() {
		$replayText = Html::element( 'div', ['class'=> 's-video-replay-text'], wfMessage( 'summary_video_finish_replay' )->text() );
		$icon = Html::element( "div", ['class' => 's-video-replay-inner'] );
		$icon .= Html::element( "div", ['class' => 's-video-replay-inner-t-right'] );
		$icon .= Html::element( "div", ['class' => 's-video-replay-inner-t-down'] );
		$replayClass = ['s-video-replay'];

		$html = Html::rawElement( 'div', ['class' => $replayClass], $icon . $replayText );
		$html .= Html::rawElement( 'div', ['class' => 's-video-replay-overlay'] );
		return $html;
	}

	public static function getDesktopVideoHelpfulness() {
		$type = 'summaryvideo';
		$buttonClass = 'button secondary yes s-help-response';
		$wrapperClass = 'm-video-helpful-wrap';

		$text = wfMessage( 'rateitem_summary_video_text' )->text();
		$finishPromptYes = wfMessage( 'rateitem_summary_video_finish_prompt_yes' )->text();
		$finishPromptNo = wfMessage( 'rateitem_summary_video_finish_prompt_no' )->text();
		$textFeedback = true;
		$html = RateItem::getSectionRatingHtml( $type, $textFeedback, $buttonClass, $text, $wrapperClass, $finishPromptYes, $finishPromptNo );
		return $html;
	}

	public static function onAddTopEmbedJavascript( &$paths ) {
		global $wgTitle;

		if ( !$wgTitle ) {
			return;
		}
		if ( !( $wgTitle->inNamespace(NS_SPECIAL) || $wgTitle->inNamespace(NS_MAIN) ) ) {
			return;
		}
		if ( pq('.m-video')->length <= 0 ) {
			return;
		}

		static $isIncluded = false;
		if (!$isIncluded) {
			$paths[] = __DIR__ . '/whvid.compiled.js';
			$isIncluded = true;
		}
	}

	public static function addCSS( &$css, $title ) {
		global $IP;

		$path = $IP .  "/extensions/wikihow/whvid/whvid.css";
		// todo conditionally add css based on test we are running for given page
		// for version of the sumary section
		if ( is_array( $css ) ) {
			$css[] = $path;
			$css[] = $IP .  "/extensions/wikihow/whvid/whvid_mobile.css";
		} else {
			$cssStr = Misc::getEmbedFiles( 'css', [$path] );
			$cssStr = wfRewriteCSS( $cssStr, true );
			$css .= HTML::inlineStyle( $cssStr );
		}

		return true;
	}
	public static function addMobileCSS(&$stylePath, $title) {
		global $IP;

		if (self::$showFirstAtTop) {
			$stylePath[] = $IP . "/extensions/wikihow/quiz/quiz.css";
		}

		return true;
	}

	public static function onAddMobileTOCItemData($title, &$extraTOCPreData, &$extraTOCPostData) {
		if (self::hasSummaryVideo($title) && !(WHVid::isYtSummaryArticle($title) && WHVid::hasYTVideo($title))) {
			$extraTOCPostData[] = [
				'anchor' => 'quick_summary_video_section',
				'name' => 'Video',
				'priority' => 1050,
				'selector' => '#quick_summary_video_section',
			];
		}
		return true;
	}

	public static function hasSummaryVideo($title) {
		if(!$title || !$title->exists()) return false;

		if(is_null(self::$titleHasSummaryVideo)) {
			$wikiText = wikiText::getWikitext(wfGetDB(DB_SLAVE), $title);
			self::$titleHasSummaryVideo = wikiText::countSummaryVideos($wikiText) > 0;
		}

		return self::$titleHasSummaryVideo;
	}

	public static function hasYTVideo($title) {
		if(!$title || !$title->exists()) return false;

		if(is_null(self::$titleHasYTVideo)) {
			$wikiText = wikiText::getWikitext(wfGetDB(DB_SLAVE), $title);
			self::$titleHasYTVideo = strlen(wikiText::getVideoSection($wikiText)[0]) > 0;
		}

		return self::$titleHasYTVideo;
	}

	//this uses the phpQuery object
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		$title = $out->getTitle();
		$user = $out->getUser();
		$isMobile = Misc::isMobileMode();
		$hasYTVideo = self::hasYTVideo($title);
		$isYTSummaryArticle = self::isYtSummaryArticle($title);

		$remove_video_section = $title && $title->exists() &&
														$title->inNamespace(NS_MAIN) &&
														$user && $user->isAnon() &&
														self::hasSummaryVideo($title) &&
														!$isYTSummaryArticle &&
														pq('.section.video')->length;

		if ($remove_video_section) pq('.section.video')->remove();

		//only do this on mobile. This happens for desktopp in WikihowArticle.class.php
		if($hasYTVideo && $isYTSummaryArticle && $isMobile) {
			pq( '.quicksummary')->remove();
		}
	}

	public static function isYtSummaryArticle($title) {
		return ArticleTagList::hasTag(WHVid::YT_VIDEOS_LIKE_SUMMARY_LIST, $title->getArticleID());
	}
}
