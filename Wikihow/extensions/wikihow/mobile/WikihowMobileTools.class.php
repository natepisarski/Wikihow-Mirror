<?php

if (!defined('MEDIAWIKI')) die();

class WikihowMobileTools {
	private static $tableOfContentsHtml = '';

	const SMALL_IMG_WIDTH = 460;

	static function onMobilePreRender($mobileTemplate) {
		global $wgOut;

		// only do this for article pages
		if ($wgOut->isArticle()) {
			$mobileTemplate->data['bodytext'] = self::processDom($mobileTemplate->data['bodytext'], $mobileTemplate->getSkin());
			$mobileTemplate->data['tableofcontents'] = self::getTableOfContents();
		}

		if ( !GoogleAmp::isAmpMode($wgOut) ) {
			StuLogger::endMobilePreRender($mobileTemplate, $wgOut->getContext());
		}

		return true;
	}

	static function processDom($text, $skin, $config = null) {
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

		// Contains elements with the raw titles of methods (i.e. non-parts)
		$nonAltMethodElements = array();
		$showTOC = false;

		foreach (pq("h2") as $node) {
			$h2Parent = pq($node)->parent();
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
						foreach ( pq("div#{$sectionName}:first") as $temp){
							$overallSet[] = $temp;
						}

						try {
							pq($overallSet)->wrapAll("<div class='section steps'></div>");
						} catch (Exception $e) {
						}
					}
					else {
						//hide the h2 tag
						pq($node)->addClass("hidden");
					}

					$stepsEditUrl = pq('.editsection', $node)->attr("href");

					$displayMethodCount = $h3Count;
					$isSample = array();
					for ($i = 1; $i <= $h3Count; $i++) {
						$isSampleItem = false;
						if (!is_array($h3Elements[$i]) || count($h3Elements[$i]) < 1) {
							$isSampleItem = false;
						}
						else {
							//the sd_container isn't always the first element, need to look through all
							foreach ($h3Elements[$i] as $node) { //not the most efficient way to do this, but couldn't get the find function to work.
								if (pq($node)->attr("id") == "sd_container") {
									$isSampleItem = true;
									break;
								}
							}
						}
						if ( $isSampleItem )
						{
							$isSample[$i] = true;
							$displayMethodCount--;
						} else {
							$isSample[$i] = false;
						}
					}

					/*if ($ads) {
						wikihowAds::setAltMethods($displayMethodCount > 1);
					}*/

					$wikitext = ContentHandler::getContentText($context->getWikiPage()->getContent(Revision::RAW));
					$hasParts = MagicWord::get( 'parts' )->match( $wikitext );

					$displayMethod = 1;
					for ($i = 1; $i <= $h3Count; $i++) {

						$methodTitle = htmlspecialchars_decode(pq("span.mw-headline", $h3Tags[$i])->html());
						$methodTitle = pq("<div>$methodTitle</div>")->text();
						$altMethodNames[] = $methodTitle;
						$altMethodAnchors[] = pq("span.mw-headline", $h3Tags[$i])->attr("id");
						$displayMethodWord = ucwords(Misc::numToWord($displayMethod,10));
						$displayMethodCountWord = ucwords(Misc::numToWord($displayMethodCount,10));
						if ($displayMethodCount > 1 && !$isSample[$i] && $hasParts && $docTitle->inNamespace(NS_MAIN)) {
							if ($methodTitle) {
								$methodTitle = wfMessage("part_mobile_2",$displayMethodWord,$displayMethodCountWord,$methodTitle)->text();
							} else {
								$methodTitle = wfMessage("part_1",$displayMethodWord, $displayMethodCountWord)->text();
							}
							$displayMethod++;
						} elseif ($displayMethodCount > 1 && !$isSample[$i] && $docTitle->inNamespace(NS_MAIN)) {
							$nonAltMethodElements[] = pq("span.mw-headline", $h3Tags[$i])->clone();
							if ($methodTitle) {
								$methodTitle = wfMessage("method_mobile_2",$displayMethodWord,$displayMethodCountWord, $methodTitle)->text();
							} else {
								$methodTitle = wfMessage("method_1",$displayMethodWord,$displayMethodCountWord)->text();
							}
							$displayMethod++;
						}
						pq("span.mw-headline", $h3Tags[$i])->html($methodTitle);

						//want to change the url for the edit link to
						//edit the whole steps section, not just the
						//alternate method
						//pq(".editsection", $h3Tags[$i])->attr("href", $stepsEditUrl);

						$sample = $isSample[$i] ? "sample" : "";

						//only wrap if there's stuff there to wrap.
						//This happens when people put two sub methods on top of each other without
						//any content between.
						if (count($h3Elements[$i]) > 0) {
							pq($h3Elements[$i])->wrapAll("<div id='{$sectionName}_{$i}' class='section_text'></div>");
						}
						$overallSet = array();
						$overallSet[] = $h3Tags[$i];
						foreach ( pq("div#{$sectionName}_{$i}:first") as $temp){
							$overallSet[] = $temp;
						}
						try {
							pq($overallSet)->wrapAll("<div class='section steps {$sample}'></div>");
							pq('.section.steps:first')->addClass('steps_first');
						} catch (Exception $e) {
						}
					}

					//BEBETH - not sure we need this anymore, but not sure yet
					//fix for Chrome -- wrap first anchor name so it detects the spacing
					/*try {
						pq(".section.steps:first")->prev()->children(".anchor")->after('<br class="clearall" />')->wrapAll('<div></div>');
					} catch (Exception $e) {
					}*/
				}

				$showTOC = !$amp
					&& !self::isInternetOrgRequest()
					&& !AndroidHelper::isAndroidRequest()
					&& !$wgContLang->isRTL();

				if ($h3Count > 0) {
					//chance to reformat the alt method_toc before output
					//using for running tests
					$bAfter = false;
					Hooks::run('BeforeOutputAltMethodTOC', array($docTitle, &$anchorList, &$bAfter));
				} else {
					if ($set) {
						try {
							pq($set)->wrapAll("<div id='{$sectionName}' class='section_text'></div>");
						} catch (Exception $e) {
						}
					}

					$overallSet = array();
					$overallSet[] = $node;
					foreach ( pq("div#{$sectionName}:first") as $temp){
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
					foreach (pq(".steps .mw-headline span") as $headline) {
						$methodType = ($hasParts?"Part ":"Method ") . $count;
						$methodTitle = pq($headline)->html();
						$quiz = $articleQuizzes->getQuiz($methodTitle, $methodType);
						if ($count == 1 && $articleQuizzes->showFirstAtTop()) {
							pq($headline)->parent()->parent()->parent()->prepend($quiz);
						} else {
							pq($headline)->parent()->parent()->parent()->append($quiz);
							if ($articleQuizzes->showFirstAtTop()) { //this is temporary while we test
								pq($headline)->parent()->parent()->parent()->find(".qz_top_info")->remove();
							}
						}
						$count++;
					}
					pq(".qz_container:last")->addClass("qz_last"); //need this for amp purposes
				}

			} else {
				//list page?
				$list_page = $docTitle->inNamespace(NS_PROJECT) && ($docTitle->getDbKey() == 'RSS-feed' || $docTitle->getDbKey() == 'Rising-star-feed');

				//not a steps section
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

				// commenting this out because it's causing the following error:
				// "Couldn't add newnode as the previous sibling of refnode"
				// // format edit links for non-steps sections
				// // pq('span', $node)->prepend(pq('a.edit', $node));

				//remove the edit link from subheaders if we're not in the steps section
				/*try {
					pq(".{$sectionName} h3 .editsection")->remove();
				} catch(Exception $e) {
				}*/
			}
		}

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

				//keeping this code for now, we'll likely bring back soon.
				/*$headingImages = pq( $headingId . ' .mwimg' )->addClass( 'summarysection' );
				foreach ( $headingImages as $headingImage ) {
					$headingImage = pq( $headingImage )->remove();
					if ( $headingImage ) {
						pq( $headingId )->prepend( pq( $headingImage ) );
					}
				}*/
			}
		}

		// add the controls
		pq( '.summarysection video' )->addClass( 'summary-m-video' )->parent()->after( WHVid::getVideoControlsSummaryHtml( $summaryIntroHeadingText ) );

		//give the whole section a consistent id
		pq( '.summarysection .video-player' )->parents( '.summarysection' )->eq( 0 )->attr( 'id','quick_summary_section');

		//give the summary video title a consistent id (like the other sections)
		pq( '.summarysection .video-player')->parents( '.section' )->find('h2 span')->attr( 'id', 'quick_summary_video_section');

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

		//deal with swapping out all images for tablet
		//and putting in the right size image
		foreach (pq(".mwimg a") as $a) {
			$img = pq($a)->find('img');
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
				$smallHeight = $smallWidth*$srcHeight/$srcWidth;
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
							$smallWidth = $smallHeight*$srcWidth/$srcHeight;
							$srcSet = null;
							$layout = "fixed";
						}
						$ampImg = GoogleAmp::getAmpArticleImg( $smallSrc, $smallWidth, $smallHeight, $srcSet, $layout );
						pq($img)->replaceWith( $ampImg );
					}
					continue;
				}

				$thumb_ss = $url;

				pq($img)->attr("data-srclarge", $thumb_ss);
				// need to add microtime to handle the editor overlays so there aren't 2 images with the same id on the page
				$thumb_id = md5(pq($img)->attr("src") . microtime());
				pq($img)->attr("id", $thumb_id);

				foreach (pq($img)->parents(".mwimg") as $parent) {
					pq($parent)->attr("style", "");
				}


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

				pq($a)->append("<div class='image_details' style='display:none'><span style='display:none'>" . htmlentities(json_encode($details)) . "</span></div>");
			}
		}

		// remove any images that are next to m-video.
		// we moved them to be next to each other above, but left them
		// in order to create the poster image for the video
		pq( ".video-player" )->parent()->nextAll( '.image' )->remove();

		//remove all images in the intro that aren't
		//marked with the class "introimage"
		pq("#intro .mwimg:not(.introimage)")->remove();

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

			TipsAndWarnings::addRedesignCTAs($doc, $docTitle);
		}

		//[sc] 12/2018 - removing UCI from mobile
		// $showUserImagesSection = $wgLanguageCode == 'en'
		// 	&& class_exists('UserCompletedImages')
		// 	&& isset($config['show-upload-images'])
		// 	&& $config['show-upload-images']
		// 	&& !$amp
		// 	&& PagePolicy::showCurrentTitle($context);

		// if ( $showUserImagesSection ) {
		// 	$uci = UserCompletedImages::getMobileSectionHTML( $context );
		// 	$doc->append($uci);
		// }

		DOMUtil::hideLinksInArticle();

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
			$canonicalIngredients = WikihowArticleHTML::canonicalizeHTMLSectionName(wfMessage('ingredients')->text());
			pq("#" . $canonicalIngredients)->attr('id', 'ingredients');
			// Thing you'll need fixing code goes haywire on Hindi, so take it out
			if ( $wgLanguageCode != "hi" ) {
				$canonicalThings = WikihowArticleHTML::canonicalizeHTMLSectionName(wfMessage('thingsyoullneed')->text());
				pq("#" . $canonicalThings)->attr('id', 'thingsyoullneed');
			}
			pq("#" . mb_strtolower(wfMessage('video')))->attr('id', 'video');

		}

		foreach (pq("#ingredients h3, #thingsyoullneed h3") as $item) {
			pq($item)->prepend('<div class="altblock"></div>');
		}

		foreach (pq("#ingredients li, #thingsyoullneed li") as $item) {
			pq($item)->prepend("<div class='checkmark'></div>");
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

		self::formatReferencesSection( $skin );

		//move any samples section below the last steps section
		foreach (pq(".sample") as $sample) {
			pq(".steps:last")->after($sample);
		}

		if (count($summarySections) > 0) {
			foreach (pq(join(", ", $summarySections)) as $summarySection) {
				//move any summary sections lower
				pq(".steps:last")->after($summarySection);

				if (pq("p", $summarySection)->length > 0) {
					//move the text part only to the article info section
					$summaryText = pq("p", $summarySection);
					$summaryText->attr("id", "summary_text")->wrap("<div id='summary_wrapper' class='section_text'></div>");
					pq("#social_proof_mobile")->after(pq("#summary_wrapper", $summarySection));
					pq("#summary_wrapper")->prepend("<a href='#summary_wrapper' class='collapse_link'>" . wfMessage("summary_toc")->text() . "</a>");
					//if there's no video summary, then remove that old section b/c nothing is left
					if (pq('video', $summarySection)->length == 0) {
						pq($summarySection)->remove();
					}
				}

				//if there's a video summary, rename the section
				if ( pq('video', $summarySection)->length > 0) {
					pq('.mw-headline', $summarySection)->html(wfMessage('qs_video_title')->text());

					// Add structured data
					if ( $videoSchema ) {
						pq('video', $summarySection)->after( SchemaMarkup::getSchemaTag( $videoSchema ) );
					}

				}

				//no edit for the summary section since we're moving to templates [sc: 5/2018]
				pq($summarySection)->find('.edit-page')->remove();

				//no last sentence for mobile (since we're forcing it lower)
				pq('#summary_last_sentence')->remove();
			}
		}

		if ( !$amp ) {
			self::insertLanguageLinksHtml( $skin );
		}

		// Remove logged in templates for logged out users
		// so that they don't display
		if ($wgUser->getID() == 0) {
			foreach (pq(".tmp_li") as $template) {
				pq($template)->remove();
			}
		}

		//remove the table under the video
		$table = pq("#video center table");
		if (pq($table)->attr("width") == "375px" ){
			pq($table)->remove();
		}
		//remove the <p><br><p> that's just causing blank space
		foreach (pq("#video p") as $paragraph) {
			$children = pq($paragraph)->children();
			//var_dump($children[0]);
			if (pq($children)->length == 1 && pq($children[0])->is("br")) {
				pq($paragraph)->remove();
			}
		}

		foreach (pq("embed") as $node) {
			$url = '';
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

		if ($config['show-ads'] && wikihowAds::isEligibleForAds() && !wikihowAds::isExcluded($docTitle)) {
			wikihowAds::insertMobileAds();

			if (class_exists('AdblockNotice')) {
				AdblockNotice::insertMobileNotice();
			}
		}

		if ($showTOC) {
			//we should have all the alt methods from further up,
			//let's create the links to them under the headline
			$vars = [
				'toc' => self::makeTableOfContentsAnchors($altMethodAnchors, $altMethodNames, $isSample)
			];

			// Instead of appending the TOC to the DOM here, just store it in a static
			// variable so it can be inserted outside the '.content' div
			self::$tableOfContentsHtml = WikihowToc::mobileToc($vars);
		}

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

		// we do not have sticky section headers anymore so remove the class
		// we do this because we often add the same html for mobile and desktop and
		// this is a way to ensure that the sticky class is not added when it is not needed
		pq('.section.sticky')->removeClass('sticky');

		// Trevor, 11/8/18 - Testing making videos a link to the video browser - this must come
		// after videos are updated
		// Trevor, 3/1/19 - Check article being on alt-domain, not just which domain we are on, logged in
		// users can see alt-domain articles on the main site
		// Trevor, 6/18/19 - Make a special exception for recipe articles, play those inline
		$recipeSchema = SchemaMarkup::getRecipeSchema( $wgTitle, $context->getOutput()->getRevisionId() );
		if ( !$recipeSchema && $wgLanguageCode == 'en' && !Misc::isAltDomain() && !GoogleAmp::isAmpMode( $context->getOutput() ) ) {
			$videoPlayer = pq( '.summarysection .video-player' );
			if ( $videoPlayer ) {
				$link = pq( '<a id="summary_video_link">' )->attr(
					'href', '/Video/' . str_replace( ' ', '-', $context->getTitle()->getText() )
				);
				$poster = pq( '<img id="summary_video_poster">' )->attr( 'data-src', $videoPlayer->find( 'video' )->attr( 'data-poster' ) );
				$poster->addClass( 'm-video' );
				$poster->addClass( 'content-fill placeholder' );
				$controls = pq( WHVid::getSummaryIntroOverlayHtml( '', $wgTitle ) );
				$videoPlayer->empty()->append( $link );
				$link->append( $poster );
				$link->append( Html::inlineScript( "WH.shared.addScrollLoadItem('summary_video_poster')" ) );
				$link->append( Html::inlineScript( "WH.shared.addLoadedCallback('summary_video_poster', function(){WH.shared.showVideoPlay(this);})" ) );
				$link->append( $controls );
			}
		}

		if( pq('.embedvideocontainer')->length > 0 && WHVid::isYtSummaryArticle($wgTitle)) {
			// Add schema to all YouTube videos that are from our channel
			foreach ( pq( '.embedvideo' ) as $video ) {
				$src = pq( $video )->attr( 'data-src' );
				preg_match( '/youtube\.com\/embed\/([A-Za-z0-9_-]+)/', $src, $matches );
				if ( $matches[1] ) {
					$videoSchema = SchemaMarkup::getYouTubeVideo( $wgTitle, $matches[1] );
					// Only videos from our own channel will have publisher information
					if ( is_array( $videoSchema ) && array_key_exists( 'publisher', $videoSchema ) ) {
						pq( $video )->after(
							SchemaMarkup::getSchemaTag( $videoSchema ) .
							'<!-- ' . (
								$videoSchema ?
									'YouTube info from cache' :
									'YouTube info being fetched'
								) .
							' -->'
						);
					}
				}
			}
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

		Hooks::run('MobileProcessArticleHTMLAfter', [ $skin->getOutput() ] );

		UserTiming::modifyDOM($canonicalSteps);
		PinterestMod::modifyDOM();
		ImageCaption::modifyDOM();

		// AMP validation
		if ( $amp ) {
			GoogleAmp::modifyDom();
		} else {
			DeferImages::modifyDOM();
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


		$formatter = MobileFormatter::newFromContext($context, $html);


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

	/**
	 * @return array data structure describing additional elements for the table
	 *   of contents.
	 */
	protected static function getTOCExtras() {
		global $wgTitle;

		$extraTOCPreData = [];
		$extraTOCPostData = [];

		// Some functional magic to transform MW messages into their corresponding
		// section header's element ID.
		list(
			$ingredientsAnchor,
			$stepsAnchor,
			$videoAnchor,
			$tipsAnchor,
			$warningsAnchor,
			$thingsyoullneedAnchor,
			$relatedAnchor
		) = array_map(
			function ($m) {
				return preg_replace(
					['@%@', '@\+@'],
					['.', '_'],
					urlencode(wfMessage($m)->plain())
				);
			},
			self::getTOCSectionMessages()
		);

		$otherWikihowsAnchor = 'Other_wikiHows';

		// Note that pq() doesn't like dots in identifiers,
		// and doesn't seem to have a way to escape them like jQuery.
		// Some headers, like "Things You'll Need", as well as headers in int'l
		// with non-Latin script, get assigned an element ID containing a dot,
		// as in "Things_You.27ll_Need".
		// Instead of selecting the header ID through pq(), iterate over
		// .mw-headline elements and check their ID "manually".
		$headlines = pq('.mw-headline');
		foreach ($headlines as $headline) {

			// we have a special change in Linker.php to switch the place of the headline and
			// the edit page link, but it is still required to be that way on desktop
			// so we just swap their places here
			$editLink = pq($headline)->prev('.edit-page');
			if ( pq($editLink)->length ) {
				pq($editLink)->insertAfter($headline);
			}

			$headlineID = pq($headline)->attr('id');

			switch ($headlineID) {
			case $ingredientsAnchor:
				$extraTOCPreData[] = [
					'anchor' => $ingredientsAnchor,
					'name' => wfMessage('ingredients')->text(),
					'priority' => 1000,
					'selector' => '#' . Misc::escapeJQuerySelector($ingredientsAnchor),
				];
				break;
			case $stepsAnchor:
				// The '#Steps' element appears on all articles, regardless of the
				// existence of a Steps section. Make sure the section actually exists.
				if (!pq($headline)->parent()->hasClass('hidden')) {
					$extraTOCPreData[] = [
						'anchor' => $stepsAnchor,
						'name' => wfMessage('steps')->text(),
						'priority' => 1500,
						'selector' => '#content>.section.steps',
					];
				}
				break;
			case $videoAnchor:
				if(!(WHVid::hasSummaryVideo($wgTitle) && !(WHVid::isYtSummaryArticle($wgTitle) && WHVid::hasYTVideo($wgTitle)))) {
					$extraTOCPostData[] = [
						'anchor' => $videoAnchor,
						'name' => wfMessage('video')->text(),
						'priority' => 1100,
						'selector' => '#' . Misc::escapeJQuerySelector($videoAnchor),
					];
				}
				break;
			case $tipsAnchor:
				$extraTOCPostData[] = [
					'anchor' => $tipsAnchor,
					'name' => wfMessage('tips')->text(),
					'priority' => 1200,
					'selector' => '#' . Misc::escapeJQuerySelector($tipsAnchor),
				];
				break;
			case $warningsAnchor:
				$extraTOCPostData[] = [
					'anchor' => $warningsAnchor,
					'name' => wfMessage('warnings')->text(),
					'priority' => 1300,
					'selector' => '#' . Misc::escapeJQuerySelector($warningsAnchor),
				];
				break;
			case $thingsyoullneedAnchor:
				$extraTOCPostData[] = [
					'anchor' => $thingsyoullneedAnchor,
					'name' => wfMessage('thingsyoullneed')->text(),
					'priority' => 1400,
					'selector' => '#' . Misc::escapeJQuerySelector($thingsyoullneedAnchor),
				];
				break;
			case $relatedAnchor:
				$extraTOCPostData[$relatedAnchor] = [
					'anchor' => $relatedAnchor,
					'name' => wfMessage('relatedarticles')->text(),
					'priority' => 1500,
					'selector' => '#' . Misc::escapeJQuerySelector($relatedAnchor),
				];
				break;
			case $otherWikihowsAnchor:
				$extraTOCPostData[] = [
					'anchor' => $otherWikihowsAnchor,
					'name' => 'Other wikiHows',
					'priority' => 1600,
					'selector' => '.section.otherwikihows',
				];
				break;
			default:
				break;
			}
		}

		Hooks::run('AddMobileTOCItemData', array($wgTitle, &$extraTOCPreData, &$extraTOCPostData));

		return [$extraTOCPreData, $extraTOCPostData];
	}

	/**
	 * @param array $altMethodAnchors
	 * @param array $altMethodNames
	 * @param array $isSample
	 * @param array $tocListMethodClass
	 *
	 * @return array list of strings with HTML <li> items containing anchor
	 *   elements of methods/parts for the table of contents.
	 */
	protected static function getTOCMethodAnchors($altMethodAnchors, $altMethodNames, $isSample, $tocListMethodClass) {
		$anchorList = [];
		$samplesList = [];
		$isSampleNormalized = [];

		if ($isSample && is_array($isSample)) {
			// $isSample can have offset indices. Make sure they're normalized.
			$isSampleNormalized = array_values($isSample);
		}

		for ($i = 0; $i < count($altMethodAnchors); $i++) {
			$methodName = pq('<div>' . $altMethodNames[$i] . '</div>')->text();

			// Remove any reference notes
			$methodName = preg_replace("@\[\d{1,3}\]$@", "", $methodName);

			if (!$methodName) {
				continue;
			}

			$anchorItem = [
				'method_class' => $tocListMethodClass,
				'anchor_link' => $altMethodAnchors[$i],
				'text' => $methodName
			];

			if ($isSampleNormalized && $isSampleNormalized[$i]) {
				$samplesList[] = $anchorItem;
			} else {
				$anchorList[] = $anchorItem;
			}
		}

		return array_merge($anchorList, $samplesList);
	}

	/**
	 * MediaWiki message keys of the "default" sections (besides methods/parts) to
	 * include with the TOC.
	 *
	 * Additional sections (for tools such as UCI and QA) are handled separately
	 * through the AddMobileTOCItemData hook.
	 *
	 * Note: When adding/removing items here, make sure to also:
	 *   - add/remove variables in the mapped list() assignment in getTOCExtras()
	 *   - add/remove data in the switch block in getTOCExtras()
	 *
	 * @return array
	 *
	 * @see WikihowMobileTools::getTOCExtras()
	 */
	protected static function getTOCSectionMessages() {
		return ['ingredients', 'steps', 'video', 'tips', 'warnings', 'thingsyoullneed', 'relatedwikihows'];
	}

	/**
	 * Transform provided array describing extra TOC elements into HTML tags.
	 *
	 * @param array &$extraTOCData
	 * @param array $defaultClassList
	 *
	 * @return array list of strings with HTML <li> items containing anchor
	 *   elements of "extra" sections (non-methods/parts) for the table of
	 *   contents.
	 */
	protected static function processTOCData(&$extraTOCData, $defaultClassList) {
		usort($extraTOCData, function ($a, $b) { return $a['priority'] > $b['priority']; });

		$extraAnchors = [];

		for ($i = 0; $i < count($extraTOCData); $i++) {
			$classList = $defaultClassList;

			if ($extraTOCData[$i]['classes'] && is_array($extraTOCData[$i]['classes'])) {
				$classList = array_merge($classList, $extraTOCData[$i]['classes']);
			}

			$classes = implode(' ', $classList);

			$extraAnchors[] = [
				'method_class' => $classes,
				'section' => $extraTOCData[$i]['selector'],
				'anchor_link' => $extraTOCData[$i]['anchor'],
				'text' => $extraTOCData[$i]['name']
			];
		}

		return $extraAnchors;
	}

	/**
	 * @param array $altMethodAnchors
	 * @param array $altMethodNames
	 * @param array $isSample
	 *
	 * @return array list of strings with HTML <li> items containing anchor
	 *   elements for the table of contents.
	 */
	public static function makeTableOfContentsAnchors($altMethodAnchors, $altMethodNames, $isSample) {
		global $wgLanguageCode;

		$tocListBaseClass = 'method_toc_item';
		$tocListMethodClass = $tocListBaseClass . ' toc_method';

		$anchorList = self::getTOCMethodAnchors(
			$altMethodAnchors, $altMethodNames, $isSample, $tocListMethodClass
		);

		list($extraTOCPreData, $extraTOCPostData) = self::getTOCExtras();

		$anchorList = array_merge(
			self::processTOCData(
				$extraTOCPreData,
				[$tocListBaseClass, 'toc_pre']
			),
			$anchorList,
			self::processTOCData(
				$extraTOCPostData,
				[$tocListBaseClass, 'toc_post']
			)
		);

		return $anchorList;
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
		$text = Html::element('span', [], $howToPrefix) . '&nbsp;' . htmlspecialchars($titleText) . $howToSuffix;
		$label = Html::rawElement( 'p', [], $text );

		$html = Html::rawElement('a',  ['class' => 'related_box', 'href' => $url], $mediaWrapper . $label );

		return $html;
	}

	public static function makeFeaturedArticlesBox($title, $forceProcessing=false, $showHighDPI=false) {
		global $wgLanguageCode;
		$nameTruncateLength = 32;
		$maxWordLength = $wgLanguageCode == 'th' ? 17 : 12;

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
		global $wgLanguageCode, $wgIsSecureSite;

		$protocol = $wgIsSecureSite ? 'https' : 'http';
		return $protocol . '://' . wfCanonicalDomain($wgLanguageCode);
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

	protected static function getTableOfContents() {
		return self::$tableOfContentsHtml;
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

	private static function formatReferencesSection( $skin ) {
		$sourcesSectionClass = ".".Misc::getSectionName( (wfMessage('sources')->text()));
		$sourcesSection = pq( $sourcesSectionClass );

		// if there is no sources section try references instead
		if ( pq( $sourcesSection )->length < 1 ) {
			$referencesSectionClass = ".section.".Misc::getSectionName( (wfMessage('references')->text()));
			$sourcesSection = pq( $referencesSectionClass );
		}

		pq( $sourcesSection )->find( '.section_text' )->prepend( '<ol class="firstref references">' );

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
		if ( pq( $sourcesSection )->find( 'li' )->length > 0 ) {
			$showMore = Html::element( 'a', ['id' => 'info_link', 'href' => '#aiinfo'], wfMessage('more_references')->text() );
			$sectionText = Html::rawElement( 'div', ['id'=>'articleinfo', 'class'=>'section_text'], $showMore );
			$articleInfoHtml = Html::rawElement( 'div', ['id' => 'aiinfo', 'class' => 'section articleinfo'], $sectionText );
			$referencesHtml .= $articleInfoHtml . $sourcesSection;
		}

		if ( pq( '#social_proof_mobile' )->length ) {
			pq( '#social_proof_mobile' )->after( $referencesHtml );
		} else {
			pq( '#article_rating_mobile' )->before( $referencesHtml );
		}
		// because we appended the new references section we need to remove this one
		pq( $sourcesSection )->remove();
	}

}
