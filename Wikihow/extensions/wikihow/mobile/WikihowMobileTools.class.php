<?php

if (!defined('MEDIAWIKI')) die();

class WikihowMobileTools {
	const SMALL_IMG_WIDTH = 460;
	const IMG_LICENSE_MEMCACHED_KEY = 'img_licenses';

	private static $referencesSection = null;

	static function onMobilePreRender($mobileTemplate) {
		global $wgOut;

		// only do this for article pages
		if ($wgOut->isArticle()) {
			$mobileTemplate->data['bodytext'] = self::processDom( $mobileTemplate->data['bodytext'], $mobileTemplate->getSkin(), null, $mobileTemplate );
		}

		if ( !GoogleAmp::isAmpMode($wgOut) ) {
			StuLogger::endMobilePreRender($mobileTemplate, $wgOut->getContext());
		}

		return true;
	}

	static function processDom($text, $skin, $config = null, $mobileTemplate = null ) {
		global $wgLanguageCode, $wgTitle, $wgMFDeviceWidthMobileSmall, $IP, $wgUser, $wgContLang;

		// Trevor, 5/22 - Used later on to add structred data to inline summary videos, must be
		// called here due to mysterious issue with calling it later to be solved in the future
		$videoSchema = SchemaMarkup::getVideo( $wgTitle );

		if (is_null($config)) {
			$config = self::getDefaultArticleConfig();
		}
		$doc = phpQuery::newDocument($text);
		$docTitle = $skin->getRelevantTitle();

		$amp =  GoogleAmp::isAmpMode( $skin->getOutput() );
		if ( $amp ) {
			$config['display-deferred-javascript'] = false;
		}

		$context = MobileContext::singleton();

		$featurestar = pq("div#featurestar");
		if ($featurestar) {
			$clearelement = pq($featurestar)->next();
			$clearelement->remove();
			$featurestar->remove();
		}

		$showHighDPI = self::isHighDPI($docTitle);

		//move firstHeading to inside the intro
		$firstH2 = pq("h2:first");
		if (pq($firstH2)->length() == 0) {
			try {
				// on mobile the bodycontents does not exist so consider removing this line of code
				// or replaceing it with div:first as is done on NS_PROJECT namespace pages below
				pq("#bodycontents")->children(":first")->wrapAll("<div class='section wh_block'></div>");
			} catch (Exception $e) {
			}
		}
		else {
			try {
				pq($firstH2)->prevAll()->reverse()->wrapAll("<div id='intro' class='section'></div>");
			} catch (Exception $e) {
			}
		}

		if ($wgTitle && $wgTitle->inNamespace(NS_PROJECT) ) {
			if (pq($firstH2)->length() == 0) {
				try {
					pq("div:first")->children(":first")->wrapAll("<div id='intro' class='section'></div>");
				} catch (Exception $e) {
				}
			}
		}

		//add a clearall to the end of the intro
		pq("#intro")->append("<div class='clearall'></div>");

		// Contains elements with the raw titles of methods (i.e. non-parts)
		$nonAltMethodElements = array();
		$showTOC = false;

		foreach (pq("h2") as $node) {
			//find each section

			$sectionName = Misc::getSectionName(pq("span.mw-headline", $node)->html());

			if ( strpos( $sectionName, 'ataglance' ) !== FALSE ) {
				$sectionName = 'ataglance';
			}

			//now find all the elements prior to the next h2
			$set = array();
			$h3Tags = array();
			$h3Elements = array();
			$priorToH3Set = array();
			$h3Count = 0;

			$nodeDiv = pq($node)->next();

			foreach (pq($nodeDiv)->children() as $sibling) {
				if (pq($sibling)->is("h2")) {
					break;
				}
				if (pq($sibling)->is("h3")) {
					$h3Count++;
					$h3Tags[$h3Count] = $sibling;
					$h3Elements[$h3Count] = array();
				}
				else {
					if ($h3Count > 0)
						$h3Elements[$h3Count][] = $sibling;
					else {
						$priorToH3Set[] = $sibling;
					}
				}
				$set[] = $sibling;
			}

			$canonicalSectionName = WikihowArticleHTML::canonicalizeHTMLSectionName($sectionName);
			$canonicalSteps = WikihowArticleHTML::canonicalizeHTMLSectionName(wfMessage('steps')->text());
			if ($canonicalSectionName == $canonicalSteps) {

				pq('h3')->prepend('<div class="altblock"></div>');
				if ($h3Count > 0) {
					//has alternate methods
					$altMethodNames = array();
					$altMethodAnchors = array();

					if (count($priorToH3Set) > 0) {
						//needs to have a steps section prior to the
						//alt method
						try {
							pq($priorToH3Set)->wrapAll("<div id='{$sectionName}' class='section_text'></div>");
						} catch (Exception $e) {
						}

						$overallSet = array();
						$overallSet[] = $node;
						foreach (pq("div#{$sectionName}:first") as $temp) {
							$overallSet[] = $temp;
						}

						try {
							pq($overallSet)->wrapAll("<div class='section steps'></div>");
						} catch (Exception $e) {
						}
					} else {
						//hide the h2 tag
						pq($node)->addClass("hidden");
					}

					// AG left this in
					$displayMethodCount = $h3Count;
					$isSample = array();
					for ($i = 1; $i <= $h3Count; $i++) {
						$isSampleItem = false;
						if (!is_array($h3Elements[$i]) || count($h3Elements[$i]) < 1) {
							$isSampleItem = false;
						} else {
							//the sd_container isn't always the first element, need to look through all
							foreach ($h3Elements[$i] as $node) { //not the most efficient way to do this, but couldn't get the find function to work.
								if (pq($node)->attr("id") == "sd_container") {
									$isSampleItem = true;
									break;
								}
							}
						}
						if ($isSampleItem) {
							$isSample[$i] = true;
							$displayMethodCount--;
						} else {
							$isSample[$i] = false;
						}
					}
					$stepsEditUrl = pq('.editsection', $node)->attr("href");

					//let's play "Find the Sample"
					for ($i = 1; $i <= $h3Count; $i++) {
						if (!is_array($h3Elements[$i]) || count($h3Elements[$i]) < 1) continue;

						$isSampleItem = false;

						//the sd_container isn't always the first element, need to look through all
						foreach ($h3Elements[$i] as $node) { //not the most efficient way to do this,
							if (pq($node)->attr("id") == "sd_container") {
								$isSampleItem = true;
								break;
							}
						}

						$isSample[$i] = $isSampleItem;
					}

					/*if ($ads) {
						wikihowAds::setAltMethods($displayMethodCount > 1);
					}*/

					$wikitext = ContentHandler::getContentText($context->getWikiPage()->getContent(Revision::RAW));
					$hasParts = MagicWord::get('parts')->match($wikitext);

					$displayMethod = 1;
					for ($i = 1; $i <= $h3Count; $i++) {

						$methodTitle = pq("span.mw-headline", $h3Tags[$i])->html();
						$altMethodNames[] = $methodTitle;
						$altMethodAnchors[] = pq("span.mw-headline", $h3Tags[$i])->attr("id");

						if (!$isSample[$i] && $docTitle->inNamespace(NS_MAIN)) {
							$method_of = wfMessage('of')->text();
							$methodPrefix = $hasParts ? wfMessage('part')->text() : wfMessage('method')->text();
							$methodPrefix .= " <span>{$displayMethod}</span>" .
								"<span class='method_of_count'> $method_of $displayMethodCount:</span>";
							$displayMethod++;
						}

						if (!$isSample[$i] && $wgTitle->inNamespace(NS_MAIN)) {
							pq(".altblock", $h3Tags[$i])->html($methodPrefix);
						} else {
							pq(".altblock", $h3Tags[$i])->remove();
						}

						pq("span.mw-headline", $h3Tags[$i])->html($methodTitle);

						$sample = $isSample[$i] ? "sample" : "";

						//only wrap if there's stuff there to wrap.
						//This happens when people put two sub methods on top of each other without
						//any content between.
						if (count($h3Elements[$i]) > 0) {
							pq($h3Elements[$i])->wrapAll("<div id='{$sectionName}_{$i}' class='section_text'></div>");
						}
						$overallSet = array();
						$overallSet[] = $h3Tags[$i];
						foreach (pq("div#{$sectionName}_{$i}:first") as $temp) {
							$overallSet[] = $temp;
						}
						try {
							pq($overallSet)->wrapAll("<div class='section steps {$sample}'></div>");
							pq('.section.steps:first')->addClass('steps_first');
						} catch (Exception $e) {
						}
					}
				}

				$showTOC = !$amp
					&& !self::isInternetOrgRequest()
					&& !AndroidHelper::isAndroidRequest()
					&& !$wgContLang->isRTL();
				if ($h3Count <= 0) {
					if ($set) {
						try {
							pq($set)->wrapAll("<div id='{$sectionName}' class='section_text'></div>");
						} catch (Exception $e) {
						}
					}

					$overallSet = array();
					$overallSet[] = $node;
					foreach (pq("div#{$sectionName}:first") as $temp) {
						$overallSet[] = $temp;
					}

					try {
						pq($overallSet)->wrapAll("<div class='section steps'></div>");
					} catch (Exception $e) {
					}
				}
				if (class_exists('ArticleQuizzes')) {
					$articleQuizzes = new ArticleQuizzes($wgTitle->getArticleID());
					$count = 1;
					foreach (pq(".steps .mw-headline") as $headline) {
						$methodType = ($hasParts?"Part ":"Method ") . $count;
						$methodTitle = pq($headline)->html();
						$quiz = $articleQuizzes->getQuiz($methodTitle, $methodType);
						if ($count == 1 && $articleQuizzes->showFirstAtTop()) {
							pq($headline)->parents('.steps')->prepend($quiz);
						} else {
							pq($headline)->parents('.steps')->append($quiz);
							if ($articleQuizzes->showFirstAtTop()) { //this is temporary while we test
								pq($headline)->parents('.steps')->find(".qz_top_info")->remove();
							}
						}
						$count++;
					}
					pq(".qz_container:last")->addClass("qz_last"); //need this for amp purposes
				}

			} else {
				//not a steps section

				//is it a list page?
				$list_page = $docTitle->inNamespace(NS_PROJECT) &&
										($docTitle->getDbKey() == 'RSS-feed' || $docTitle->getDbKey() == 'Rising-star-feed');

				//bad articles are formatted in a different way; recalculate
				if (!$docTitle->exists()) {
					$set = [];
					foreach (pq($node)->nextAll() as $sibling) {
						$set[] = $sibling;
					}
				}

				//custom user page content
				if ($docTitle->inNamespace(NS_USER)) {
					$set = [];
					foreach (pq($node)->nextAll() as $sibling) {
						if (pq($sibling)->is('h2')) break;
						$set[] = $sibling;
					}
				}

				if ($set) {
					$sec_id = ($list_page) ? '' : 'id="'.$sectionName.'"';
					try {
						$new_set = pq($set)->wrapAll("<div {$sec_id} class='section_text'></div>");
					} catch (Exception $e) {
					}
				}

				$overallSet = array();
				$overallSet[] = $node;
				foreach ( pq("div#{$sectionName}:first") as $temp){
					$overallSet[] = $temp;
				}
				try {
					pq($overallSet)->wrapAll("<div class='section {$sectionName}'></div>");
				} catch (Exception $e) {
				}

				if ($list_page) {
					//gotta pull those dangling divs into the same space as the h2
					try {
						pq($overallSet)->parent()->append(pq($new_set));
					} catch(Exception $e) {
					}
				}

			}
		}

		//Move the edit links to the end b/c new skin uses table display
		//UPGRADE TODO - decide if this is an ok solution
		foreach(pq(".section-heading .mw-editsection") as $editsection) {
			pq($editsection)->parent()->append($editsection);
		}
		foreach(pq("h3 > .mw-editsection") as $editsection) {
			pq($editsection)->parent()->append($editsection);
		}

		//add extra green blocks to certain headers
		pq('.tips h2, .warnings h2')->prepend('<div class="altblock"></div>');

		Hooks::run('AtAGlanceTest', array( $wgTitle ) );

		//now put the step numbers in
		$methodNum = 1;
		foreach (pq("div.steps .section_text > ol") as $list) {
			pq($list)->addClass("steps_list_2");
			$stepNum = 1;
			foreach (pq($list)->children() as $step) {
				$boldStep = WikihowArticleHTML::boldFirstSentence(pq($step)->html());
				pq($step)->html($boldStep);
				pq($step)->prepend("<a name='" . wfMessage('step_anchor', $methodNum, $stepNum) . "' class='stepanchor'></a>");
				pq($step)->prepend("<div class='step_num'>{$stepNum}</div>");
				pq($step)->append("<div class='clearall'></div>");
				$stepNum++;
			}

			//handles the case where there's some text prior to the
			//numbered steps
			$prev = pq($list)->prev();
			if (pq($prev)->length) {
				pq($prev)->addClass("steps_text");
			}
			$methodNum++;
		}

		foreach (pq(".steps:last .steps_list_2")->children(":last-child") as $step) {
			pq($step)->addClass("final_li");
		}

		// clean up the dom for use of video
		foreach ( pq( '.m-video' ) as $node ) {
			$mVideo = pq( $node );

			$mobilePoster = $mVideo->attr( 'data-poster-mobile' );
			if ( $mobilePoster ) {
				$mVideo->attr( 'data-poster', $mobilePoster );
			}

			$imgAttributes = array( 'poster', 'data-poster', 'data-poster-mobile', 'data-gifsrc', 'data-giffirstsrc' );
			foreach ( $imgAttributes as $attrName ) {
				$attrVal = $mVideo->attr( $attrName );
				if ( !$attrVal ) {
					continue;
				}
				$attrVal = wfGetPad( $attrVal );
				if ( $attrVal ) {
					$mVideo->attr( $attrName, $attrVal );
				}
			}

			// if the video is in a p tag unwrap it so it can be moved properly
			if ( $mVideo->parent()->is('p') ) {
				$mVideo->parent()->contentsUnwrap();
			}
			// we want no inline style on the mwimg containing a video
			$whvid = $mVideo->nextAll( ".mwimg" )->removeAttr( "style" );
			$whvid->removeClass( 'floatright' );
			// we want to move the javascript that adds the video to after the video
			$mVideo->next('script')->insertAfter( $whvid );
			// move the video into the mwimg, just after the <a class='image'> which we can remove later
			$whvid->find( ".image" )->before( $mVideo );

			$mVideo->wrap( '<div class="video-container">' );
			$videoContainer = $mVideo->parent();
			$videoContainer->wrap( '<div class="video-player">' );
			$videoPlayer = $videoContainer->parent();
			$videoPlayer->wrap('<div class="content-spacer" style="padding-top: 56.25%;">');
			$videoContainer->addClass('content-fill');
			$mVideo->addClass('content-fill');

			if ( $mVideo->attr( 'data-watermark' ) ) {
				$videoContainer->after( WHVid::getVideoWatermarkHtml( $context->getTitle() ) );
			}
		}

		$headingsList = ConfigStorage::dbGetConfig(Wikitext::SUMMARIZED_HEADINGS_KEY);
		$headings = explode("\n", $headingsList);

		$summarySections = [];
		$summaryIntroHeadingText = '';
		if ($headingsList != "") { //we only want to do this if we've defined the summary sections for this language
			foreach ($headings as $heading) {
				$headingText = WikihowArticleHTML::canonicalizeHTMLSectionName(Misc::getSectionName($heading));
				$headingId = '#' . $headingText;
				$summarySections[] = "." . $headingText;

				// move the summary video to the top of the section
				if ( pq($headingId)->length ) {
					$summaryIntroHeadingText = $heading;
				}

				//add a class to these sections so we can normalize the css
				pq($headingId)->addClass("summarysection");
			}
		}

		// add the controls
		pq( '.summarysection video' )->addClass( 'summary-m-video' )->parent()->after( WHVid::getVideoControlsSummaryHtml( $summaryIntroHeadingText ) );

		//give the whole section a consistent id
		pq( '.summarysection .video-player' )->parents( '.summarysection' )->eq( 0 )->attr( 'id','quick_summary_section');

		//give the summary video title a consistent id (like the other sections)
		pq( '.summarysection .video-player')->parents( '.section' )->find('h2 span')->attr( 'id', 'quick_summary_video_section');

		pq( "#quick_summary_section")->parents('.section')->addClass("summary_with_video");

		pq( 'video:not(.summary-m-video)' )->parent()->after( WHVid::getVideoControlsHtmlMobile() );

		//move each of the large images to the top
		foreach (pq(".steps_list_2 li .mwimg.largeimage") as $image) {
			//delete any previous <br>
			foreach (pq($image)->prevAll() as $node) {
				if ( pq($node)->is("br") )
					pq($node)->remove();
				else
					break;
			}

			//first handle the special case where the image
			//ends up inside the bold tag by accident
			if (pq($image)->parent()->is("b")) {
				pq($image)->insertBefore(pq($image)->parent());
			}

			if (pq($image)->parent()->parent()->parent()->is(".steps_list_2")) {
				pq($image)->parent()->parent()->prepend($image);
			}
		}
		// on project pages if the first bullet in a list is an image add a special class
		if ( $wgTitle->inNamespace(NS_PROJECT) ) {
			foreach ( pq( '.section_text ul' ) as $node ) {
				foreach ( pq( $node )->find( 'li:first' ) as $firstItem ) {
					if ( pq( $firstItem )->find( '.mwimg' )->length ) {
						pq( $firstItem )->addClass( 'hasimg' );
					}
				}
			}
		}

		//now that we've moved images up, we need to move all the step anchors
		//back to the top so the offsets are easier to calculate
		foreach (pq(".steps_list_2 .stepanchor") as $anchor) {
			pq($anchor)->parent()->prepend($anchor);
		}

		$imageCreators = array();
		$pageId = $wgTitle->getArticleID();

		$imageLicensesOriginal = self::getImageLicenses( $pageId );

		//deal with swapping out all images for tablet
		//and putting in the right size image
		foreach (pq(".mwimg a") as $a) {
			$img = pq($a)->find('img');
			$originalSrc = $img->attr('src');
			//get original info
			$srcWidth = pq($img)->attr("width");
			$srcHeight = pq($img)->attr("height");

			if ($srcWidth == 0 ) {
				continue;
			}

			// Decode image if the translation of image has encoded characters.
			// Fix for alignment issues for images with apostrophes in the name: decode all image names
			$title = Title::newFromText(urldecode(substr(pq($a)->attr('href'),1)), NS_IMAGE);

			$imageObj = RepoGroup::singleton()->findFile($title);
			if ($imageObj && $imageObj->exists()) {
				$imageCreators[$imageObj->getUser( 'text' )] = $title;
				//get the mobile sized image
				$smallWidth = 460; //we've chosed this as our max image size on mobile devices
				$smallHeight = round($smallWidth*$srcHeight/$srcWidth, 0);
				$smallQuality = self::getImageQuality($wgTitle);
				list($thumb_small, $newWidth, $newHeight) =
					self::makeThumbDPI($imageObj, $smallWidth, $smallHeight, false, $pageId, $smallQuality);
				$smallSrc = wfGetPad( $thumb_small->getUrl() );
				pq($img)->attr( 'src', $smallSrc );

				//we actually need to remove these fields, not just empty them b/c IE on mobile sets them to 1 if they're empty
				pq($img)->removeAttr('width', '');
				pq($img)->removeAttr('height', '');

				//make a srcset value
				$bigWidth = 760; //optimized for iPad in portrait mode
				$bigHeight = $bigWidth*$srcHeight/$srcWidth;
				$bigQuality = self::getImageQuality($wgTitle);
				list($thumb_big, $newWidth, $newHeight) =
					self::makeThumbDPI($imageObj, $bigWidth, $bigHeight, false, $pageId, $bigQuality);
				$url = wfGetPad($thumb_big->getUrl());

				if ( $amp ) {
					pq('.m-video-controls')->remove();
					// get the video url and remove the existing <video> element
					$video = pq( $a )->parents( '.mwimg:first' )->find( '.m-video:first');
					if ( $video->length > 0 ) {
						$ampVideo = GoogleAmp::getAmpVideo( $video, $summaryIntroHeadingText );
						pq( $a )->replaceWith( $ampVideo );
					} else {
						$srcSet = $smallSrc. " " . $smallWidth . "w, ";
						$srcSet .= $url. " " . $bigWidth . "w";
						$layout = "responsive";
						if ( pq($img)->parents( '.techicon' )->length > 0 ) {
							//for the techicon template
							$smallHeight = 30;
							$smallWidth = round($smallHeight*$srcWidth/$srcHeight, 0);
							$srcSet = null;
							$layout = "fixed";
						}
						$ampImg = GoogleAmp::getAmpArticleImg( $smallSrc, $smallWidth, $smallHeight, $srcSet, $layout );
						pq( $a )->replaceWith( $ampImg );
					}
					continue;
				}

				$thumb_ss = $url;
				pq($img)->attr("data-srclarge", $originalSrc);
				// need to add microtime to handle the editor overlays so there aren't 2 images with the same id on the page
				$thumb_id = md5(pq($img)->attr("src") . microtime());
				pq($img)->attr("id", $thumb_id);

				// We include the local URL here as part of the hash ref for 2 reasons:
				// (1) Using a bare "#" makes it so that clicking on an image before the
				//     mobileslideshow.js loads makes the page jump to the top of browser
				//     window -- definitely not what user was intending!
				// (2) At some point in the future, we can use this hash ref to load the
				//     image that the user wanted, after the right libraries are present.
				pq($a)->attr("href", "#" . $title->getLocalURL());

				$details = array(
					'smallUrl' => pq($a)->find("img")->attr("src"),
					'bigUrl' => $url,
					'smallWidth' => $smallWidth,
					'smallHeight' => $smallHeight,
					'bigWidth' => $bigWidth,
					'bigHeight' => $bigHeight,
				);

				// set the widths and height on the img for use in js to have placeholder space
				$img->attr( 'data-width', $smallWidth );
				$img->attr( 'data-height', $smallHeight );

				if ($showHighDPI) {
					//get all the info for retina images
					list($retina_small, $newWidth, $newHeight) =
						self::makeThumbDPI($imageObj, $smallWidth, $smallHeight, true, $pageId);
					list($retina_big, $newWidth, $newHeight) =
						self::makeThumbDPI($imageObj, $bigWidth, $bigHeight, true, $pageId);

					$retinaSmallUrl = wfGetPad($retina_small->getUrl());
					$retinaBigUrl = wfGetPad($retina_big->getUrl());

					$details['retinaSmall'] = $retinaSmallUrl;
					$details['retinaBig'] = $retinaBigUrl;

					$thumb_rs = $retinaSmallUrl.' '.$bigWidth.'w';
					$thumb_rb = $retinaBigUrl.' '.$bigWidth.'w';

					pq($img)->attr("retsmallset", $thumb_rs);
					pq($img)->attr("retbigset", $thumb_rb);
				}

				$licenseInfo = self::getImageLicenseInfo( $title, $imageLicensesOriginal, $imageObj );
				$details['licensing'] = $licenseInfo;
				$imageLicenses[$title->getArticleID()] = $licenseInfo;

				if ( $wgUser->isLoggedIn() ) {
					$details['instructions'] .= wfMessage( 'image_instructions', $title->getFullText() )->text();
				}

				$details = htmlentities( json_encode( $details ) );
				$detailsInner = Html::rawElement( 'span', ['style' => 'display:none;'],  $details );
				$detailsAttr = ['class' => 'image_details', 'style' => 'display:none;'];
				$imageDetailsHtml = Html::rawElement( 'div', $detailsAttr, $detailsInner );

				pq($a)->append( $imageDetailsHtml );
			}
		}

		// now save the image license info if it has changed
		if ( $imageLicenses != $imageLicensesOriginal ) {
			self::saveImageLicenses( $pageId, $imageLicenses );
		}

		// Remove logged in templates for logged out users so that they don't display
		if ($skin->getUser()->isAnon()) {
			foreach (pq(".tmp_li") as $template) {
				pq($template)->remove();
			}
		}

		// Move templates above article body contents and style appropriately
		foreach (pq('.template_top') as $template) {
			if ($skin->getUser()->isAnon()) {
				pq($template)->addClass('notice_bgcolor_lo');
			} else {
				pq($template)->addClass('notice_bgcolor_important');
			}
		}

		if ( !pq( '.template_top' )->find('#intro')->length ) {
			pq('.template_top')->insertAfter('#intro');
		}

		// remove any images that are next to m-video.
		// we moved them to be next to each other above, but left them
		// in order to create the poster image for the video
		pq( ".video-player" )->parent()->nextAll( '.image' )->remove();

		//remove all images in the intro that aren't
		//marked with the class "introimage"
		if ($wgTitle->inNamespace(NS_MAIN)) {
			pq("#intro .mwimg:not(.introimage)")->remove();
		}

		//let's remove all the empty p's in steps
		foreach (pq(".section.steps p") as $p) {
			if (pq($p)->parents(".steps_list_2")->count() == 0 && pq($p)->children(".anchor")->count() == 0) {
				$content = strtolower(pq($p)->html());
				if ($content == "<br>" || $content == "<br />") {
					pq($p)->remove();
				}
			}
		}

		// We add the deferred loading scripts until the end of this html
		$out = $context->getOutput();
		if ($config['display-deferred-javascript']) {
			$scripts = $out->getDeferHeadScripts();
			if ($scripts) {
				$scripts .= "<script>mw.mobileFrontend.emit( 'header-loaded' );</script>";
				pq('')->append($scripts);
			}
		}

		if ($wgLanguageCode == "en"  && $config['show-thumbratings']) {
			ThumbRatings::addMobileThumbRatingsHtml($doc, $docTitle);
		}

		foreach(pq('.section h3') as $methodHeader) {
			pq($methodHeader)->parent()->find('.section_text:first')->prepend($methodHeader);
		}

		DOMUtil::hideLinksForAnons();

		if ( $wgTitle->inNamespace(NS_MAIN) && PagePolicy::showCurrentTitle($context) ) {

			if ( $wgTitle->exists() ) {
				$parenttree = CategoryHelper::getCurrentParentCategoryTree();
				$fullCategoryTree = CategoryHelper::cleanCurrentParentCategoryTree($parenttree);

				$spStats = new SocialProofStats( $context, $fullCategoryTree );
				$sp = $spStats->getMobileHtml();
				if (!empty($sp)) $doc->append( $sp );
			}

			if (class_exists('UserReview')) {
				$ur = UserReview::getMobileReviews($pageId);
				if (!empty($ur)) $doc->append( $ur );
			}

			// mobile accuracy
			if ( $wgTitle->exists() ) {
				$ratingHtml = RatingArticle::getMobileForm( $pageId, $amp );
				$doc->append( $ratingHtml );
			}
		}

		if (class_exists('Recommendations') && $config['show-recommendations-test']) {
			$whr = new Recommendations();
			$doc->append($whr->getArticleDisplayHtml());
		}

		//removing all images from the tips and thingsyoullneed sections for now
		foreach (pq(".tips .mwimg, .thingsyoullneed .mwimg") as $img) {
			pq($img)->remove();
		}

		//add a clear to the end of each section_text to make sure
		//images don't bleed across the bottom
		pq(".section_text")->append("<div class='clearall'></div>");

		// Change ids for ingredients and things you'll need so CSS and other stuff works on intl
		if ( $wgLanguageCode != "en" ) {
			if (wfMessage('ingredients')->exists()) {
				$canonicalIngredients = WikihowArticleHTML::canonicalizeHTMLSectionName(wfMessage('ingredients')->text());
				pq("#" . $canonicalIngredients)->attr('id', 'ingredients');
			}
			// Thing you'll need fixing code goes haywire on Hindi, so take it out
			if ( $wgLanguageCode != "hi" ) {
				$canonicalThings = WikihowArticleHTML::canonicalizeHTMLSectionName(wfMessage('thingsyoullneed')->text());
				pq("#" . $canonicalThings)->attr('id', 'thingsyoullneed');
			}
			pq("#" . mb_strtolower(wfMessage('video')))->attr('id', 'video');
		}

		foreach (pq("#ingredients li, #thingsyoullneed li") as $item) {
			pq($item)->prepend("<div class='checkmark'></div>");
		}

		//linking to methods for ingredients (if they match)
		$minimumIngredientsLinkCharacters = 5;
		foreach (pq('#ingredients h3 .mw-headline') as $list_name) {
			$header = pq($list_name)->html();
			$header = preg_replace('/<.*>/', '', $header); //remove links and refs
			$header = trim($header);

			if (strlen($header) >= $minimumIngredientsLinkCharacters) {
				$header_matchable = Sanitizer::escapeIdForLink(htmlspecialchars_decode($header));
				$header_matchable = str_replace('_', '-', $header_matchable); //dashes; not underscores

				foreach(pq('.steps h3 .mw-headline') as $method) {
					$anchor = pq($method)->attr('id');

					if (stripos($anchor, $header_matchable) !== false) {
						$link = HTML::rawElement('a', ['href' => '#'.$anchor, 'class' => 'ingredient_method'], $header);

						$list_header = pq($list_name)->html();
						$list_header = preg_replace('/'.$header.'/', $link, $list_header);

						pq($list_name)->html($list_header);
						break;
					}
				}
			}
		}

		if ($config['show-related-articles']) {
			$relatedsName = RelatedWikihows::getSectionName();
			$relatedWikihows = new RelatedWikihows( $context, $wgUser, pq( ".section.".$relatedsName ) );
			$relatedWikihows->addRelatedWikihowsSection();
		}

		//add read more button
		if ($wgLanguageCode == "en") {
			$rm_button = '<div id="rmb_container" class="section_text" style="display:none"><input type="button" class="button primary" id="rmbutton" value="'.wfMessage('read_more_btn')->text().'" /></div>';
			pq('.relatedwikihows')->before($rm_button);
		}

		$referencesSection = pq( self::getReferencesSection() );

		//self::compareReferencesHtml( $skin );
		// get the php querty document id because the references code changes it
		// so ew can set it back later
		$phpQueryDocumentId = $referencesSection->getDocumentID();
		$referencesSectionHtml = self::getNewReferencesSection( $referencesSection );
		phpQuery::selectDocument($phpQueryDocumentId);
		//$referencesSectionHtml = self::formatReferencesSection( $skin );
		if ( $referencesSectionHtml ) {
			if ( pq( "#aboutthisarticle" )->length > 0 ) {
				pq( "#aboutthisarticle" )->before( $referencesSectionHtml );
			} else {
				pq( '#article_rating_mobile' )->before( $referencesSectionHtml );
			}
		}
		// this sets the php querty document id back to what it was before

		// because we appended the new references section we need to remove the old one
		pq( $referencesSection )->remove();

		if(class_exists("TrustedSources")) {
			TrustedSources::markTrustedSources($pageId);
		}

		$showUserImagesSection = $wgLanguageCode == 'en'
			&& class_exists('UserCompletedImages')
			&& isset($config['show-upload-images'])
			&& $config['show-upload-images']
			&& !$amp
			&& PagePolicy::showCurrentTitle($context)
			&& UserCompletedImages::validMobileUCIArticle( $wgTitle );

		if ( $showUserImagesSection ) {
			//put it under the references section, but sometimes there's a "more references" section tacked on
			$end_of_references = pq('.aidata')->length ? '.aidata' : self::getReferencesSection();
			pq( $end_of_references )->after( UserCompletedImages::getMobileSectionPlaceholder() );
		}

		if (count($summarySections) > 0) {
			foreach (pq(join(", ", $summarySections)) as $summarySection) {
				//move any summary sections lower
				pq(".steps:last")->after($summarySection);

				if (pq("p", $summarySection)->length > 0) {
					//move the text part only to the article info section
					$summaryText = pq("p.text_summary_wrapper", $summarySection);
					$summaryText->attr("id", "summary_text")->wrap("<div id='summary_wrapper' class='section_text'></div>");
					pq("#social_proof_mobile")->after(pq("#summary_wrapper", $summarySection));
					pq("#summary_wrapper")->prepend("<a href='#summary_wrapper' class='collapse_link'>" . wfMessage("summary_toc")->text() . "<span id='summary_close'>X</span></a>");
					//if there's no video summary, then remove that old section b/c nothing is left
					if (pq('video', $summarySection)->length == 0) {
						pq($summarySection)->remove();
					}
					if(!$amp) {
						$html = RateItem::getSummarySectionRatingHtml( false );
						$summaryText->append($html);
					}
				}

				//if there's a video summary, rename the section
				if ( pq('video', $summarySection)->length > 0) {
					$titleText = wfMessage('howto', $context->getTitle()->getText())->text();
					pq('.mw-headline', $summarySection)->html(wfMessage('qs_video_title')->text() . ": " . $titleText);

					// Add structured data
					if ( $videoSchema ) {
						pq('video', $summarySection)->after( SchemaMarkup::getSchemaTag( $videoSchema ) );
					}
					WikihowToc::setSummaryVideo();
				}

				//no edit for the summary section since we're moving to templates [sc: 5/2018]
				pq($summarySection)->find('.edit-page')->remove();
			}
		}

		if ( pq( '#summary_wrapper' )->length ) {
			WikihowToc::setSummary();
		}

		if ( !$amp ) {
			self::insertLanguageLinksHtml( $skin );
		}

		//remove the table under the video
		$table = pq("#video center table");
		if (pq($table)->attr("width") == "375px" ){
			pq($table)->remove();
		}
		//remove the <p><br><p> that's just causing blank space
		foreach (pq("#video p") as $paragraph) {
			$children = pq($paragraph)->children();
			if (pq($children)->length == 1 && pq($children[0])->is("br")) {
				pq($paragraph)->remove();
			}
		}

		foreach (pq("embed") as $node) {
			$src = pq($node)->attr("src");
			if (stripos($src, 'youtube.com') === false) {
				$parent = $node->parentNode;
				$grandParent = $parent->parentNode;
				if ($grandParent && $parent) {
					$grandParent->removeChild($parent);
				}
			} else {
				foreach (array(&$node, pq($node)->parent()) as $node) {
					$oldWidth = pq($node)->attr("width");
					$newWidth = $wgMFDeviceWidthMobileSmall;
					if ($newWidth < $oldWidth) {
						pq($node)->attr("width", $newWidth);
						$oldHeight = pq($node)->attr("height");
						$newHeight = (int)round($newWidth * $oldHeight / $oldWidth);
						pq($node)->attr("height", $newHeight);
					}
				}
			}
		}

		// remove video section if it has no iframe (which means it has no video)
		if ( pq("#video")->find('iframe')->length < 1 ) {
			pq(".video")->remove();
		}

		// Remove quizzes
		pq(".testyourknowledge")->remove();

		// Questions and Answers feature
		if (class_exists('QAWidget') && $config['show-qa'] && QAWidget::isTargetPage()) {
			$qaWidget = new QAWidget();
			$qaWidget->addWidget();
		}

		if ( $mobileTemplate
				&& $mobileTemplate->data
				&& $mobileTemplate->data['rightrail']
				&& $mobileTemplate->data['rightrail']->mAds ) {
			$mobileTemplate->data['rightrail']->mAds->addToBody();
		} else if ( $amp ) {
			$ads = new Ads( $context, $wgUser, $wgLanguageCode, array(), false );
			$ads->addToBody();
		}
		// TODO this
		//if (class_exists('AdblockNotice')) {
			//AdblockNotice::insertMobileNotice();
		//}

		foreach ( pq( ".embedvideo_gdpr:first" ) as $node ) {
			pq( $node )->parents( '.section:first' )->find( '.mw-headline:first' )->after( pq( $node )->html() );
		}
		pq( ".embedvideo_gdpr" )->remove();

		//if the article isn't nabbed, show a message
		// update: don't show the message if user has 'showdemoted' option
		$isUnnabbed = $wgLanguageCode == "en"
			&& $wgTitle->inNamespace(NS_MAIN)
			&& PagePolicy::showCurrentTitle($context)
			&& !NewArticleBoost::isNABbedNoDb($wgTitle->getArticleID())
			&& $wgUser->getOption('showdemoted') == '0';
		if ($isUnnabbed) {
			$intro = pq("#intro");
			$unnabbedAlert = "<div class='unnabbed_alert'><div><a href='#' id='nab_alert_close'>Ok,<br /> Close</a>" . wfMessage('nab_warning')->text() . " <a id='nab_learn' href='/Get-your-Article-Reviewed-or-Approved-on-wikiHow'>Learn more</a></div></div>";
			if (pq($intro)->length) {
				pq("#intro")->append($unnabbedAlert);
			} else {
				pq("p:first")->append($unnabbedAlert);
			}
		}

		pq('.section')->addClass('sticky');

// TODO this was a weird merge so check it out
		// Maybe include summary video in VideoCatalog
		$videoPlayer = pq( '#quick_summary_section .video-player' );
		if ( $videoPlayer && class_exists( 'VideoCatalog' ) && VideoCatalog::shouldIncludeSummaryVideo( $context ) ) {
			// Grab the original video source before the video is messed with
			$src = $videoPlayer->find( 'video' )->attr( 'data-src' );

			// Maybe replace inline player with link to VideoBrowser
			if ( $src && class_exists( 'VideoBrowser' ) && VideoBrowser::inlinePlayerShouldBeReplaced( $context ) ) {
				$link = pq( '<a id="summary_video_link">' )->attr(
					'href', '/Video/' . str_replace( ' ', '-', $wgTitle->getText() )
				);
				$poster = pq( '<img id="summary_video_poster">' )
					->attr( 'data-src', $videoPlayer->find( 'video' )->attr( 'data-poster' ) )
					->addClass( 'm-video' )
					->addClass( 'content-fill placeholder' );
				$controls = pq( WHVid::getSummaryIntroOverlayHtml( '', $wgTitle ) );
				// Include the structured data, which was appened to .video-player
				$videoPlayer->empty()->append( $link );
				$link->append( $poster );
				$link->append( Html::inlineScript(
					"WH.shared.addScrollLoadItem('summary_video_poster')"
				) );
				$link->append( Html::inlineScript(
					"WH.shared.addLoadedCallback('summary_video_poster', function(){WH.shared.showVideoPlay(this);})"
				) );
				$link->append( $controls );
			}
		}

		// Check the YouTube videos
		if ( pq( '.embedvideocontainer' )->length > 0 && WHVid::isYtSummaryArticle( $wgTitle ) ) {
			wikihowToc::setSummaryVideo( true );
			// Add schema to all YouTube videos that are from our channel
			foreach ( pq( '.embedvideo' ) as $video ) {
				$src = pq( $video )->attr( 'data-src' );
				preg_match( '/youtube\.com\/embed\/([A-Za-z0-9_-]+)/', $src, $matches );
				if ( $matches[1] ) {
					WikihowToc::setSummaryVideo(true);
					$videoSchema = SchemaMarkup::getYouTubeVideo( $wgTitle, $matches[1] );
					// Only videos from our own channel will have publisher information
					if ( $videoSchema && array_key_exists( 'publisher', $videoSchema ) ) {
						pq( $video )->after( SchemaMarkup::getSchemaTag( $videoSchema ) );
					}
				}
			}
		}

		if ($showTOC) {
			//we should have all the alt methods from further up,
			//let's create the links to them under the headline
			WikihowToc::setMethods($altMethodAnchors, $altMethodNames);

			if( pq('.section.tips')->length > 0 ) {
				WikihowToc::setTipsAndWarnings(true);
			} elseif ( pq('.section.warnings')->length > 0 ) {
				WikihowToc::setTipsAndWarnings(false);
			}
			$things = pq('.section.thingsyoullneed');
			if($things->length > 0) {
				if($things->nextAll(".section.tips, .section.warnings")->length > 0) {
					WikihowToc::setThingsYoullNeed(true);
				} else {
					WikihowToc::setThingsYoullNeed(false);
				}
			}
			if(pq('.section.ingredients')->length > 0) {
				WikihowToc::setIngredients();
			}

			WikihowToc::addMobileToc();
		}

		// add id to each stepslist2 li so we can make a url link to it if need be (like in the howto schema)
		$sectionNumber = 0;
		$stepNumber = 0;
		foreach ( pq( '.section.steps .steps_list_2 ' ) as $section ) {
			foreach ( pq( $section )->children( 'li' ) as $stepItem ) {
				// check if it has an id already..if it does not add one
				$stepId = pq( $stepItem )->attr( 'id' );
				if ( !$stepId ) {
					pq( $stepItem )->attr('id', 'step-id-' . $sectionNumber . $stepNumber );
				}
				$stepNumber++;
			}
			$sectionNumber++;
		}

		SchemaMarkup::calcHowToSchema( $out );
		SchemaMarkup::calcFAQSchema( $out );

		Hooks::run('MobileProcessArticleHTMLAfter', [ $skin->getOutput() ] );

		//stu font test
		if( ArticleTagList::hasTag("stu_test_font", $docTitle->getArticleID()) ) {
			pq(".mw-parser-output")->addClass("stu_test_font");
		}

		UserTiming::modifyDOM($canonicalSteps);
		PinterestMod::modifyDOM();
		if ( class_exists('ImageCaption') ) {
			ImageCaption::modifyDOM();
		}

		// AMP validation
		if ( $amp ) {
			GoogleAmp::modifyDom();
		} else {
			DeferImages::modifyDOM();
		}

		if ( Misc::isFastRenderTest() ) {
			self::fastRenderModifyDOM();
		}

		if ( class_exists( 'AlternateDomain' ) ) {
			AlternateDomain::modifyDom();
		}

		if (class_exists("Donate")) {
			Donate::addDonateSectionToArticle();
		}

		$html = $doc->documentWrapper->markup();
		if ($isUnnabbed) {
			$html = "<div class='unnabbed'>{$html}</div>";
		}

		if ( !$amp ) {
			$scripts = [];
			Hooks::run( 'AddTopEmbedJavascript', [&$scripts] );
			if ($scripts) {
				$html = Html::inlineScript(Misc::getEmbedFiles('js', $scripts)) . $html;
			}
		}

		return $html;
	}

	// gets list of licences on this page from memcached since it is very expensive to recompute
	// if not in memcached just return empty array and it will be recalculated later
	private static function getImageLicenses( $pageId ) {
		global $wgMemc;
		$cachekey = wfMemcKey( self::IMG_LICENSE_MEMCACHED_KEY, $pageId );
		$val = $wgMemc->get( $cachekey );
		if ( $val ) {
			return $val;
		}
		return array();
	}

	private static function saveImageLicenses( $pageId, $data ) {
		global $wgMemc;
		$cachekey = wfMemcKey( self::IMG_LICENSE_MEMCACHED_KEY, $pageId );
		$wgMemc->set( $cachekey, $data, 86400 * 30 );
	}

	private static function getImageLicenseInfo( $imageTitle, $imageLicenses, $imageObject ) {
		$id = $imageTitle->getArticleID();
		if ( isset( $imageLicenses[ $id ] ) ) {
			return $imageLicenses[ $id ];
		}

		$helper = $helper = new ImageHelper();
		$imagePage = WikiPage::newFromID( $imageTitle->getArticleID() );
		$result = $helper->getImageInfoMobile( $imagePage, $imageObject );
		return $result;
	}

	private static function fastRenderModifyDOM() {
		pq('.printfooter')->remove();
		foreach ( pq('.collapsible-block') as $node ) {
			if ( pq($node)->children()->count() == 0 && trim(pq($node)->html()) =='' ) {
				pq($node)->remove();
			}
		}
	}

	private static function getDefaultArticleConfig() {
		return array(
			'display-deferred-javascript' => true,
			'show-ads' => true,
			'show-qa' => true,
			'show-recommendations-test' => true,
			'show-related-articles' => true,
			'show-thumbratings' => true,
			'show-upload-images' => true,
		);
	}

	public static function getToolArticleConfig() {
		$config = self::getDefaultArticleConfig();
		$config['display-deferred-javascript'] = false;
		$config['show-ads'] = false;
		$config['show-qa'] = false;
		$config['show-recommendations-test'] = false;
		$config['show-related-articles'] = false;
		$config['show-thumbratings'] = false;
		$config['show-upload-images'] = false;
		return $config;
	}

	/**
	 *  Get the html for a given title (and revision).  A lot of this is taken from ExtMobileFrontend::DOMParse
	 * @param $revision
	 * @param $title
	 * @param $config
	 * @return String
	 */
	public static function getToolArticleHtml($title, $config, $revision = null, $wikitext = null) {

		// Assume the latest revision if none is specified
		if (is_null($revision)) {
			$revision = Revision::newFromTitle($title);
		}

		$oldContext = MobileContext::singleton()->getContext();
		$oldTitle = MobileContext::singleton()->getTitle();

		// Create a new context for the title we want to parse
		$context = new DerivativeContext(RequestContext::getMain());
		$context->setTitle($title);
		MobileContext::singleton()->setContext($context);
		$context = MobileContext::singleton();

		$out = MobileContext::singleton()->getOutput();
		// Set the title in the OutputPage to the title we want to parse
		$out->setTitle($title);

		// Assume wikitext from latest revision if none set
		if (is_null($wikitext)) {
			$wikitext = $revision->getContent( Revision::FOR_PUBLIC, null);
			$wikitext = ContentHandler::getContentText( $wikitext );
		}
		$html = $out->parse($wikitext);

		// Maybe ContentProviderFactory::getProvider() should be used instead,
		// but this way is easier
		$contentProvider = new \MobileFrontend\ContentProviders\DefaultContentProvider($html);
		$formatter = MobileFormatter::newFromContext($context, $contentProvider, true);

		Hooks::run('MobileFrontendBeforeDOM', array($context, $formatter));

		$specialPage = false;
		if ($context->getContentTransformations()) {
			// Remove images if they're disabled from special pages, but don't transform otherwise
			$formatter->filterContent( /* remove defaults */
				!$specialPage);
		}

		$html = $formatter->getText();
		$context->getSkin()->setRelevantTitle($title);
		$html = self::processDom($html, $context->getSkin(), $config);

		// Restore old values for the context
		//$out->setTitle($oldTitle);
		//MobileContext::singleton()->setContext($oldContext);
		return $html;
	}

	protected static function getFeaturedArticlesBoxResolutions() {
		return [
			'thumburl' => [
				'width' => 500,
				'height' => 320,
				'required' => true,
				'retina' => false,
				'type' => 'url'
			]
		];
	}

	/**
	 * Generate thumbnail link(s) to title image for given title.
	 *
	 * @param Title $title
	 * @param array $boxResolutions data structure defining what kinds of
	 *   thumbnail links to generate
	 * @param bool $forceProcessing
	 * @param bool $showHighDPI
	 * @param int $nameTruncateLength the length at which the overlay text should
	 *   be cut off and truncated with trailing ellipses
	 * @param int $maxWordLength titles with any word longer than this will not be
	 *   included
	 *
	 * @return stdClass an object with thumbnail links and other data to construct
	 *   an image box
	 *
	 * @see WikihowMobileTools::getFeaturedArticlesBoxResolutions()
	 */
	protected static function makeImageContainerBox(
		$title,
		$boxResolutions,
		$forceProcessing = false,
		$showHighDPI = false,
		$nameTruncateLength = 32,
		$maxWordLength = 12
	) {
		$box = new stdClass();
		$box->url = '';
		$box->name = '';
		$box->fullname = '';
		$box->title = $title;

		foreach ($boxResolutions as $resolutionKey => $resolutionInfo) {
			$box->$resolutionKey = '';
			if ($resolutionInfo['retina']) {
				$box->{$resolutionInfo['retina']} = '';
			}
		}

		if (!$title || !$title->exists()) return $box;

		if (!$forceProcessing) {
			// Exit if there's a word that'll be too long
			$word_array = explode(' ', $title->getText());
			foreach ($word_array as $word) {
				if (mb_strlen($word) >= $maxWordLength) return $box;
			}
		}

		$imageFile = Wikitext::getTitleImage($title);

		if (!$imageFile) {
			$imageFile = Wikitext::getDefaultTitleImage($title);
		}

		if ($imageFile) {
			$box->imgFile = $imageFile;
			$sourceWidth = $imageFile->getWidth();
			$sourceHeight = $imageFile->getHeight();

			// Let retina images retain default quality
			$retinaQuality = null;

			// We don't want watermarks
			$imagePageId = null;

			// Get thumbnails for each requested resolution, with optional retina versions.
			foreach ($boxResolutions as $resolutionKey => $resolutionInfo) {
				$displayWidth = $resolutionInfo['width'];
				$displayHeight = $resolutionInfo['height'];
				$quality = $resolutionInfo['quality'] ?: null;

				if ($displayWidth/$displayHeight < $sourceWidth/$sourceHeight) {
					$heightPreference = true;
				}

				$thumb = $imageFile->getThumbnail($displayWidth, $displayHeight, true, true, $heightPreference, $imagePageId, $quality);

				if ($thumb->getUrl() == '') {
					if ($resolutionInfo['required']) {
						return $box;
					} else {
						continue;
					}
				}

				$srcType = $resolutionInfo['type'];

				switch ($srcType) {
				case 'background-image':
					$box->$resolutionKey = 'background-image:url(' . wfGetPad($thumb->getUrl()) . ')';
					break;
				case 'url':
				default:
					$box->$resolutionKey = wfGetPad($thumb->getUrl());
				}

				if ($showHighDPI && $resolutionInfo['retina']) {
					list($retina) = self::makeThumbDPI($imageFile, $displayWidth, $displayHeight, true, $retinaQuality);

					$box->{$resolutionInfo['retina']} = $retina->getUrl();
				}
			}
		} else {
			return $box;
		}


		if (mb_strlen($title->getText()) > $nameTruncateLength + 3) {
			// Too damn long
			$titleText = mb_substr($title->getText(), 0, $nameTruncateLength) . '...';
		} else {
			// We good
			$titleText = $title->getText();
		}

		$box->url = $title->getLocalURL();
		$box->name = $titleText;
		$box->fullname = $title->getText();

		return $box;
	}

	public static function getImageContainerBoxHtml( $imageContainerBox ) {
		$url = $imageContainerBox->url;
		$titleText = $imageContainerBox->name;
		$thumbUrl = $imageContainerBox->thumburl;
		$title = $imageContainerBox->title;

		$thumbUrl = wfGetPad( $thumbUrl );
		$imgAttrs = [
			'class' => 'related_img wide',
			'src' => $thumbUrl
		];

		// look for video
		$videoUrl = ArticleMetaInfo::getVideoSrc( $title );
		if ( $videoUrl && !$imageContainerBox->noVideo ) {
			$attributes = [
				'src' => $videoUrl,
				'poster' => $thumbUrl,
			];
			$mediaElement = Misc::getMediaScrollLoadHtml( 'video', $attributes );
		} else {
			$mediaElement = Misc::getMediaScrollLoadHtml( 'img', $imgAttrs );
		}
		$mediaWrapper = Html::rawElement( 'div', ['class' => 'related_img_wrapper'], $mediaElement );

		$howToPrefix = wfMessage('howto_prefix')->showIfExists();
		$howToSuffix = wfMessage('howto_suffix')->showIfExists();
		$text = Html::element('span', [], $howToPrefix) . htmlspecialchars($titleText) . $howToSuffix;
		$label = Html::rawElement( 'p', [], $text );

		$html = Html::rawElement('a',  ['class' => 'related_box', 'href' => $url], $mediaWrapper . $label );

		return $html;
	}

	public static function makeFeaturedArticlesBox($title, $forceProcessing=false, $showHighDPI=false) {
		$nameTruncateLength = 32;
		$maxWordLength = RequestContext::getMain()->getLanguage()->getCode() == 'th' ? 17 : 12;

		return self::makeImageContainerBox(
			$title,
			self::getFeaturedArticlesBoxResolutions(),
			$forceProcessing,
			$showHighDPI,
			$nameTruncateLength,
			$maxWordLength
		);
	}

	// Bebeth: Taken from MobileHtmlBuilder.class.php
	// Make a thumb either regular res or high res (2x pixel density such
	// as retina display)
	private static function makeThumbDPI($image, $newWidth, $newHeight, $makeHighDPI, $pageId = null, $quality = null) {
		$thumbRender = true;
		$thumbCrop = false;
		$thumbHeightPref = false;

		// Always turn off the title display on mobile within images
		$pageId = null;

		if ($makeHighDPI) {
			$thumb = $image->getThumbnail(
				2 * $newWidth,
				2 * $newHeight,
				$thumbRender,
				$thumbCrop,
				$thumbHeightPref,
				$pageId,
				$quality
			);
			$actualWidth = $thumb->getWidth();
			$actualHeight = $thumb->getHeight();
			if ($actualWidth > $newWidth) {
				$nh = round( $actualHeight * $newWidth / $actualWidth );
				// if $nh is still too high, balance $newWidth
				if ($nh > $newHeight) {
					$newWidth = round( $newWidth * $newHeight / $nh );
				} else {
					$newHeight = $nh;
				}
			} elseif ($actualHeight > $newHeight) {
				$newWidth = round( $actualWidth * $newHeight / $actualHeight );
			} else {
				$newWidth = $actualWidth;
				$newHeight = $actualHeight;
			}
		} else {
			if ($image->getWidth() < $newWidth) {
				$thumb = $image;
			}
			else {
				$thumb = $image->getThumbnail(
					$newWidth,
					$newHeight,
					$thumbRender,
					$thumbCrop,
					$thumbHeightPref,
					$pageId,
					$quality
				);
			}
			$newWidth = $thumb->getWidth();
			$newHeight = $thumb->getHeight();
		}
		return array($thumb, $newWidth, $newHeight);
	}

	/**
	 * Get image quality based on the given title.
	 *
	 * Can be overridden by 'imgquality' GET requests.
	 *
	 * @param Title $title
	 *
	 * @return int|null
	 *
	 * @todo expand with more parameters?
	 */
	public static function getImageQuality($title) {
		global $wgRequest;

		$quality = $wgRequest->getIntOrNull('imgquality');

		if ($quality && $quality > 0 && $quality <= 95) {
			return $quality;
		} else {
			return null;
		}
	}

	/***
	 *
	 * Function determines whether Retina images should be turned on.
	 * Still needs to be implemented.
	 *
	 **/
	public static function isHighDPI($title) {

		if (!$title || !$title->exists())
			return false;

		$aid = $title->getArticleID();
		$showHighDPI = ArticleTagList::hasTag( 'retina-articles', $aid );
		return $showHighDPI;
	}

	public static function onMinervaViewportClasses(&$classes) {
		global $wgTitle;
		if (stripos('Userlogin', $wgTitle->getText()) !== false) {
			$classes[] = 'login';
		}
		return true;
	}

	// Moved and refactored from MobileWikihow class
	public static function getMobileSite() {
		global $wgServer, $wgNoMobileRedirectTest;
		$domainRegex = wfGetDomainRegex(
			false, // mobile?
			true, // includeEn?
			true // capture?
		);
		if (preg_match('@^((https?:)?//)' . $domainRegex . '@', $wgServer, $m)) {
			$protocol = $m[1];
			$domain = $m[2];
			$lang = wfGetLangCodeFromDomain($domain);
			return $protocol . wfCanonicalDomain($lang, true);
		} else {
			if (!$wgNoMobileRedirectTest && !preg_match('@\bm\.@', $wgServer)) {
				return preg_replace('@^((https?:)?//[^\.]*)\.(.+\.[^/]+)$@', '$1.m.$3', $wgServer);
			} else {
				return $wgServer;
			}
		}
		$domain = wfCanonicalDomain('', true);
		return '//' . $domain;
	}

	public static function getNonMobileSite() {
		global $wgLanguageCode, $wgIsDevServer, $wgDomainName;

		if (!$wgIsDevServer) {
			$domain = Misc::getCanonicalDomain();
		} else {
			// On dev, we just remove the m- from the url and that
			// should send us to the right place.
			$newDomain = preg_replace('@(\b|^)m-@', '', $wgDomainName);
			if ($newDomain != $wgDomainName) {
				$domain = $newDomain;
			} else {
				// or remove -m if we couldn't remove m-
				$domain = preg_replace('@(\b|^)-m\.@', '.', $wgDomainName);
			}
		}

		return 'https://' . $domain;
	}

	/**
	 * Returns true if and only if this request is from the
	 * internet.org proxy OR it's a test internetorg=true request.
	 */
	public static function isInternetOrgRequest() {
		global $wgRequest;
		if ($wgRequest) {
			$viaHeader = $wgRequest->getHeader('Via');
			$xiorgHeader = $wgRequest->getHeader('x-internetorg');
			if (stripos($viaHeader, 'Internet.org') !== false
				|| $xiorgHeader === '1'
				|| $wgRequest->getVal('internetorg') == 'true'
			) {
				return true;
			}
		}
		return false;
	}

	public static function getInternetOrgAnalytics() {
		global $wgRequest;

		$url = "https://www.google-analytics.com/__utm.gif?";
		$params = array();
		$params['utmn'] = rand(0, 10000);
		$params['utmhn'] = @$_SERVER['SERVER_NAME'];
		$params['utmp'] = $wgRequest->getRequestURL();
		$params['utmr'] = "~"; //referrer
		$params['utmac'] = "UA-2375655-12";
		$params['utmcc'] = "";
		$params['utmwv'] = 1;
		$params['utmcn'] = 1;

		$url .= http_build_query($params);
		return "<img src='{$url}' />";
	}

	private static function insertLanguageLinksHtml( $skin ) {
		global $wgLanguageCode;

		$isIndexed = RobotPolicy::isIndexable( $skin->getTitle(), $skin->getContext() );
		if ( !$isIndexed ) {
			return;
		}

		$alternateDomain = AlternateDomain::onAlternateDomain();
		if ( $alternateDomain ) {
			return;
		}

		//other languages
		$languageLinks = WikihowSkinHelper::getLanguageLinks();

		$linksHtml = '';
		// if we are on english, then we need to remove any links to alt domains
		foreach ( $languageLinks as $langLink ) {
			if ( !$langLink ) {
				continue;
			}
			if ( $langLink['lang'] == 'es' ) {
				$altDomains = AlternateDomain::getAlternateDomains();
				foreach ( $altDomains as $altDomain ) {
					if ( strstr( $langLink['href'], $altDomain ) ) {
						continue;
					}
				}
			}
			$linkText = $langLink['text'];
			$text = Html::rawElement( 'span', [], htmlspecialchars( trim( $langLink['language'] ) ) );
			$link = Html::rawElement( 'a', ['href' => htmlspecialchars( $langLink['href'] )], $linkText );
			$linksHtml .= Html::rawElement( 'div', ['class' => 'language_link'], $text . $link );
			//$linksHtml .= htmlspecialchars(trim($langLink['text'])) . '&nbsp;<span><a href="' .  htmlspecialchars($langLink['href']) . '">' .  $linkText . "</a></span>";
		}

		if ( !$linksHtml ) {
			return;
		}

		// wrap it
		$linksHtml = Html::rawElement( 'div', ['id' => 'language_links'], $linksHtml );

		$linksHeader = Html::rawElement( 'a', ['href' => '#other_languages', 'class' => 'collapse_link'], wfMessage('otherlanguages')->text() );
		$html = Html::rawElement( 'div', ['id' => 'other_languages', 'class' => 'section_text'], $linksHeader . $linksHtml );

		if ( pq( '#summary_wrapper' )->length ) {
			pq( '#summary_wrapper' )->after( $html );
		} elseif ( pq( '#social_proof_mobile' )->length ) {
			pq( '#social_proof_mobile' )->after( $html );
		}
	}

	//identify our references section so we can mess with it
	private static function getReferencesSection() {
		if (!is_null(self::$referencesSection)) return self::$referencesSection;

		$sourcesSectionClass = ".".Misc::getSectionName( (wfMessage('sources')->text()));

		// if there is no sources section try references instead
		if ( pq( $sourcesSectionClass )->length < 1 ) {
			$referencesSectionClass = ".section.".Misc::getSectionName( (wfMessage('references')->text()));
			self::$referencesSection = $referencesSectionClass;
		}
		else {
			self::$referencesSection = $sourcesSectionClass;
		}

		return self::$referencesSection;
	}

	private static function formatReferencesSection( $skin ) {
		$sourcesSection = pq( self::getReferencesSection() )->clone();

		pq( $sourcesSection )->find( '.section-heading' )->removeAttr( 'onclick' );
		pq( $sourcesSection )->find( '.section_text' )->prepend( '<ol class="firstref references">' );

		//open all links in new tabs
		pq( $sourcesSection )->find('a')->attr('target','_blank');

		// take out all li items and move them in to an ol
		foreach ( pq( $sourcesSection )->find( 'li' ) as $listItem ) {
			// clone the item so  we do not mess with the data in our list we are iterating over
			$tempListItem = pq( $listItem )->clone();
			// remove any sub lists from each item
			pq( $tempListItem )->find( 'ol,ul' )->remove();

			$text = pq( $tempListItem )->text();
			// skip any empty items
			if ( !trim( $text ) ) {
				continue;
			}
			// if the item does not have a ref text class wrap it in a span with that class
			if ( !pq( $tempListItem )->find( '.reference-text' )->length ) {
				pq( $tempListItem )->wrapinner('<span class="reference-text">');
			}
			// add this to the new list of references which will replace the existing one
			pq( $sourcesSection )->find( '.firstref.references' )->append( $tempListItem );
		}

		foreach ( pq( $sourcesSection )->find( '.section_text' )->children() as $child ) {
			if ( pq( $child )->hasClass('firstref') ) {
				continue;
			}
			pq( $child )->remove();
		}

		// add classes to the section so we can target it with css
		// also remove any stray p tags
		pq( $sourcesSection )->addClass( 'aidata' )->find('p')->remove();
		pq( $sourcesSection )->addClass( 'sourcesandcitations' );

		// change title of section if user is anon
		if ( $skin->getUser()->isAnon()) {
			pq( $sourcesSection )->find('.mw-headline')->text( wfMessage( 'references' )->text() );
		}

		$referencesFirst = pq( $sourcesSection )->clone();
		pq( $referencesFirst )->find('div:first')->attr('id', 'references_first');
		pq( $referencesFirst )->removeClass( 'aidata' );
		pq( $referencesFirst )->find( 'li:gt(8)' )->remove();

		pq( $sourcesSection )->find( 'li:lt(8)' )->remove();
		pq( $sourcesSection )->find( 'h2' )->remove();
		pq( $sourcesSection )->find( 'ol' )->attr('start', 10);
		pq( $sourcesSection )->find('div:first')->attr('id', 'references_second');

		// remove all ISBN links
		foreach ( pq( $referencesFirst )->find( '.mw-magiclink-isbn' ) as $isbn ) {
			$replaceText = pq( $isbn )->text();
			pq( $isbn )->replaceWith( $replaceText );
		}

		$referencesHtml = $referencesFirst;

		// create a show more link if needed
		$moreCount = pq( $sourcesSection )->find( 'li' )->length;
		if ( $moreCount > 0 ) {
			$showMore = Html::element( 'a', ['id' => 'info_link', 'href' => '#aiinfo'], wfMessage('more_references', $moreCount)->text() );
			$sectionText = Html::rawElement( 'div', ['id'=>'articleinfo', 'class'=>'section_text'], $showMore );
			$articleInfoHtml = Html::rawElement( 'div', ['id' => 'aiinfo', 'class' => 'section articleinfo'], $sectionText );
			$referencesHtml .= $articleInfoHtml . $sourcesSection;
		}

		return $referencesHtml;
	}

	private static function getReferencesListFromReferencesSection( $referencesText ) {
		$result = [];

		$referencesText->find('a')->attr('target','_blank');
		// take out all li items and move them in to an ol
		foreach ( $referencesText->find( 'li' ) as $listItem ) {
			// clone the item so  we do not mess with the data in our list we are iterating over
			$tempListItem = phpQuery::newDocument( pq( $listItem ) );

			// remove any sub lists from each item
			$tempListItem->find( 'ol,ul' )->remove();

			$text = $tempListItem->text();
			// skip any empty items
			if ( !trim( $text ) ) {
				continue;
			}
			// if the item does not have a ref text class wrap it in a span with that class
			if ( $tempListItem->find( '.reference-text' )->length == 0 ) {
				$tempListItem->find('li')->wrapInner('<span class="reference-text">');
			}
			$result[] = $tempListItem->html();
		}

		return $result;
	}

	// input: referencesSection - php query object which is the references section
	// output: the html of the reformatted references section
	private static function getNewReferencesSection( $referencesSection ) {
		$refsList = self::getReferencesListFromReferencesSection( $referencesSection->find( '.section_text' ) );
		if ( !count( $refsList ) ) {
			return '';
		}

		// these for now are used for ids and class names below but that should be changed to just english strings
		$refMsg = wfMessage( 'references' )->text();
		$refLowerCase = strtolower( $refMsg );

		// create the first section
		// TODO all these classes are not needed eventually
		$sectionHeadingInner = Html::element( 'div', ['class' => 'mw-ui-icon mw-ui-icon-element indicator', 'id' => 'references_first'] );
		$sectionHeadingInner .= Html::element( 'span', ['class' => 'mw-headline', 'id' => $refMsg], $refMsg );
		$sectionHeading = Html::rawElement( "h2", ['class' => 'section-heading'], $sectionHeadingInner );

		$refsFirst = array_slice( $refsList, 0, 9 );
		$refsFirst = implode($refsFirst);
		$refsFirstList = Html::rawElement( 'ol', ['class' => 'firstref references'], $refsFirst );
		$sectionText = Html::rawElement( 'div', ['id' => $refLowerCase, 'class' => 'section_text'], $refsFirstList );


		$firstSectionInner = $sectionHeading . $sectionText;
		$firstSection = Html::rawElement( 'div', ['class' => 'section references sourcesandcitations'], $firstSectionInner );
		$result = $firstSection;

		// create the second section if needed
		if ( count( $refsList ) > 9 ) {
			$refsSecond = array_slice( $refsList, 9 );
			$moreCount = count( $refsSecond );

			$showMore = Html::element( 'a', ['id' => 'info_link', 'href' => '#aiinfo'], wfMessage( 'more_references', $moreCount )->text() );
			$articleInfoSectionInner = Html::rawElement( 'div', ['id'=>'articleinfo', 'class'=>'section_text'], $showMore );
			$articleInfoSection = Html::rawElement( 'div', ['id' => 'aiinfo', 'class' => 'section articleinfo'], $articleInfoSectionInner );

			$refs = implode( $refsSecond );
			$refsList = Html::rawElement( 'ol', ['class' => 'firstref references', 'start' => 10], $refs );
			$sectionInner = Html::rawElement( 'div', ['id' => 'references_second', 'class' => 'section_text'], $refsList );
			$secondSection = Html::rawElement( 'div', ['class' => 'section references aidata sourcesandcitations'], $sectionInner );

			$result .= $articleInfoSection . $secondSection;
		}

		return $result;
	}

	// no currently used, but was used to help in refactoring the references section
	// to make sure new and old output match, so it is kind of useful to keep around for
	// future refactoring
	private static function compareReferencesHtml( $skin ) {
		// for testing the old vs new way of creating references
		$referencesSection = pq( self::getReferencesSection() );
		$old = self::formatReferencesSection( $skin );
		$new = self::getNewReferencesSection( $referencesSection );
		$old = str_replace(array("\n", "\r"), '', $old);
		$new = str_replace(array("\n", "\r"), '', $new);
		if ( $old != $new ) {
			decho('old', $old);
			decho('new', $new);
			exit;
		}
	}

}
