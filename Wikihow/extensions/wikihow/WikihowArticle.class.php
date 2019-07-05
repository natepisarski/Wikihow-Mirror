<?php

use MethodHelpfulness\ArticleMethod;

class WikihowArticleHTML {

	static $methodCount = 1;
	static $hasParts = false;

	var $mBody = null;
	var $mOpts = array();
	var $mRelatedWikihows = null;
	var $mDesktopAds = null;

	// this is deprecated.
	// instead of using it, construct a new instance and call processBody instead
	public static function processArticleHTML($body, $opts = array()) {
		wfDeprecated( __METHOD__, '1.23' );
		$wikihowArticleHTML = new WikihowArticleHTML( $body, $opts );
		return $wikihowArticleHTML->processBody();
	}

	public function __construct( $body, $opts = array() ) {
		$this->mBody = $body;
		$this->mOpts = $opts;
	}

	public function getRelatedWikihows() {
		return $this->mRelatedWikihows;
	}

	public function getDesktopAds() {
		return $this->mDesktopAds;
	}

	public function processBody() {
		$body = $this->mBody;
		$opts = $this->mOpts;
		$ctx = RequestContext::getMain();
		$req = $ctx->getRequest();
		$out = $ctx->getOutput();
		$user = $ctx->getUser();
		$title = $ctx->getTitle();
		$langCode = $ctx->getLanguage()->getCode();

		// Trevor, 5/22 - Used later on to add structred data to inline summary videos, must be
		// called here due to mysterious issue with calling it later to be solved in the future
		$videoSchema = SchemaMarkup::getVideo( $title );

		$doc = phpQuery::newDocument($body);
		$context = RequestContext::getMain();


		Hooks::run('WikihowArticleBeforeProcessBody', array( $title ) );

		$featurestar = pq("div#featurestar");
		if ($featurestar) {
			$clearelement = pq($featurestar)->next();
			$clearelement->remove();
			$featurestar->remove();
		}

		$isMainPage = $title
			&& $title->inNamespace(NS_MAIN)
			&& $title->getText() == wfMessage('mainpage')->inContentLanguage()->text()
			&& $req->getVal('action', 'view') == 'view';

		$isNewTocArticle = WikihowToc::isNewArticle();
		$action = $req ? $req->getVal('action') : '';

		// Remove __TOC__ resulting html from all pages other than User pages
		if (@$opts['ns'] != NS_USER && pq('div#toc')->length) {
			$toc = pq('div#toc');
			//in upgrade, it's no longer preceded by an h2, so deleting the intro instead :(
			//maybe this will change so leaving in for now.
			//$toc->prev()->remove();
			$toc->remove();
		}

		$sticky = "";
		if (@$opts['sticky-headers']) {
			$sticky = " sticky ";
		}

		//move firstHeading to inside the intro
		$firstH2 = pq("h2:first");
		if (pq($firstH2)->length() == 0) {
			try {
				pq("#bodycontents")->children(":first")->wrapAll("<div class='section wh_block'></div>");
			} catch (Exception $e) {
			}
		}
		else {
			try {
				pq($firstH2)->prevAll()->reverse()->wrapAll("<div id='intro' class='section {$sticky}'></div>");
			} catch (Exception $e) {
			}
		}

		//remove top edit link
		pq("#intro .editsection")->remove();

		// The "whcdn" class is added to all <img> tags whose src contents
		// should pull from the whstatic.com (CDN) domain. We apply this change
		// as post-processing after the parser has done its thing so that
		// the results of wfGetPad aren't cached with the parser cache. This
		// is important because the function is context-dependent, and its
		// output should vary based on domain and HTTP/S status.
		foreach (pq('.whcdn') as $node) {
			$pqNode = pq($node);
			$pqNode->attr('src', wfGetPad( $pqNode->attr('src') ) );
		}

		//add a clearall to the end of the intro
		pq("#intro")->append("<div class='clearall'></div>");

		$showCurrentTitle = $langCode == "en"
			&& $title->inNamespace(NS_MAIN)
			&& !$title->isRedirect()
			&& $title->exists()
			&& PagePolicy::showCurrentTitle($context);

		$showUnnabbed = $showCurrentTitle && !NewArticleBoost::isNABbedNoDb($title->getArticleID());

		if ($showUnnabbed) {
			$intro = pq("#intro");
			if ($req->getVal('new',null) == '1' || $user->getOption('showdemoted') == '1') {
				//just show top bar and don't obfuscate
				if (pq($intro)->length) {
					pq("#intro")->before("<div class='unnabbed_alert_top' style='display:block'>" . wfMessage('nab_warning_top')->parse() . "</div>");
				} else {
					pq("#bodycontents .section")->before("<div class='unnabbed_alert_top' style='display:block'>" . wfMessage('nab_warning_top')->parse() . "</div>");
				}
			}
			else {
				//do full unnabbed work-up
				pq("#bodycontents")->addClass("unnabbed");
				$unnabbedAlert = "<div class='unnabbed_alert'><div><a href='#' id='nab_alert_close'>Ok,<br /> Close</a>" . wfMessage('nab_warning')->text() . " <a id='nab_learn' href='/Get-your-Article-Reviewed-or-Approved-on-wikiHow'>Learn more</a></div></div>";
				$unnabbedAlertTop = "<div class='unnabbed_alert_top'>" . wfMessage('nab_warning_top')->parse() . "</div>";
				if (pq($intro)->length) {
					pq("#intro")->append($unnabbedAlert);
					pq("#intro")->before($unnabbedAlertTop);
				} else {
					pq("#bodycontents .section")->append($unnabbedAlert);
					pq("#bodycontents .section")->before($unnabbedAlertTop);
				}
			}
		}

		//add the pimpedheader to our h3s!
		pq('h3')->prepend('<div class="altblock"></div>');


		// Contains elements with the raw titles of methods (i.e. non-parts)
		$nonAltMethodElements = array();

		$h2s = pq('h2');
		$h2Count = count($h2s);
		foreach ($h2s as $node) {
			$h2Parent = pq($node)->parent();
			if (@$opts['ns'] == NS_USER && pq($h2Parent)->attr("id") == "toctitle") {
				pq("#toc")->wrapAll("<div class='section'></div>");
				pq("#toc ul:first")->addClass("section_text");
				continue;
			}
			//find each section

			$sectionName = Misc::getSectionName(pq("span.mw-headline", $node)->html());

			//now find all the elements prior to the next h2
			$set = array();
			$h3Tags = array();
			$h3Elements = array();
			$priorToH3Set = array();
			$h3Count = 0;

			foreach (pq($node)->nextAll() as $sibling) {
				if (pq($sibling)->is("h2")) {
					break;
				}
				if (pq($sibling)->is("h3")) {
					$h3Count++;
					$h3Tags[$h3Count] = $sibling;
					$h3Elements[$h3Count] = array();
				} else {
					if ($h3Count > 0) {
						$h3Elements[$h3Count][] = $sibling;
					} else {
						$priorToH3Set[] = $sibling;
					}
				}
				$set[] = $sibling;
			}

			$canonicalSectionName = self::canonicalizeHTMLSectionName($sectionName);
			$canonicalSteps = self::canonicalizeHTMLSectionName(wfMessage('steps')->text());
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
						foreach (pq("div#{$sectionName}:first") as $temp) {
							$overallSet[] = $temp;
						}

						try {
							pq($overallSet)->wrapAll("<div class='section steps {$sticky}'></div>");
						} catch (Exception $e) {
						}
					} else {
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

					$hasParts = $opts['magic-word'] == MagicWord::get( 'parts' );
					self::$hasParts = $hasParts;

					$displayMethod = 1;
					for ($i = 1; $i <= $h3Count; $i++) {
						//change the method title
						$methodTitle = pq("span.mw-headline", $h3Tags[$i])->html();
						//[sc] ***INTERMEDIATE STEP (remove line below)
						$removeRet = WikihowArticleEditor::removeMethodNamePrefix( $methodTitle );
						$altMethodNames[] = $methodTitle;
						$altMethodAnchors[] = pq("span.mw-headline", $h3Tags[$i])->attr("id");
						//[sc] ***INTERMEDIATE STEP (swap if logic below)
						//if ($displayMethodCount > 1 && $hasParts && $opts['ns'] == NS_MAIN) {
						if ($displayMethodCount > 1 && !$isSample[$i] && ($removeRet['has_parts'] || $hasParts) && $opts['ns'] == NS_MAIN) {
							$methodPrefix = wfMessage("part")->text() . " <span>{$displayMethod}</span>";
							$displayMethod++;
						} elseif ($displayMethodCount > 1 && !$isSample[$i] && $opts['ns'] == NS_MAIN) {
							$nonAltMethodElements[] = pq("span.mw-headline", $h3Tags[$i])->clone();
							$labelTxt = wfMessage("method")->text();
							$methodPrefix =  "<label class='method_label'>{$labelTxt}</label> <span>{$displayMethod}</span>";
							$displayMethod++;
						}
						pq("span.mw-headline", $h3Tags[$i])->html($methodTitle);
						if (!$isSample[$i] && $opts['ns'] == NS_MAIN) {
							pq(".altblock", $h3Tags[$i])->html($methodPrefix);
						} else {
							pq(".altblock", $h3Tags[$i])->remove();
						}

						//add our custom anchors
						$anchor_name = pq("span.mw-headline", $h3Tags[$i])->attr('id').'_sub';
						try {
							pq($h3Tags[$i])->before('<a name="'.$anchor_name.'" class="anchor"></a>');
						} catch (Exception $e) {
						}

						//want to change the url for the edit link to
						//edit the whole steps section, not just the
						//alternate method
						pq(".editsection", $h3Tags[$i])->attr("href", $stepsEditUrl);

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
							pq($overallSet)->wrapAll("<div class='section steps {$sample} {$sticky}'></div>");
							pq('.section.steps:first')->addClass('steps_first');
						} catch (Exception $e) {
						}
					}

					//BEBETH - not sure we need this anymore, but not sure yet
					//fix for Chrome -- wrap first anchor name so it detects the spacing
					try {
						pq(".section.steps:first")->prev()->children(".anchor")->after('<br class="clearall" />')->wrapAll('<div></div>');
					} catch (Exception $e) {
					}

					//now we should have all the alt methods,
					//let's create the links to them under the headline
					self::$methodCount = count($altMethodAnchors);
					$anchorList = self::getAnchorList( $altMethodAnchors, $altMethodNames );

					if ($isNewTocArticle) {
						WikihowToc::setMethods($altMethodAnchors, $altMethodNames);
					} else {
						//chance to reformat the alt method_toc before output
						//using for running tests
						Hooks::run('BeforeOutputAltMethodTOC', array($wgTitle, &$anchorList));
						pq('.firstHeading')->after("<p id='method_toc' class='sp_method_toc'>{$anchorList}</p>");
					}
				}
				else {
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
						pq($overallSet)->wrapAll("<div class='section steps {$sticky}'></div>");
					} catch (Exception $e) {
					}
				}
			}
			else {
				//not a steps section

				if ( strpos( $sectionName, 'ataglance' ) !== FALSE ) {
					$sectionName = 'ataglance';
				}

				if ($set) {
					$sec_id = (@$opts['list-page']) ? '' : 'id="'.$sectionName.'"';
					try {
						$new_set = pq($set)->wrapAll("<div {$sec_id} class='section_text'></div>");
					} catch (Exception $e) {
					}
				}

				$overallSet = array();
				$overallSet[] = $node;
				foreach (pq("div#{$sectionName}:first") as $temp) {
					$overallSet[] = $temp;
				}
				try {
					pq($overallSet)->wrapAll("<div class='section {$sectionName} {$sticky}'></div>");
				} catch (Exception $e) {
				}

				if (@$opts['list-page']) {
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
				try {
					// Reuben note: I made this removal only apply if there are fewer than 30 h2
					// nodes in the DOM, since the search was very expensive on large articles
					// if it is unrestricted
					//
					// Note, this is an example article where this rule removes an "edit" link:
					// http://www.wikihow.com/Make-Low-Carb-Biscuits
					if ($h2Count <= 30) {
						pq(".{$sectionName} h3 .editsection")->remove();
					}
				} catch(Exception $e) {
				}
			}
		}


		//add a clear to the end of each section_text to make sure
		//images don't bleed across the bottom
		pq(".section_text")->append("<div class='clearall'></div>");

		Hooks::run('AtAGlanceTest', array( $title ) );

		// Add checkboxes to Ingredients and 'Things You Need' sections, but only to the top-most li
		$lis = pq('#ingredients > ul > li, #thingsyoullneed > ul > li');
		foreach ($lis as $li) {
			$id = md5(pq($li)->html() . mt_rand(1, 100));
			pq($li)->html("<input id='item_$id' class='css-checkbox' type='checkbox'/><label for='item_$id' name='item_{$id}_lbl' class='css-checkbox-label'></label><div class='checkbox-text'>" . pq($li)->html() . '</div>');
		}
		// Move templates above article body contents and style appropriately
		foreach (pq('.template_top') as $template) {
			pq($template)->addClass('sidebox');
			if (pq($template)->parent()->hasClass('tmp_li')) {
				pq($template)->addClass('tmp_li');
			}
			if ($user->isAnon()) {
				pq($template)->addClass('notice_bgcolor_lo');
			} else {
				pq($template)->addClass('notice_bgcolor_important');
			}

		}

		if ( !pq( '.template_top' )->find('#intro')->length ) {
			pq('.template_top')->insertAfter('#intro');
		}


		if (class_exists("StepEditorParser")) {
			$stepEditorHelper = new StepEditorParser($title, 0, $out);
			if ($stepEditorHelper->hasAnyEditableSteps()) {
				$out->addModules('ext.wikihow.stepeditor');
				pq(".steps:last")->after($stepEditorHelper->getParsingMessage());
			}
		}

		//now put the step numbers in
		$absoluteStepNum = 1;
		$methodNum = 1;
		foreach (pq("div.steps .section_text > ol") as $list) {
			pq($list)->addClass("steps_list_2");
			$stepNum = 1;
			foreach (pq($list)->children() as $step) {
				$boldStep = self::boldFirstSentence(pq($step)->html());
				pq($step)->html($boldStep);
				pq($step)->prepend("<a name='" . wfMessage('step_anchor', $methodNum, $stepNum) . "' class='stepanchor'></a>");
				pq($step)->prepend('<div class="step_num" aria-label="' . wfMessage('aria_step_n', $stepNum)->showIfExists() . '">' . $stepNum . '</div>');
				pq($step)->append("<div class='clearall'></div>");
				if (class_exists("StepEditorParser") && $stepEditorHelper->isEditable($absoluteStepNum) ) {
					pq($step)->addClass("stepedit");
					pq($step)->prepend("<a href='#'  class='stepeditlink' style='display:none;'>Edit step</a>");
					pq($step)->prepend("<span class='absolute_stepnum' style='display:none'>{$absoluteStepNum}</span>");
				}
				if (pq(".largeimage", $step)->length > 0) {
					pq($step)->addClass("hasimage");
				}
				$stepNum++;
				$absoluteStepNum++;
			}
			$methodNum++;
		}

		foreach (pq(".steps:last .steps_list_2")->children(":last-child") as $step) {
			pq($step)->addClass("final_li");
		}

		// if we find videos, then replace the images with videos
		$videoCount = 0;
		foreach ( pq( '.m-video' ) as $node ) {
			$mVideo = pq( $node );
			$videoCount++;

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

			// find the mwimg so we can move the video into it
			// as it is a wrapper to our img and video elements
			$mwimg = $mVideo->nextAll( ".mwimg:first" );

			// move the js snippet into the mwimg
			$mVideo->next( 'script' )->insertAfter( $mwimg );

			$mwimg->find( ".image" )->before( $mVideo )->remove();

			$mVideo->wrap( '<div class="video-container">' );
			$videoContainer = $mVideo->parent();
			$videoContainer->wrap( '<div class="video-player">' );
			$videoPlayer = $videoContainer->parent();
			if ( Misc::isIntl() ) {
				$videoPlayer->addClass( 'intl' );
			}
			$videoPlayer->wrap('<div class="content-spacer" style="padding-top: 56.25%;">');
			$videoContainer->addClass('content-fill');
			$mVideo->addClass('content-fill');

			if ( $mVideo->attr( 'data-watermark' ) ) {
				$videoContainer->after( WHVid::getVideoWatermarkHtml( $context->getTitle() ) );
			}
			if ( $mVideo->attr( 'data-summary' ) ) {
				if ($user && in_array('staff', $user->getGroups() ) ) {
					$mVideo->attr( 'oncontextmenu', '' );
				}
				// this is where we put the ad container to enable video ads which are not active
				// for this type of video at the moment
				if ( $title && $title->getArticleID() == 2723473 ) {
					$videoContainer->after( '<div class="video-ad-container"></div>' );
					$mVideo->attr( 'data-ad-type', 'linear' );
				}
			} elseif ( $videoCount < 2 ) {
				if ( $title && $title->getArticleID() == 1630 ) {
					$videoContainer->after( '<div class="video-ad-container"></div>' );
					$mVideo->attr( 'data-ad-type', 'nonlinear' );
				}
			}

			if ( $mVideo->attr( 'data-controls' ) && !$mVideo->attr( 'data-summary' )) {
				$videoContainer->after( WHVid::getVideoControlsHtml() );
			}
		}

		$summary_at_top = true;
		if ( pq('#summary_position')->length ) {
			$summary_at_top = pq('#summary_position')->hasClass(SummarySection::SUMMARY_POSITION_TOP_CLASS);
		}

		$headings = explode("\n", ConfigStorage::dbGetConfig(Wikitext::SUMMARIZED_HEADINGS_KEY));
		if ( !$headings ) {
			$headings = array();
		}

		foreach ( $headings as $heading ) {
			$canonicalSummaryName = self::canonicalizeHTMLSectionName( Misc::getSectionName( $heading ) );
			$headingId = '#' . $canonicalSummaryName;
			if ( pq( $headingId )->length ) {
				$headingText = $heading;
				// add helpful feedback section

				if ( pq( $headingId . ' .text_summary_wrapper' )->length != 0 ) {
					$html = RateItem::getSummarySectionRatingHtml( $summary_at_top );
					pq( $headingId )->append( $html );
				}
				//give the whole section a consistent id
				pq('.'.$canonicalSummaryName)->attr('id','quick_summary_section');
				//wrap the text part in a div
				$textSummary = pq( $headingId )->find(".text_summary_wrapper");

				if ($textSummary->length > 0) {
					// if there is a mwimg, then make sure to put the text summary after the script tag which follows it
					// or else the wrapping will not work correctly
					if ( pq('#quick_summary_section')->find('.mwimg')->prev()->length ) {
						$textSummary->insertBefore( pq('#quick_summary_section')->find('.s-help-wrap') );
					}


					$textSummary->add($textSummary->siblings('.s-help-wrap'))->wrapAll("<div id='summary_wrapper'><div id='summary_text'></div></div>");

					pq("#summary_text")->prepend("<h2>" . wfMessage('summary_toc')->text() . "<a href='#' id='summary_close'>X</a></h2>");

					//if there's no video, hide the section
					if ( pq( $headingId . ' video' )->length == 0 ) {
						pq("#summary_wrapper")->insertBefore(pq("#quick_summary_section"));
						pq("#quick_summary_section")->remove();
					} else {
						//if there is, move this outside of that section so we can use the same css for both cases
						pq("#summary_wrapper")->appendTo("#quick_summary_section");
					}

					if ($isNewTocArticle) {
						//tell the TOC there's a summary
						WikihowToc::setSummary();
					} else {
						//if there's no TOC, make one now
						if (pq("#method_toc")->length <= 0) {
							$specialAnchorArray = [Html::element( 'span', [], wfMessage('toc_title') )];
							Hooks::run('AddDesktopTOCItems', array( RequestContext::getMain()->getTitle(), &$specialAnchorArray ) );
							$specialAnchorList = implode( "" , $specialAnchorArray );
							pq('.firstHeading')->after("<p id='method_toc' class='sp_method_toc'>{$specialAnchorList}</p>");
						}

						SummarySection::addDesktopTOCItems();
					}
				}
			}
			$headingImages = pq( $headingId . ' .mwimg' )->addClass( 'summarysection' );
			foreach ( $headingImages as $headingImage ) {
				$headingImage = pq($headingImage)->remove();
				if ( $headingImage ) {
					pq( $headingId )->prepend( pq($headingImage));
				}
			}
			pq( $headingId . ' .m-video-wm' )->remove();
		}

		if (pq("#method_toc")->length <= 0) {
			pq('.firstHeading')->addClass('no_toc');
		}

		// if there is a summary video but no text
		SummarySection::addIntlDesktopVideoTOCItem();

		// add the controls
		$summaryHtml = WHVid::getVideoControlsSummaryHtml( $headingText );
		$summaryHelpfulHtml = WHVid::getDesktopVideoHelpfulness();
		$replayHtml = WHVid::getVideoReplayHtml();
		pq( '.summarysection .video-container' )->after( $summaryHtml . $summaryHelpfulHtml . $replayHtml );

		if ( pq('#quick_summary_section video')->length > 0) {
			$titleText = wfMessage('howto', $title->getText())->text();
			if (strlen($titleText) > 49) {
				$titleText = mb_substr($titleText, 0, 46) . '...';
			}
			pq("#quick_summary_section h2 span")->html(wfMessage('qs_video_title')->text() . ": " . $titleText);
			pq( "#quick_summary_section")->addClass("summary_with_video");

			// Structured data
			if ( $videoSchema ) {
				pq( '#quick_summary_section .video-player' )->append( SchemaMarkup::getSchemaTag( $videoSchema ) );
			}

			if ( Misc::isIntl() ) {
				pq( "#quick_summary_section")->addClass("intl");
			}
			WikihowToc::setSummaryVideo();
		}

		//check the yt vidoes
		if( pq('.embedvideocontainer')->length > 0 && WHVid::isYtSummaryArticle($title)) {
			wikihowToc::setSummaryVideo(true);
			if(pq('.summary_with_video')->length) {
				pq('.summary_with_video')->replaceWith(pq('#summary_wrapper'));
			}
			// Add schema to all YouTube videos that are from our channel
			foreach ( pq( '.embedvideo' ) as $video ) {
				$src = pq( $video )->attr( 'data-src' );
				preg_match( '/youtube\.com\/embed\/([A-Za-z0-9_-]+)/', $src, $matches );
				if ( $matches[1] ) {
					$videoSchema = SchemaMarkup::getYouTubeVideo( $title, $matches[1] );
					// Only videos from our own channel will have publisher information
					if ( array_key_exists( 'publisher', $videoSchema ) ) {
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

		//move each of the large images to the top
		foreach (pq(".steps_list_2 li .mwimg.largeimage") as $image) {
			//delete any previous <br>
			foreach (pq($image)->prevAll() as $node) {
				if ( pq($node)->is("br") ) {
					pq($node)->remove();
				} else {
					break;
				}
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
		if ( $title->inNamespace(NS_PROJECT) ) {
			foreach ( pq( '.section_text ul' ) as $node ) {
				foreach ( pq( $node )->find( 'li:first' ) as $firstItem ) {
					if ( pq( $firstItem )->find( '.mwimg' )->length ) {
						pq( $firstItem )->addClass( 'hasimg' );
					}
				}
			}
		}


		//Made Recently section
		if (class_exists('UserCompletedImages') && $showCurrentTitle) UserCompletedImages::addDesktopSection($context);

		//remove all images in the intro that aren't
		//marked with the class "introimage"
		pq("#intro .mwimg:not(.introimage)")->remove();


		//let's mark all the <p> tags that aren't inside a step.
		//they need special padding
		foreach (pq(".section.steps p") as $p) {
			if (pq($p)->parents(".steps_list_2")->count() == 0 && pq($p)->children(".anchor")->count() == 0) {
				pq($p)->addClass("lone_p");
				$content = strtolower(pq($p)->html());
				if ($content == "<br>" || $content == "<br />") {
					pq($p)->remove();
				}

			}
		}


		//add line breaks between the p tags
		foreach (pq("p") as $paragraph) {
			$sibling = pq($paragraph)->next();
			if (!pq($sibling)->is("p"))
				continue;
			if (pq($sibling)->children(":first")->hasClass("anchor"))
				continue;
			$id = pq($paragraph)->attr("id");
			if ($id == "method_toc")
				continue;

			pq($paragraph)->after("<br />");
		}

		// remove video section if it has no iframe (which means it has no video)
		if ( pq("#video")->find('iframe')->length < 1 ) {
			pq(".video")->remove();
		}
		// remove the empty <p> after the video
		if ( pq("#video")->children('p:first')->text() == "" ) {
			pq("#video")->children('p:first')->remove();
		}
		// remove the empty table inside video section if it's empty
		if ( trim( pq( "#video" )->find( 'table:first' )->text() ) == "" ) {
			pq( "#video" )->find( 'table:first' )->remove();
		}

		foreach (pq(".embedvideo_gdpr:first") as $node) {
			pq( $node )->parents( '.section:first' )->find( '.mw-headline:first' )->after( pq( $node )->html() );
		}
		pq( ".embedvideo_gdpr" )->remove();
		// Truncate the list of sources/citations and show a link to expand it
		$sourcestext = wfMessage("sources")->text();
		$sourcesId = str_replace( [' ','(',')'], "", mb_strtolower($sourcestext));
		$sources = pq("#{$sourcesId}");

		// if there is no sources section try references instead
		if ( pq( $sources )->length < 1 ) {
			$referencesText = wfMessage( 'references' )->text();
			$referencesId = str_replace( [' ','(',')'], "", mb_strtolower( $referencesText ) );
			$sources = pq("#{$referencesId}");
		}

		if ( pq( $sources )->find( '.references' )->length ) {
			$extraItems = pq( $sources )->find( 'ul' )->remove()->html();
			pq( $sources )->find( '.references' )->append( $extraItems );
		}

		// rename the sources and citations section
		if ($user->isAnon()) {
			pq($sources)->prev()->find( '.mw-headline' )->text( wfMessage("references")->text() );
		}

		self::formatSourcesSection( $sources, $context );

		DOMUtil::hideLinksInArticle();


		if (class_exists('ArticleQuizzes')) {
			$articleQuizzes = new ArticleQuizzes($title->getArticleID());
			$count = 1;
			foreach (pq(".steps h3") as $headline) {
				$methodType = pq(".altblock", $headline)->text();
				$methodTitle = pq(".mw-headline", $headline)->html();
				$quiz = $articleQuizzes->getQuiz($methodTitle, $methodType);
				if ($count == 1 && $articleQuizzes->showFirstAtTop()) {
					pq("#intro")->after($quiz);
				} else {
					pq($headline)->parent()->append($quiz);
					if ($articleQuizzes->showFirstAtTop()) { //this is temporary while we test
						pq($headline)->parent()->find(".qz_top_info")->remove();
					}
				}
				$count++;
			}
		}

		$this->mDesktopAds = new DesktopAds( $context, $user, $langCode, $opts, $isMainPage );
		$this->mDesktopAds->addToBody();

		$relatedsName = RelatedWikihows::getSectionName();
		$this->mRelatedWikihows = new RelatedWikihows( $context, $user, pq( ".section.".$relatedsName ) );
		$this->mRelatedWikihows->setAdHtml( $this->mDesktopAds->getRelatedAdHtml() );
		$this->mRelatedWikihows->addRelatedWikihowsSection();

		$markPatrolledLink = self::getMarkPatrolledLink();
		if ($markPatrolledLink) {
			pq('#bodycontents')->append( $markPatrolledLink );
		}

		// Questions and Answers feature
		if (class_exists('QAWidget') && QAWidget::isTargetPage()) {
			$widget = new QAWidget();
			$widget->addWidget();
		}

		//special querystring for loading pages faster by removing step images
		//STAFF ONLY
		if ($user && in_array('staff', $user->getGroups()) && $req && $req->getVal('display_images') == 'false') {
			pq(".steps_list_2 li .mwimg")->remove();
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

		Hooks::run('ProcessArticleHTMLAfter', array( $out ) );


		UserTiming::modifyDOM($canonicalSteps);
		PinterestMod::modifyDOM();
		DeferImages::modifyDOM();
		Lightbox::modifyDOM($title->getArticleID());
		ImageCaption::modifyDOM();
		if (class_exists('Donate')) {
			Donate::addDonateSectionToArticle();
		}

		if ($isNewTocArticle) {
			WikihowToc::addToc();
		}

		//english only test
		if ($langCode == "en" && ArticleTagList::hasTag("test_bold_1", $title->getArticleID())) {
			$titleText = pq(".firstHeading a")->html();
			pq(".firstHeading a")->html("<span style='font-weight: normal'>How to </span>" . substr($titleText, 7));
		}
		if ($langCode == "en" && ArticleTagList::hasTag("test_bold_2", $title->getArticleID())) {
			$firstParagraph = pq("#intro > p:not('#method_toc'):first");
			$text = $firstParagraph->html();
			$words = explode(" ", $text);
			if (count($words) > 5) {
				$start = array_slice($words, 0, 5);
				$end = array_slice($words, 5);
				$firstParagraph->html("<span style='font-weight: bold'>" . join(" ", $start) . "</span> " . join(" ", $end));
			}
		}

		// do not update video path if we are viewing an old revision
		if ( !$context->getRequest()->getVal( 'oldid' ) ) {
			// get the last video name and add it to article meta info
			$ami = ArticleMetaInfo::getAMICache();
			$lastVideo = '';
			$summaryVideo = '';
			// look through all videos for summary section

			foreach ( pq( '.m-video' ) as $mVideo ) {
				if ( pq( $mVideo )->attr( 'data-summary' ) ) {
					$summaryVideo = pq( $mVideo )->attr( 'data-src' );
				} else {
					$lastVideo = pq( $mVideo )->attr('data-src');
				}
			}

			$ami->updateLastVideoPath( $lastVideo );
			$ami->updateSummaryVideoPath( $summaryVideo );
		}

		// Trevor, 10/29/18 - Testing making videos a link to the video browser - this must come
		// after videos are updated
		// Trevor, 3/1/19 - Check article being on alt-domain, not just which domain we are on, logged in
		// users can see alt-domain articles on the main site
		// Trevor, 6/18/19 - Make a special exception for recipe articles, play those inline
		$recipeSchema = SchemaMarkup::getRecipeSchema( $title, $context->getOutput()->getRevisionId() );
		if ( !$recipeSchema && $langCode == 'en' && !AlternateDomain::getAlternateDomainForPage( $title->getArticleID() ) ) {
			$videoPlayer = pq( '#quick_summary_section .video-player' );
			if ( $videoPlayer ) {
				$link = pq( '<a id="summary_video_link">' )->attr(
					'href', '/Video/' . str_replace( ' ', '-', $context->getTitle()->getText() )
				);
				$poster = pq( '<img id="summary_video_poster">' )->attr( 'data-src', $videoPlayer->find( 'video' )->attr( 'data-poster' ) );
				$poster->addClass( 'm-video' );
				$poster->addClass( 'content-fill placeholder' );
				$controls = pq( WHVid::getSummaryIntroOverlayHtml( '', $title ) );
				// Includes the structured data, which was appened to .video-player
				$videoPlayer->empty()->append( $link );
				$link->append( $poster );
				$link->append( Html::inlineScript( "WH.shared.addScrollLoadItem('summary_video_poster')" ) );
				$link->append( Html::inlineScript( "WH.shared.addLoadedCallback('summary_video_poster', function(){WH.shared.showVideoPlay(this);})" ) );
				$link->append( $controls );
			}
		}

		//tabs should really be last so that it has access to all the content that might be there
		if ( class_exists( 'DesktopTabs' ) ) {
			DesktopTabs::modifyDOM();
		}

		if ( class_exists( 'AlternateDomain' ) ) {
			AlternateDomain::modifyDom();
		}

		if (class_exists( 'TechLayout' ) && TechLayout::isTechLayoutTest($title)) {
			TechLayout::modifyDom();
		}

		if (class_exists('MethodHelpfulness\ArticleMethod')) {
			MethodHelpfulness\ArticleMethod::modifyDOM($nonAltMethodElements, '#bodycontents', true);
		}

		$scripts = [];
		$snippets = [];
		Hooks::run( 'AddTopEmbedJavascript', [&$scripts] );
		$html = $doc->htmlOuter();
		if ($scripts) {
			$html = Html::inlineScript(Misc::getEmbedFiles('js', $scripts)) . $html;
		}


		return $html;
	}

	// NOTE: used in StepEditor and WikihowMobileTools too
	public static function boldFirstSentence($htmlText) {
		$langCode = RequestContext::getMain()->getLanguage()->getCode();

		if (in_array($langCode, ['ja', 'zh'])) {
			$punct = '。\:!\?'; // Use the Japanese and Chinese period character instead
			if ($langCode == 'ja') {
				$punct .= '　'; // Double-byte space
			}
			$replacement = '$1</b>';
			$pattern = "@([$punct])@imu";
		} else {
			$punct = '\.\:!\?'; // valid ways of ending a sentence for bolding
			$replacement = '$1</b>$2';
			// ($|\s|\W|\D) = end of line, a space, or any non-word, non-number (skip decimals and urls)
			$pattern = "@([$punct])($|\s|\W|\D])@im";
		}
		// Array of characters that are used to end sentences (language-specific)
		$punct_split = str_split( str_replace( "\\", '', $punct ) );
		$htmlparts = preg_split('@(<[^>]*>)@im', $htmlText,
			0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		$intag = false;
		$apply_b = false;
		$closed_b = false;
		$endsWithSpecial = false;
		$endOfLink = false;
		$p = '';

		while ($x = array_shift($htmlparts)) {
			// Hack-ish fix for when the sentence ends with a hyperlink that contains a punctuation
			if( $endsWithSpecial == true && $endOfLink == true ) {
				$x = '</b>' . $x;
				$p .= $x;
				continue;
			}
			if ( in_array( substr( trim( $x ), -1 ), $punct_split ) ) {
				$endsWithSpecial = true;
			}
			if ( $x == '</a>' ) {
				$endOfLink = true;
			} else {
				$endOfLink = false;
			}

			# add any other "line-break" tags here.
			$is_break_tag = strpos($x, '<ul>') === 0 || strpos($x, '<ol>') === 0;

			# check if it is "in" a tag. If yes, just append it and continue until it is "out" of the tag
			if (!$is_break_tag && strpos($x, '<') === 0) {
				# add the tag
				$p .= $x;

				# Captions are turned off on the site as on 12th June 2019. However, if these are enabled later,
				# do not boldface caption text. Continue to next "htmlparts" - Gaurang
				if( $x == '<span class="caption">' ){
					$intag = true;
					continue;
				}

				# Check if it is in a hyperlink, math template, or keyboard template. If yes, boldface the template text
				if ( $x == '<span style="white-space: nowrap;">' || strpos($x, '<math') === 0 || strpos($x, '<a') === 0 ) {
					$p .= '<b class="whb">';
					$intag = true;
				} elseif ( ($x == "</span>" || $x == "</math>" || $x == "</a>") && $intag ) {
					# Stop boldface if at the end of the template. Boldfacing of out-of-template text is handled
					# seperately below (line 993)
					$x = '</b>' . $x;
					$intag = false;
				}
				continue;
			}
			# append and keep going if it's a colon in a link
			if (strpos($x, '://') !== false) {
				   $p .= $x;
				   continue;
			}


			# put the closing </b> in if we hit the end of the sentence
			if (!$intag) {
				if (!$apply_b && trim($x)) {
					$p .= '<b class="whb">';
					$apply_b = true;
				}
				if ($apply_b) {
					$x = preg_replace($pattern, $replacement, $x, 1, $closeCount);
					$closed_b = $closeCount > 0;
				}
			}
			if (!$closed_b && $is_break_tag) {
				$x = '</b>' . $x;
				$closed_b = true;
			}

			$p .= $x;

			if ($closed_b) {
				break;
			}
		}

		# get anything left over
		$p .= implode('', $htmlparts);
		return "<div class='step'>". $p . "</div>";
	}

	/**
	 * Convert section name to lowercase and remove whitespace.
	 */
	public static function canonicalizeHTMLSectionName($sectionName) {
		return preg_replace('@\s*@', '', mb_strtolower($sectionName));
	}

	/**
	 * This method is used to process non-article HTML
	 */
	static function processHTML($body, $action='', $opts = array()) {
		$ctx = RequestContext::getMain();
		$user = $ctx->getUser();
		$title = $ctx->getTitle();

		$processHTML = true;
		// $title isn't used in the hook below
		Hooks::run('PreWikihowProcessHTML', array($title, &$processHTML));
		if (!$processHTML) {
			return $body;
		}

		$doc = phpQuery::newDocument($body);

		//run ShowGrayContainer hook for this
		if (@$opts['show-gray-container']) pq("#bodycontents")->addClass("minor_section");

		//let's mark each bodycontents section so we can target it with CSS
		if ($action) pq("#bodycontents")->addClass("bc_".$action);

		//default each mw-htmlform-submit button to a primary button
		//gotta clear too because we're floating it now
		pq(".mw-htmlform-submit")->addClass("primary button buttonright");
		pq(".mw-htmlform-submit")->after("<div class='clearall'></div>");

		//adds CSS to Cancel Upload and Upload Ignore Warning buttons,removes them from DOM, and adds them above clearall div`
		$pqButtons = pq("[name='wpCancelUpload'], [name='wpUploadIgnoreWarning]")->addClass("button buttonright");
		$pqButtons->remove();
		$html = (string)$pqButtons;
		pq(".mw-htmlform-submit")->after($html);

		//USER PREFERENCES//////////////////////
		//pq("#mw-prefsection-echo")->append(pq("#mw-prefsection-echo-emailsettingsind"));

		//DISCUSSION/USER TALK//////////////////////

		//move some pieces above the main part
		pq("#bodycontents")->before(pq(".template_top")->addClass("wh_block"));
		pq("#bodycontents")->before(pq(".archive_table")->addClass("wh_block"));

		//remove those useless paragraph line breaks
		$bc = preg_replace('/<p><br><\/p>/','',pq("#bodycontents")->html());
		pq("#bodycontents")->html($bc);

		//insert postcomment form
		$pc = new PostComment();
		$pcf = $pc->getForm(false, $title, true);
		if ($pcf && $title->getFullURL() != $user->getUserPage()->getTalkPage()->getFullURL()) {
			$pc_form = $pcf;
			pq("#bodycontents")->append($pc_form);
		}
		else {
			$pc_form = '<a name="postcomment"></a><a name="post"></a>';
			pq(".de:last")->prepend($pc_form);
		}

		//HISTORY//////////////////////
		//move top nav down a smidge
		pq("#history_form")->before(pq(".navigation:first"));


		//EDIT PREVIEW//////////////////////
		if (substr($action,0,6) == 'submit') {
			$name = ($action == 'submit2') ? "#editpage" : "#editform";

			$preview = pq("#wikiPreview");
			$changes = pq("#wikiDiff")->addClass("wh_block");
			pq("#wikiPreview")->remove();
			pq("#wikiDiff")->remove();

			//preview before or after based on user preference
			if ($user->getOption('previewontop')) {
				pq($name)->before($preview);
				pq($name)->before($changes);
			}
			else {
				pq($name)->after($preview);
				pq($name)->after($changes);
			}
		}

		$markPatrolledLink = self::getMarkPatrolledLink();
		if ($markPatrolledLink) {
			pq('#bodycontents')->append( $markPatrolledLink );
		}

		return $doc->htmlOuter();
	}

	static function getMarkPatrolledLink() {
		$ctx = RequestContext::getMain();

		// Append a [Mark as Patrolled] link in certain cases
		$user = $ctx->getUser();
		$markPatrolledLink = '';
		$rcid = $ctx->getRequest()->getInt('rcid');
		$fromRC = $ctx->getRequest()->getInt('fromrc');
		if ( $user && $rcid > 0 && $fromRC && $user->isAllowed( 'patrol' ) ) {
			$rc = RecentChange::newFromId($rcid);
			if ($rc) {
				$oldRevId = $rc->getAttribute('rc_last_oldid');
				$newRevId = $rc->getAttribute('rc_this_oldid');
				$diff = new DifferenceEngine(null, $oldRevId, $newRevId);
				if ( $diff->loadRevisionData() ) {
					$markPatrolledLink = $diff->markPatrolledLink();
				} else {
					throw new MWException("wikiHow internal error: we know there is an rcid ($rcid) and newrevid ($newRevId), but couldn't find the revision");
				}
			}
		}

		return $markPatrolledLink;
	}

	/**
	 * Insert ad codes, and other random bits of html, into the body of the article
	 */
	static function postProcess($body, $opts = array()) {
		global $wgWikiHowSections;
		$ctx = RequestContext::getMain();
		$ads = $ctx->getUser()->isAnon() && !@$opts['no-ads'];
		$parts = preg_split("@(<h2.*</h2>)@im", $body, 0, PREG_SPLIT_DELIM_CAPTURE);
		$reverse_msgs = array();
		$no_third_ad = false;
		foreach ($wgWikiHowSections as $section) {
			$reverse_msgs[wfMessage($section)->text()] = $section;
		}
		$charcount = strlen($body);
		$body = "";
		for ($i = 0; $i < sizeof($parts); $i++) {
			if ($i == 0) {

				if ($body == "") {
					// if there is no alt tag for the intro image, so it to be the title of the page
					preg_match("@<img.*mwimage101[^>]*>@", $parts[$i], $matches);
					$title = $ctx->getTitle();
					if ($title && sizeof($matches) > 0) {
						$m = $matches[0];
						$newm = str_replace('alt=""', 'alt="' . htmlspecialchars($title->getText()) . '"', $m);
						if ($m != $newm) {
							$parts[$i] = str_replace($m, $newm, $parts[$i]);
						}

						$parts[$i] = preg_replace('/mwimage101"/','mwimage101" itemprop="image"',$parts[$i], 1);
						$img_itemprop_done = true;
					} else {
						$img_itemprop_done = false;
					}

					$parts[$i] = preg_replace('/\<p\>/','<p itemprop="description">',$parts[$i], 1);

					// done alt test
					$anchorPos = stripos($parts[$i], "<a name=");
					if ($anchorPos > 0 && $ads){
						$content = substr($parts[$i], 0, $anchorPos);
						$count = preg_match_all('@</p>@', $parts[$i], $matches);

						if ($count == 1) { // this intro only has one paragraph tag
							$class = 'low';
						} else {
							$endVar = "<p><br /></p>\n<p>";
							$end = substr($content, -1*strlen($endVar));

							if ($end == $endVar) {
								$class = 'high'; //this intro has two paragraphs at the end, move ads higher
							}
							else{
								$class = 'mid'; //this intro has no extra paragraphs at the end.
							}
						}


						if (stripos($parts[$i], "mwimg") != false) {
							$body = "<div class='article_inner editable'>" . $content . "<div class='ad_image " . $class . "'>" . wikihowAds::getAdUnitPlaceholder('intro') . "</div>" . substr($parts[$i], $anchorPos) ."</div>\n";
						} else {
							$body = "<div class='article_inner editable'>" . $content . "<div class='ad_noimage " . $class . "'>" . wikihowAds::getAdUnitPlaceholder('intro') . "</div>" . substr($parts[$i], $anchorPos) ."</div>\n";
						}
					} elseif ($anchorPos == 0 && $ads) {
						$body = "<div class='article_inner editable'>{$parts[$i]}" . wikihowAds::getAdUnitPlaceholder('intro') . "</div>\n";
					}
					else
						$body = "<div class='article_inner editable'>{$parts[$i]}</div>\n";
				}
				continue;
			}

			if (stripos($parts[$i], "<h2") === 0 && $i < sizeof($parts) - 1) {
				// MWUP aaron changed this from <span> to <span because we now no longer wrap
				// the section name in a normal span, but a span with class and id
				preg_match("@<span.*</span>@", $parts[$i], $matches);
				$rev = "";
				if (sizeof($matches) > 0) {
					$h2 =  trim(strip_tags($matches[0]));
					$rev = isset($reverse_msgs[$h2]) ? $reverse_msgs[$h2] : "";
				}

				$body .= $parts[$i];

				$i++;
				if ($rev == "steps") {
					$body .= "\n<div id=\"steps\" class='editable'>{$parts[$i]}</div>\n";
				} elseif ($rev != "") {
					$body .= "\n<div id=\"{$rev}\" class='article_inner editable'>{$parts[$i]}</div>\n";
				} else {
					$body .= "\n<div class='article_inner editable'>{$parts[$i]}</div>\n";
				}
			} else {
				$body .= $parts[$i];
			}
		}

		$punct = "!\.\?\:"; # valid ways of ending a sentence for bolding
		$i = strpos($body, '<div id="steps"');
		if ($i !== false) {
			$j = strpos($body, '<div id=', $i+5); //find the position of the next div. Starting after the '<div ' (5 characters)
			$sub = "sd_"; //want to skip over the samples section if they're there
			while ($j !== false && $sub == "sd_") {
				$sub = substr($body, $j+9, 3); //find the id of the next div section 9=strlen(<div id="), 3=strlen(sd_)
				$j = strpos($body, '<div id=', $j+12); //find the position of the next div. Starting after the '<div id="sd_' (12 characters)
			}
		}
		if ($j === false) $j = strlen($body);
		if ($j !== false && $i !== false) {
			$steps = substr($body, $i, $j - $i);
			$parts = preg_split("@(<[/]?ul>|<[/]?ol>|<[/]?li>)@im", $steps, 0, PREG_SPLIT_DELIM_CAPTURE  | PREG_SPLIT_NO_EMPTY);
			$numsteps = preg_match_all('/<li>/m',$steps, $matches );
			$level = 0;
			$steps = "";
			$upper_tag = "";
			// for the redesign we need some extra formatting for the OL, etc
			$levelstack = array();
			$tagstack = array();
			$current_tag = "";
			$current_li = 1;
			$donefirst = false; // used for ads to tell when we've put the ad after the first step
			$bImgFound = false;
			$the_last_picture = '';
			$final_pic = array();
			$alt_link = array();

			// Limit steps to 400 or it will timeout
			if ($numsteps < 400) {

				while ($p = array_shift($parts)) {
					switch (strtolower($p)) {
						case "<ol>":
							$level++;
							if ($level == 1)  {
								$p = '<ol class="steps_list_2">';
								$upper_tag = "ol";
							} else {
								$p = "&nbsp;{$p}";
							}
							if ($current_tag != "")
								$tagstack[] = $current_tag;
							$current_tag = "ol";
							$levelstack[] = $current_li;
							$current_li = 1;
							break;
						case "<ul>":
							if ($current_tag != "")
								$tagstack[] = $current_tag;
							$current_tag = "ul";
							$levelstack[] = $current_li;
							$level++;
							break;
						case "</ol>":
						case "</ul>":
							$level--;
							if ($level == 0) $upper_tag = "";
							$current_tag = array_pop($tagstack);
							$current_li = array_pop($levelstack);
							break;
						case "<li>":
							$closecount = 0;
							if ($level == 1 && $upper_tag == "ol") {
								$li_number = $current_li++;
								$p = '<li><div class="step_num">' . $li_number . '</div>';

								# this is where things get interesting. Want to make first sentence bold!
								# but we need to handle cases where there are tags in the first sentence
								# split based on HTML tags
								$next = array_shift($parts);

								$htmlparts = preg_split("@(<[^>]*>)@im", $next,
									0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
								$dummy = 0;
								$incaption = false;
								$apply_b = false;
								$the_big_step = $next;
								while ($x = array_shift($htmlparts)) {
									# if it's a tag, just append it and keep going
									if (preg_match("@(<[^>]*>)@im", $x)) {
										//tag
										$p .= $x;
										if(preg_match("@<span*@",$x)){
											$incaption = true;
										} elseif ($x == "</span>" && $incaption) {
											$incaption = false;
										}
										continue;
									}
									# put the closing </b> in if we hit the end of the sentence
									if (!$incaption) {
										if (!$apply_b && trim($x) != "") {
											$p .= "<b class='whb'>";
											$apply_b = true;
										}
										if ($apply_b) {
											$x = preg_replace("@([{$punct}])@im", "</b>$1", $x, 1, $closecount);

										}
									}

									$p .= $x;

									if ($closecount > 0) {
										break;
									}
									$dummy++;
								}

								# get anything left over
								$p .= implode("", $htmlparts);

								//microdata the final image if we haven't already tagged the intro img
								if ((!$img_itemprop_done) && ($numsteps == $li_number)) {
									$p = preg_replace('/mwimage101"/','mwimage101" itemprop="image"',$p, 1);
								}

								if ($closecount == 0) $p .= "</b>"; // close the bold tag if we didn't already
								if ($level == 1 && $current_li == 2 && $ads && !$donefirst) {
									$p .= wikihowAds::getAdUnitPlaceholder(0);
									$donefirst = true;
								}

							} elseif ($current_tag == "ol") {
								//$p = '<li><div class="step_num">'. $current_li++ . '</div>';
							}
							break;
						case "</li>":
							$p = "<div class='clearall'></div>{$p}"; //changed BR to DIV b/c IE doesn't work with the BR clear tag
							break;
					} // switch
					$steps .= $p;
				} // while
			} else {
				$steps = substr($body, $i, $j - $i);
				$steps = "<div id='steps_notmunged'>\n" . $steps . "\n</div>\n";
			}

			// we have to put the final_li in the last OL LI step, so reverse the walk of the tokens
			$parts = preg_split("@(<[/]?ul>|<[/]?ol>|<[/]?li>)@im", $steps, 0, PREG_SPLIT_DELIM_CAPTURE);
			$parts = array_reverse($parts);
			$steps = "";
			$level = 0;
			$gotit = false;
			$donelast = false;
			$insertedAlt = false;
			foreach ($parts as $p) {
				$lp = strtolower($p);
				if ($lp == "</ol>" ) {
					$level++;
					$gotit= false;
				} elseif ($lp == "</ul>") {
					$level++;
				} elseif (strpos($lp, "<li") !== false && $level == 1 && !$gotit) {
					/// last OL step list fucker
					$p = preg_replace("@<li[^>]*>@i", '<li class="steps_li final_li">', $p);
					$gotit = true;
				} elseif (strpos($lp, "<ul") !== false) {
					$level--;
				} elseif (strpos($lp, "<ol") !== false) {
					$level--;
				} elseif ($lp == "</li>" && !$donelast) {
					// ads after the last step
					if ($ads) {
						if (substr($body, $j) == ""){
							$p = "<script>missing_last_ads = true;</script>" . wikihowAds::getAdUnitPlaceholder(1) . $p;
							$no_third_ad = true;
						}
						else {
							$p = wikihowAds::getAdUnitPlaceholder(1) . $p;
						}
					}
					$donelast = true;
				}
				$steps = $p . $steps;
			}

			$body = substr($body, 0, $i) . $steps . substr($body, $j);

		} // if numsteps == 400?

		/// ads below tips, walk the sections and put them after the tips
		if ($ads) {
			$foundtips = false;
			$anchorTag = "";
			foreach ($wgWikiHowSections as $s) {
				$isAtEnd = false;
				if ($s == "ingredients" || $s == "steps")
					continue; // we skip these two top sections
				$i = strpos($body, '<div id="' . $s. '"');
				if ($i !== false) {
					$j = strpos($body, '<h2>', $i + strlen($s));
				} else {
					continue; // we didnt' find this section
				}
				if ($j === false){
					$j = strlen($body); // go to the end
					$isAtEnd = true;
				}
				if ($j !== false && $i !== false) {
					$section  = substr($body, $i, $j - $i);
					if ($s == "video") {
						// special case for video
						$newsection = "<div id='video' itemprop='video'><center>{$section}</center></div>";
						$body = str_replace($section, $newsection, $body);
						continue;
					} elseif ($s == "tips") {
						//tip ad is now at the bottom of the tips section
						//need to account for the possibility of no sections below this and therefor
						//no anchor tag
						if ($isAtEnd) {
							$anchorTag = "<p></p>";
						}
						$body = str_replace($section, $section . $anchorTag . wikihowAds::getAdUnitPlaceholder('2a') , $body);
						$foundtips = true;
						break;
					} else {
						$foundtips = true;
						if ($isAtEnd) {
							$anchorTag = "<p></p>";
						}
						$body = str_replace($section, $section . $anchorTag . wikihowAds::getAdUnitPlaceholder(2) , $body);
						break;
					}
				}
			}
			if (!$foundtips && !$no_third_ad) { //must be the video section
				//need to put in the empty <p> tag since all the other sections have them for the anchor tags.
				$body .= "<p class='video_spacing'></p>" . wikihowAds::getAdUnitPlaceholder(2);
			}

		}

		return $body;
	}

	/*
	 * look for some key magic words we'll use in processArticleHTML()
	 */
	public static function grabTheMagic($wikitext) {

		//has parts?
		$mw = MagicWord::get('parts');
		if ($mw->match($wikitext)) return $mw;

		//has methods?
		$mw = MagicWord::get('methods');
		if ($mw->match($wikitext)) return $mw;

		//has ways?
		$mw = MagicWord::get('ways');
		if ($mw->match($wikitext)) return $mw;

		//has no magic?
		return '';
	}

/* unused - Reuben 3/2019
	private static function formatSourcesSectionNew( $sources, $context) {
		$out = RequestContext::getMain()->getOutput();

		if ( !$sources->length ) {
			return;
		}

		pq( $sources )->find( "ol, ul" )->addClass( "sources" );

		$count = pq( $sources )->find( "ol li, ul li" )->length;

		$limit = 21;

		$title = $context->getTitle();
		$request = $context->getRequest();
		// Making all reference links open in a new browser tab. Feature requested by Michelle.
		pq('.reference-text a, .sources li a', $sources)->attr('target', '_blank');

		// Remove the extra <br> if it exists
		pq( $sources )->children("p")->remove();

		$titlesFetched = 0;
		foreach ( pq( $sources )->find( "li a" ) as $refLink ) {
			$refTitle = '';
			$url = pq( $refLink )->text();
			$refTitle = self::getReferenceTitle( $url );
			$url = parse_url( $url );
			$host = $url['host'];
			$host = explode( '.', $host );
			if ( count( $host ) > 2 ) {
				$host = $host[1] . '.' . $host[2];
			} elseif ( count( $host ) == 2 ) {
				$host = $host[0] . '.' . $host[1];
			} elseif ( count( $host ) == 1 ) {
				$host = $host[0];
			} else {
				$host = '';
			}
			$refText = '';
			if ( $host ) {
				$refText = "[" . $host . "]";
			}
			if ( $refTitle ) {
				$refText = $refTitle . " " . $refText;
			}
			$url = pq( $refLink )->text( $refText );
		}

		if ( $count <= $limit ) {
			return;
		}

		// Don't hide refs if it's a printable page
		if ( $out->isPrintable() ) {
			return;
		}

		// Don't hide refs if we're on a special page
		if ( !$title || $title->inNamespace( NS_SPECIAL ) ) {
			return;
		}

		// Don't hide refs if we're in a diff view
		if ( !$request || $request->getVal( 'diff' ) || $request->getVal( 'oldid') ) {
			return;
		}

		// Index of the last item that we will show
		// Choose in which order the lists will be truncated
		$index = $limit - 1;

		// Citations, then sources. This will work too if one of the
		// sections is missing, as that won't affect the selector below.
		// Hide items from the cutoff point
		$selector = "ul, ol";
		if ( pq($sources)->find( ".sources + .references-small" )->length ) {
			$selector =  "ol, ul";
		}
		pq( $sources )->find( $selector )->find( "li:gt($index)" )->addClass( "hidden" );

		// Append link to expand the list
		$remaining = $count - $limit;
		pq( $sources )->append("<a href='#' class='showsources'>" . wfMessage("Show")->text() . ' ' . strtolower(wfMessage("moredotdotdot")->text()) . " ({$remaining})</a>");
	}
*/

	private static function getReferenceTitle( $url ) {
		$title = self::getLinkInfo( $url );
		if ( $title ) {
			return $title;
		}

		return $url;
	}

	public static function getLinkInfo( $url ) {
		$dbr = wfGetDb( DB_REPLICA );
        $table = 'link_info';
        $var = 'li_title';
		$cond = array( 'li_url' => $url );
		$options = array();
		$title = $dbr->selectField( $table, $var, $cond, __METHOD__, $options );
		return $title;
	}

	private static function formatSourcesSection( $sources, $context) {
		$out = RequestContext::getMain()->getOutput();

		if ( !$sources->length ) {
			return;
		}

		pq($sources)->find("ol, ul")->addClass("sources");
		$count = pq($sources)->find("ol li, ul li")->length;
		$limit = 9;

		$title = $context->getTitle();
		$request = $context->getRequest();
		// Making all reference links open in a new browser tab. Feature requested by Michelle.
		pq('.reference-text a, .sources li a', $sources)->attr('target', '_blank');
		// Don't hide refs if it's a printable page, we're on a special page, or we're in a diff view
		if ($count > $limit && !$out->isPrintable()
				&& ( $title && !$title->inNamespace( NS_SPECIAL ) )
				&& ( $request && !$request->getVal( 'diff' ) && !$request->getVal( 'oldid') ) ) {
			$index = $limit - 1; // Index of the last item that we will show
			// Choose in which order the lists will be truncated
			$selector = pq($sources)->find(".sources + .references-small")->length
				? "ul, ol"  // Sources, then citations
				: "ol, ul"; // Citations, then sources. This will work too if one of the
			// sections is missing, as that won't affect the selector below.
			// Hide items from the cutoff point
			pq($sources)->find($selector)->find("li:gt($index)")->addClass("hidden");
			// Append link to expand the list
			$remaining = $count - $limit;
			pq($sources)->append("<a href='#' class='showsources'>" . wfMessage("Show")->text()
					. ' ' . strtolower(wfMessage("moredotdotdot")->text()) . " ({$remaining})</a>");
		}

		// Remove the extra <br> if it exists
		pq($sources)->children("p")->remove();

		// remove all ISBN links
		foreach ( pq( $sources )->find( '.mw-magiclink-isbn' ) as $isbn ) {
			$replaceText = pq( $isbn )->text();
			pq( $isbn )->replaceWith( $replaceText );
		}
	}

	public static function getAnchorList( $altMethodAnchors, $altMethodNames ) {
		$anchorList = [];
		for ( $i = 0; $i < count( $altMethodAnchors ); $i++ ) {
			$methodName = pq( '<div>' . $altMethodNames[$i] . '</div>' )->text();
			// remove any reference notes
			$methodName = preg_replace( "@\[\d{1,3}\]$@", "", $methodName );
			if ( $methodName == "" ) {
				continue;
			}
			$methodName = htmlspecialchars( $methodName );
			//use rawElement so any special characters from the method name shows up correctly in the TOC
			$anchorList[] = Html::rawElement( 'a', ['href' => "#{$altMethodAnchors[$i]}_sub"], $methodName );
		}

		// A hook to add anchors to the TOC.
		Hooks::run('AddDesktopTOCItems', array( RequestContext::getMain()->getTitle(), &$anchorList ) );

		$result = Html::element( 'span', [], wfMessage('toc_title') );
		$result .= implode( "" , $anchorList );
		return $result;
	}
}
