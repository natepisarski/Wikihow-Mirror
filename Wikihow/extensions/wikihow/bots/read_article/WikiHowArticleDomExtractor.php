<?php

/**
 * Utility to pull article data from parsed wikitext html
 */

class WikiHowArticleDomExtractor {

	static $hasParts;
	var $revision = null;
	var $parsedHtml = null;
	var $phpQueryDocId = null;
	var $initialized = false;

	/**
	 * @return bool
	 */
	public function isInitialized(): bool {
		return $this->initialized;
	}

	/**
	 * @param bool $initialized
	 */
	protected function setInitialized(bool $initialized) {
		$this->initialized = $initialized;
	}

	public function __construct($r) {
		$this->setRevision($r);
		$this->init();
	}

	protected function init() {
		if (!$this->isInitialized()) {
			$this->postParseDOMTransform();
			$this->setInitialized(true);
		}
	}

	/**
	 * @return null|Revision
	 */
	public function getRevision() {
		return $this->revision;
	}

	/**
	 * @param null|Revision $revision
	 */
	protected function setRevision($revision) {
		$this->revision = $revision;
	}

	/**
	 * @return null
	 */
	protected function getPhpQueryDocId() {
		if ($this->phpQueryDocId) {
			return $this->phpQueryDocId;
		}
		// Silence the errors reported for creating a new document since
		// php query can't recognize some newer html tags (eg video)
		$doc = @phpQuery::newDocument($this->getMediawikiParsedHtml());
		$this->setPhpQueryDocId($doc->getDocumentID());

		return $this->phpQueryDocId;
	}

	/**
	 * @param null $phpQueryDocId
	 */
	protected function setPhpQueryDocId($phpQueryDocId) {
		$this->phpQueryDocId = $phpQueryDocId;
	}

	/**
	 * @param string $parsedHtml
	 */
	protected function setParsedHtml(string $parsedHtml) {
		$this->parsedHtml = $parsedHtml;
	}

	/**
	 * @return string|null
	 */
	public function getMediawikiParsedHtml() {
		if ($this->parsedHtml) {
			return $this->parsedHtml;
		}

		$r = $this->getRevision();
		$t = $r->getTitle();
		$ctx = RequestContext::getMain();
		$out = $ctx->getOutput();

		$content = $r->getContent(Revision::RAW);
		$text = ContentHandler::getContentText($content);
		$popts = $out->parserOptions();
		$popts->setTidy(true);
		$parsedHtml = $out->parse($text, $t, $popts);

		$this->setParsedHtml($parsedHtml);

		return $parsedHtml;
	}

	public function getMethodCount() {
		$this->setAsDefaultPhpQueryDoc();
		return count(pq('div.steps')->not('.sample'));
	}

	protected function getStepsSectionsNodes() {
		$this->setAsDefaultPhpQueryDoc();
		return pq('div.steps')->not('.sample');
	}

	protected function getStepsNodes($section) {
		$this->setAsDefaultPhpQueryDoc();
		return pq('.section_text > ol > li', $section);
	}

	/**
	 * @param $extractingFunction
	 * @param int $methodNum
	 * @param bool $cloneStep Clone the step if extracting function causes any DOM destruction
	 * @return array
	 */
	protected function extractFromSteps($extractingFunction, $methodNum = 0, $cloneStep = false) {
		$extractedData = [];
		$sections = $this->getStepsSectionsNodes();
		//TODO non-alt method article logic
		foreach ($sections as $i => $section) {
			if ($i == $methodNum) {
				$steps = $this->getStepsNodes($section);
				foreach ($steps as $j => $step) {
					// Clone the step
					if ($cloneStep) {
						$step = pq($step)->clone();
					}
					$extractedData []= $extractingFunction($step);
				}
				break;
			}
		}

		return $extractedData;
	}

	public function getStepText($methodNum = 0) {
		$extractionFn = function($step) {
			@pq($step)->find('script')->html('')->remove();
			@pq($step)->find('div')->html('')->remove();
			return trim(pq($step)->text());
		};

		return $this->extractFromSteps($extractionFn, $methodNum, true);
	}

	public function getVideoPlaceHolderImages($methodNum = 0) {
		$extractionFn = function($step) {
			return pq($step)->find('div[id^="lrgimgurl-whvid-"]')->text();
		};

		return $this->extractFromSteps($extractionFn, $methodNum);
	}

	public function getVideoCaptions($methodNum = 0) {
		$extractionFn = function($step) {
			return pq($step)->find('.mwimg-caption-text-first')->text();
		};

		return $this->extractFromSteps($extractionFn, $methodNum);
	}

	public function getStepImages($methodNum = 0) {
		$extractionFn = function($step) {
			$imgSrc = pq('img:first', $step)->attr('src');
			if (empty($imgSrc)) {
				$imgSrc = "";
			}
			else {
			  $imgSrc = stripos($imgSrc, "/") === 0  ? Misc::getLangBaseURL() . $imgSrc : $imgSrc;
			}
			return  $imgSrc;
		};

		return $this->extractFromSteps($extractionFn, $methodNum);
	}


	public function extractFromSummarizedSection($extractionFunction, $cloneSection = false) {
		$extractedData = "";
		// Find the first summarized section
		$headings = explode("\n", ConfigStorage::dbGetConfig(Wikitext::SUMMARIZED_HEADINGS_KEY));
		foreach ( $headings as $i => $heading ) {
			$selector = '#' . WikihowArticleHTML::canonicalizeHTMLSectionName(Misc::getSectionName($heading));
			$summaryNode = pq($selector);
			if (pq($summaryNode)->length > 0) {
				if ($cloneSection) {
					$summaryNode = pq($summaryNode)->clone();
				}

				$extractedData = $extractionFunction($summaryNode);
				break;
			}

		}

		return $extractedData;
	}

	public function getSummarizedSectionHtml() {
		$extractionFn = function($node) {
			return pq($node)->html();
		};

		return $this->extractFromSummarizedSection($extractionFn, true);
	}

	public function getSummarizedSectionText() {
		$extractionFn = function($node) {
			@pq($node)->find('script')->remove();
			@pq($node)->find('#summary_last_sentence')->remove();
			return trim(pq($node)->text());
		};

		return $this->extractFromSummarizedSection($extractionFn, true);
	}

	public function getSummarizedSectionVideoUrl() {
		$extractionFn = function($node) {
			$path = pq($node)->find('.m-video:first')->attr('data-src');
			if (strlen($path) > 0) {
				$path = implode('/', array_map('rawurlencode', explode('/', $path)));
				$path = WHVid::getVidFullPathForAmp($path);
			}
			return $path;
		};

		return $this->extractFromSummarizedSection($extractionFn);
	}

	/**
	 * Do some DOM manipulation to put the HTML in a state that is easier to pull out relevant metadata
	 *
	 * This code is largely borrowed from WikihowArticleHTML::processBody but with significant chunks
	 * removed for simplicity.  While there is a lot of duplicate code, this seemed safer than refactoring the method
	 * for a non-essential project like Alexa development.
	 *
	 * It also ensures that the resulting DOM will be in a predictable state since future changes in
	 * WikihowArticleHTML::processBody will likely occur
	 */
	protected function postParseDOMTransform() {
		if ($this->isInitialized()) {
			return $this->getPhpQueryDoc()->htmlOuter();
		}

		$this->setAsDefaultPhpQueryDoc();

		// Always assume main namespace for now
		$opts['ns'] = NS_MAIN;
		$opts['list-page'] = false;


		// Contains elements with the raw titles of methods (i.e. non-parts)
		$nonAltMethodElements = array();

		$h2s = pq('h2');
		foreach ($h2s as $node) {

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

			$canonicalSectionName = WikihowArticleHTML::canonicalizeHTMLSectionName($sectionName);
			$canonicalSteps = WikihowArticleHTML::canonicalizeHTMLSectionName(wfMessage('steps')->text());
			if ($canonicalSectionName == $canonicalSteps) {
				if ($h3Count > 0) {

					//has alternate methods
					$altMethodNames = array();
//					$altMethodAnchors = array();

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

					//$hasParts = $opts['magic-word'] == MagicWord::get( 'parts' );
					$hasParts = false;
//					self::$hasParts = $hasParts;

					$displayMethod = 1;
					for ($i = 1; $i <= $h3Count; $i++) {
						//change the method title
						$methodTitle = pq("span.mw-headline", $h3Tags[$i])->html();
						//[sc] ***INTERMEDIATE STEP (remove line below)
						$removeRet = WikihowArticleEditor::removeMethodNamePrefix( $methodTitle );
//						$altMethodNames[] = $methodTitle;
//						$altMethodAnchors[] = pq("span.mw-headline", $h3Tags[$i])->attr("id");

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
						if (!$isSample[$i]) {
							pq(".altblock", $h3Tags[$i])->html($methodPrefix);
						} else {
							pq(".altblock", $h3Tags[$i])->remove();
						}


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



					//now we should have all the alt methods,
					//let's create the links to them under the headline
//					$charCount = 0;
//					$maxCount = 80000; //temporarily turning off hidden headers
//					$hiddenCount = 0;
//					$anchorList = [];
//					self::$methodCount = count($altMethodAnchors);
//					for ($i = 0; $i < count($altMethodAnchors); $i++) {
//						$methodName = pq('<div>' . $altMethodNames[$i] . '</div>')->text();
//						// remove any reference notes
//						$methodName = preg_replace("@\[\d{1,3}\]$@", "", $methodName);
//						$charCount += strlen($methodName);
//						$class = "";
//
//						if ($methodName == "") {
//							continue;
//						}
//						$methodName = htmlspecialchars($methodName);
////						$anchorList []= "<a href='#{$altMethodAnchors[$i]}_sub' class='{$class}'>{$methodName}</a>";
//					}

//					$hiddentext = "";
//					if ($hiddenCount > 0) {
//						$hiddenText = "<a href='#' id='method_toc_unhide'>{$hiddenCount} more method" . ($hiddenCount > 1?"s":"") . "</a>";
//						$hiddenText .= "<a href='#' id='method_toc_hide' class='hidden'>show less methods</a>";
//					} else {
//						$hiddenText = '';
//					}

					// A hook to add anchors to the TOC.
//					Hooks::run('AddDesktopTOCItems', array($wgTitle, &$anchorList, &$maxCount));

					//add our little list header
//					if ($hasParts) {//ucwords
//						$anchorList = 	'<span>'.ucwords(Misc::numToWord(count($altMethodAnchors),10)).
//							' ' . wfMessage('part_3')->text() . ':</span>' . implode("",$anchorList);
//					} else {
//						$anchorList = 	'<span>'.ucwords(Misc::numToWord(count($altMethodAnchors),10)).
//							' ' . wfMessage('method_3')->text() . ':</span>' . implode("", $anchorList);
//					}

					//chance to reformat the alt method_toc before output
					//using for running tests
//					Hooks::run('BeforeOutputAltMethodTOC', array($wgTitle, &$anchorList));
//					pq('.firstHeading')->after("<p id='method_toc' class='sp_method_toc'>{$anchorList}{$hiddenText}</p>");
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
						pq($overallSet)->wrapAll("<div class='section steps'></div>");
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
					pq($overallSet)->wrapAll("<div class='section {$sectionName}'></div>");
				} catch (Exception $e) {
				}

				if (@$opts['list-page']) {
					//gotta pull those dangling divs into the same space as the h2
					try {
						pq($overallSet)->parent()->append(pq($new_set));
					} catch(Exception $e) {
					}
				}

			}
		}

		// Remove refs
		pq('.reference')->remove();


		return $this->getPhpQueryDoc()->htmlOuter();
	}

	protected function setAsDefaultPhpQueryDoc() {
		phpQuery::selectDocument($this->getPhpQueryDocId());
	}

	protected function getPhpQueryDoc() {
		return phpQuery::getDocument($this->getPhpQueryDocId());
	}
}
