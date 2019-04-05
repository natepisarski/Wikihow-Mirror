<?php

/**
 * Follows something like the active record pattern.
 */
class Wikitext {
	const SUMMARIZED_HEADINGS_KEY = 'wh_summarized_section_headings';

	/**
	 * Get the first [[Image: ...]] tag from an article, and return it as a
	 * URL.
	 * @param string $text the wikitext for the article
	 * @return string The URL
	 */
	public static function getFirstImageURL($text) {
		$url = '';
		if (preg_match('@\[\[Image:([^\]|]*)(\|[^\]]*)?\]\]@s', $text, $m)) {
			$imgTitle = Title::newFromText($m[1], NS_IMAGE);
			if ($imgTitle) {
				$file = wfFindFile($imgTitle);
				if ($file && $file->exists()) {
					$url = $file->getUrl();
				}
			}
		}
		return $url;
	}

	/**
	 * Cut just the first step out of the Steps section wikitext.
	 * @param string $text Steps (only) section wikitext.
	 * @return string The text from the first step of the Steps section.  Note:
	 *   May contain wikitext markup in output.
	 */
	public static function cutFirstStep($text) {
		// remove alternate method title
		$text = preg_replace('@^===[^=]*===@', '', $text);

		// cut just first step
		$text = preg_replace('@^[#*\s]*([^#*]([^#]|\n)*)([#*](.|\n){0,1000})?$@', '$1', $text);
		$text = trim($text);
		return $text;
	}

	// Return just the part of the step up to and including the end of sentence marker
	// (such as [?!.]).
	public static function summarizeStep($stepText) {
		$text = self::flatten($stepText);
		$ret = preg_match('@[?!.](\s|\n)@', $text, $m, PREG_OFFSET_CAPTURE);
		if ($ret) {
			$offset = $m[0][1]; // offset into full string
			if ($offset > 0) {
				$text = substr($text, 0, $offset + 1);
				$text = trim($text);
			}
		}
		$text = preg_replace("@'{2,}@", '', $text);
		$text = preg_replace('@^\s*([#*]|\s)+@', '', $text);
		return $text;
	}

	/**
	 * Remove wikitext markup from a single section of an article to return
	 * the flattened text.  Removes some unicode characters, templates,
	 * links (leaves the descriptive text in the link though) and images.
	 * @param string $text wikitext to flatten
	 * @return string the flattened text
	 */
	public static function flatten($text) {
		// change unicode quotes (from MS Word) to ASCII
		$text = preg_replace('@[\x{93}\x{201C}\x{94}\x{201D}]@u', '"', $text);
		$text = preg_replace('@[\x{91}\x{2018}\x{92}\x{2019}]@u', '\'', $text);

		// remove templates
		$text = preg_replace('@{{[^}]+}}@', '', $text);

		// remove [[Image:foo.jpg]] images and [[Link]] links
		$text = preg_replace_callback(
			'@\[\[([^\]|]+(#[^\]|]*)?)((\|[^\]|]*)*\|([^\]|]*))?\]\]@',
			function ($m) {

				// if the link text has Image: or something at the start,
				// we don't want it to be in the description
				if (strpos($m[1], ':') !== false) {
					return '';
				} else {
					// if the link looks like [[Texas|The lone star state]],
					// we try to grab the stuff after the vertical bar
					if (isset($m[5]) && strpos($m[5], '|') === false) {
						return $m[5];
					} else {
						return $m[1];
					}
				}
			},
			$text);

		// remove [http://link.com/ Link] links
		$text = preg_replace_callback(
			'@\[http://[^\] ]+( ([^\]]*))?\]@',
			function ($m) {
				// if the link looks like [http://link/ Link], we try to
				// grab the stuff after the space
				if (isset($m[2])) {
					return $m[2];
				} else {
					return '';
				}
			},
			$text);

		// remove multiple quotes since they're wikitext for bold or italics
		$text = preg_replace('@[\']{2,}@', '', $text);

		// remove other special wikitext stuff
		$text = preg_replace('@(__FORCEADV__|__TOC__|#REDIRECT|__PARTS__)@i', '', $text);

		// convert special HTML characters into spaces
		$text = preg_replace('@(<br[^>]*>|&nbsp;)+@i', ' ', $text);

		// replace multiple spaces in a row with just one
		$text = preg_replace('@[[:space:]]+@', ' ', $text);

		// remove all HTML
		$text = strip_tags($text);

		return $text;
	}

	/**
	 * Remove references such as "[2]" from "Dogs have tails.[2]" in
	 * flattened (markup-free) wikitext.
	 */
	public static function removeRefsFromFlattened($flattenedText) {
		$text = preg_replace('@\[[0-9]{1,2}\]@', '', $flattenedText);
		return $text;
	}

	/**
	 * Remove http:// links from text
	 * (sometimes caused by <ref> links
	 */
	public static function stripLinkUrls($text, $includeHttps = false) {
		$regex = '@http://.+? @';
		if ($includeHttps) {
			$regex = '@http(s)?://.+? @';
		}
		$text = preg_replace($regex, ' ', $text);
		return $text;
	}

	/**
	 * Extract the intro from the wikitext of an article
	 */
	public static function getIntro($wikitext) {
		global $wgParser;
		$intro = $wgParser->getSection($wikitext, 0);
		return $intro;
	}

	public static function hasSummary($wikitext) {
		return strpos($wikitext, '__SUMMARIZED__') !== false;
	}

	/**
	 * Get the first summary section heading from admin tags or return null
	 */
	public static function getFirstSummarizedSectionHeading() {
		$result = null;

		$headings = explode("\n", ConfigStorage::dbGetConfig(self::SUMMARIZED_HEADINGS_KEY));
		if ( !$headings || count( $headings ) < 1 ) {
			return $result;
		}
		return $headings[0];
	}
	/**
	 * Get the summarized section wikitext if exists. Return empty string otherwise.
	 */
	public static function getSummarizedSection($wikitext) {
		global $wgParser;
		$content = '';
		$headings = explode("\n", ConfigStorage::dbGetConfig(self::SUMMARIZED_HEADINGS_KEY));
		for ($i = 1; $i < 100; $i++) {
			$section = $wgParser->getSection($wikitext, $i);
			if (empty($section)) break;
			if (preg_match('@^\s*==\s*([^=]+)\s*==\s*$((.|\n){0,1000})@m', $section, $m)) {
				if ( in_array( trim( $m[1] ), $headings ) ) {
					$content = $section;
					break;
				}
			}
		}
		return $content;
	}

	/**
	 * Replace the intro in the wikitext
	 */
	public static function replaceIntro($wikitext, $intro) {
		global $wgParser;
		$wikitext = $wgParser->replaceSection($wikitext, 0, $intro);
		return $wikitext;
	}

	/**
	 * Replace the Steps section in the wikitext.
	 */
	public static function replaceStepsSection($wikitext, $sectionID, $stepsText, $withHeader = false) {
		global $wgParser;
		static $stepsMsg = '';
		if (empty($stepsMsg)) $stepsMsg = wfMessage('steps')->inContentLanguage()->text();

		if (!$withHeader) {
			$stepsText = "== $stepsMsg ==\n" . $stepsText;
		}
		$wikitext = $wgParser->replaceSection($wikitext, $sectionID, $stepsText);
		return $wikitext;
	}

	public static function removeVideoSection($wikitext) {
		global $wgParser;
		$section = self::getVideoSection($wikitext);
		if (empty($section[0])) {
			throw new Exception("couldn't find video section");
		}
		$wikitext = $wgParser->replaceSection($wikitext, $section[1], "");
		return $wikitext;
	}

	/**
	 * Extract the Steps section from the wikitext of an article
	 */
	public static function getVideoSection($wikitext, $withHeader = true) {
		global $wgParser;
		static $videoMsg = '';
		if (empty($videoMsg)) $videoMsg = wfMessage('video')->inContentLanguage()->text();
		return self::getSection($wikitext, $videoMsg, $withHeader);
	}

	/**
	 * Count alternate methods in the Steps section.
	 */
	public static function countAltMethods($stepsText) {
		$count = preg_match_all('@^===[^=]@m', $stepsText, $m);
		return $count;
	}

	/**
	 * Split a Steps section into different methods (returned as an array).
	 * NOTE: input should not include "== Steps ==" header
	 */
	public static function splitAltMethods($wikitext) {
		$parts = preg_split('@^=@m', $wikitext);
		$methods = array();
		foreach ($parts as $i => $part) {
			if ($i == 0) {
				if (!empty($part)) {
					$methods[] = $part;
				}
			} else {
				$methods[] = "=" . $part;
			}
		}
		return $methods;
	}

	/**
	 * Check if an individual piece of wikitext looks like an alt method.
	 */
	public static function isAltMethod($wikitext) {
		$wikitext = trim($wikitext);
		$isMethod = (preg_match('@^===[^=]@', $wikitext) > 0);
		return $isMethod;
	}

	/**
	 * Count the number of tips in the Tips section.
	 */
	public static function countTips($wikitext) {
		list($tips, ) = self::getSection($wikitext, wfMessage('tips')->inContentLanguage()->text(), true);
		$count = 0;
		if ($tips) {
			$count = preg_match_all('@\s*\*@m', $tips, $m);
		}
		return $count;
	}

	/**
	 * Count the number of steps in the Steps section.
	 */
	public static function countSteps($stepsText) {
		$num_steps = 0;
		if ($stepsText) {
			// has steps section, so assume valid candidate for detailed title
			$num_steps = preg_match_all('/^#[^*]/im', $stepsText, $m);
		}
		return $num_steps;
	}

	/**
	 * Count the number of images in a block of wikitext
	 */
	public static function countImages($wikitext) {
		$num_images = preg_match_all('/\[\[Image:/im', $wikitext, $m);
		return $num_images;
	}

	/**
	 * Count the number of wikivid in a block of wikitext
	 */
	public static function countVideos($wikitext) {
		$num_videos = preg_match_all('/\{\{whvid\|/im', $wikitext, $m);
		return $num_videos;
	}

	public static function countSummaryVideos($wikitext) {
		$num_videos = preg_match_all('/\{\{whvid\|.*Step 0/im', $wikitext, $m);
		return $num_videos;
	}

	/**
	 * Extract a particular section from the wikitext of an article.
	 *
	 * WARNING: if you pass $withHeader == false to this method,
	 * it will trim the "== Steps ==" header but will limit the output
	 * to 1000 characters. This method should be re-written if the
	 * limitation cannot be worked around.
	 */
	public static function getSection($wikitext, $sectionMsg, $withHeader) {
		global $wgParser;
		if (empty($sectionMsg)) throw new Exception('Must provide the section message');
		$content = '';
		$id = 0;
		for ($i = 1; $i < 100; $i++) {
			$section = $wgParser->getSection($wikitext, $i);
			if (empty($section)) break;
			//removed the space so we can grab "Sources and Citations" as well as "Tips"
			//if (preg_match('@^\s*==\s*([^=\s]+)\s*==\s*$((.|\n){0,1000})@m', $section, $m)) {
			if (preg_match('@^\s*==\s*([^=]+)\s*==\s*$((.|\n){0,1000})@m', $section, $m)) {
                // remove zero width spaces which sometimes appear
                $x = str_replace("\xE2\x80\x8B", "", $m[1]);
				if (trim($x) == $sectionMsg) {
					$content = $withHeader ? $section : trim($m[2]);
					$id = $i;
					break;
				}
			}
		}
		return array($content, $id);
	}

	/**
	 * Remove a particular section from wikitext
	 */
	public static function removeSection($wikitext, $sectionMsg) {
		global $wgParser;
		$section = self::getSection($wikitext, $sectionMsg, true);
		if (empty($section[0]) || empty($section[1])) {
			//fail gracefully
			//throw new Exception("couldn't find $sectionMsg section");
		}
		else {
			$wikitext = $wgParser->replaceSection($wikitext, $section[1], "");
		}
		return $wikitext;
	}

	/**
	 * Remove the "== Section ==" header from a body of wikitext.
	 */
	public static function stripHeader($wikitext) {
		$wikitext = preg_replace('@^\s*==\s*([^=\s]+)\s*==\s*$@m', '', $wikitext);
		return $wikitext;
	}

	/**
	 * Extract the Steps section from the wikitext of an article
	 */
	public static function getStepsSection($wikitext, $withHeader = false) {
		global $wgParser;
		static $stepsMsg = '';
		if (empty($stepsMsg)) $stepsMsg = wfMessage('steps')->inContentLanguage()->text();
		return self::getSection($wikitext, $stepsMsg, $withHeader);
	}

	/**
	 * Split an alternate method, or the Steps section, into different
	 * steps (returned as an array).
	 */
	public static function splitSteps($wikitext, $includeSubsteps = true) {
		$regex = $includeSubsteps ? '@^#@m' : '@^#(?!\*)@m';
		$steps = preg_split($regex, $wikitext);
		for ($i = 1; $i < count($steps); $i++) {
			$steps[$i] = "#" . $steps[$i];
		}
		return $steps;
	}

	public static function splitTips($wikitext) {
		// Remove section header, if it exists
		$wikitext = trim(preg_replace('@^\s*==\s*([^=\s]+)\s*==\s*$@m', '', trim($wikitext)));
		$split = preg_split('@^(\*[^*])@m', $wikitext, -1, PREG_SPLIT_DELIM_CAPTURE);
		$tips = array();

		// Special case: Check for random text at the beginning of the section.  If it's there
		// add it as an element to the $tips array even though it's technically not a tip
		$firstElement = trim(array_shift($split));
		if (preg_match('@^[^*]@', $firstElement)) {
			$tips[] = $firstElement;
		}

		for ($i = 0; $i < count($split); $i = $i + 2) {
				// Combine split deliiter and rest of tip
				$tips[] = trim($split[$i] . $split[$i + 1]);
		}
		return $tips;
	}

	public static function splitWarnings($wikitext) {
		return self::splitTips($wikitext);
	}

	/**
	 * Check if a piece of wikitext is a step (ie, starts with "#").
	 */
	public static function isStepSimple($wikitext) {
		return preg_match('@^#@m', trim($wikitext)) > 0;
	}

	/**
	 * Checks whether a string of wikitext starts with "# ...".  If
	 * $checkTopLevel is true, returns true iff the "..." doesn't indicate
	 * a sub-step or bullet point.
	 */
	public static function isStep($stepText, $checkTopLevel = false) {
		if (preg_match('@^(([#*]|\s)+)((.|\n)*)@m', $stepText, $m)) {
			if (!$checkTopLevel) {
				return true;
			}
            $stripped = preg_replace('@\s+@', '', $m[1]);
			$levels = strlen($stripped);
			if ($levels == 1) {
				return true;
			}
		}
		return false;
	}

	/**
	 * If there are images in the step, replace them with placeholders and
	 * return the modified text.
	 */
	public static function cutImages($stepText) {
		$tokens = array();
		$output = preg_replace_callback('@\[\[Image:[^\]]*\]\]@i',
			function ($m) use (&$tokens) {
				$token = 'IMG_' . Wikitext::genRandomString();
				$tokens[] = array('token' => $token, 'tag' => $m[0]);
				return $token;
			},
			$stepText
		);
		return array($output, $tokens);
	}

	/**
	 * Remove caption from iamge
	 */
	public static function removeImageCaption($img) {
		$params = self::parseImageTag($img);
		if (sizeof($params) < 2) {
			return($img);
		}

		$opts = array('right', 'left', 'center', 'none', 'thumb', 'frame', 'border');
		$regex = '@' . implode('|', $opts) . '|\s*[0-9]\s*px\s*@';

		$newParams = array();
		$newParams[] = $params[0];
		for ($n=1; $n<sizeof($params); $n++) {
			// If it doesn't match the regex, it is a caption
			if (preg_match($regex, $params[$n])) {
				$newParams[] = $params[$n];
			}
		}
		return(self::buildImageTagFromParams($newParams));
	}
	/**
	 * Change the size param in a list of image tag params.  Params should
	 * have been parsed with the parseImageTag() function first.
	 *
	 * Details on understanding image params are here:
	 * http://en.wikibooks.org/wiki/Editing_Wikitext/Pictures/The_Quick_Course
	 *
	 * @param int $size size in pixels of new image tag.  Must be positive.
	 * @param string $orientation new orientation of image.  If emprty string
	 *   is given, don't change orientation.
	 */
	public static function changeImageTag($tag, $size, $orientation) {
		$positionOpts = array('right', 'left', 'center', 'none');
		$typeOpts = array('thumb', 'frame', 'border');
		$size = intval($size);
		$sizePx = $size . 'px';

		if ($orientation && !in_array($orientation, $positionOpts)) {
			print "error: bad orientation given";
			return $tag;
		}
		if ($size <= 0) {
			print "error: bad size given";
			return $tag;
		}

		$params = self::parseImageTag($tag);
		if (count($params) == 1) {
			if ($orientation) {
				$params[] = $orientation;
			}
			$params[] = $sizePx;
		}

		$needsSize = true;
		for ($i = 1; $i < count($params); $i++) {
			$param = strtolower($params[$i]);
			if ($orientation) {
				if (in_array($param, $positionOpts)) {
					$params[$i] = $orientation;
				} else {
					array_splice($params, $i, 0, $orientation);
					$i++;
				}
				if ($i < count($params)
					&& in_array(strtolower($params[$i]), $typeOpts))
				{
					array_splice($params, $i, 1);
				}
				$orientation = '';
				continue;
			}
			if (preg_match('@^\s*[0-9]+\s*px\s*$@', $param, $m)) {
				$params[$i] = $sizePx;
				$needsSize = false;
				break;
			}
		}
		if ($needsSize) {
			$last = count($params);
			$param = strtolower($params[$last - 1]);
			if (!in_array($param, $positionOpts)
				&& !in_array($param, $typeOpts))
			{
				$last--;
			}
			array_splice($params, $last, 0, $sizePx);
		}

		$tag = self::buildImageTagFromParams($params);
		return $tag;
	}

	/**
	 * Parse an image wikitext into params array.
	 */
	private static function parseImageTag($tag) {
		$noBookends = preg_replace('@^\[\[(.*)\]\]$@', '$1', $tag);
		return explode('|', $noBookends);
	}

	/**
	 * Build an image tag from the params array given.  Params should
	 * have been parsed with the parseImageTag() function first.
	 */
	private static function buildImageTagFromParams($params) {
		return '[[' . join('|', $params) . ']]';
	}

	/**
	 * Generate a string of random characters of a given length.
	 */
	public static function genRandomString($chars = 20) {
		$str = '';
		$set = array(
			'0','1','2','3','4','5','6','7','8','9',
			'a','b','c','d','e','f','g','h','i','j','k','l','m',
			'n','o','p','q','r','s','t','u','v','w','x','y','z',
			'A','B','C','D','E','F','G','H','I','J','K','L','M',
			'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		);
		for ($i = 0; $i < $chars; $i++) {
			$r = mt_rand(0, count($set) - 1);
			$str .= $set[$r];
		}
		return $str;
	}

	/**
	 * Utility method to return the wikitext for an article
	 */
	public static function getWikitext(&$dbr, $title) {
		if (!$title) return false;
		// try to see if the wikihow article editor instance already has this title loaded
		$whow = WikihowArticleEditor::wikiHowArticleIfMatchingTitle($title);
		if ($whow) {
			$wikitext = $whow->mLoadText;
		} else {
			$rev = Revision::loadFromTitle($dbr, $title);
			if (!$rev) {
				return false;
			}
			$wikitext = ContentHandler::getContentText( $rev->getContent() );
		}
		return $wikitext;
	}

	/**
	 * Utility method to save wikitext of an article
	 */
	public static function saveWikitext($title, $wikitext, $comment) {
		$wikiPage = WikiPage::factory($title);
		$content = ContentHandler::makeContent($wikitext, $title);

		$saved = $wikiPage->doEditContent($content, $comment);

		if ( !$saved->isOK() ) {
			return 'Unable to save wikitext for article: ' . $title->getText();
		} else {
			return '';
		}
	}

	/**
	 * Enlarge the images in the wikitext for the given title objects.
	 * @return (array) first element any error string (empty if no error);
	 *   2nd element is number of images found/changed
	 */
	public static function enlargeImages($title, $recenter, $px, $introPx = 0) {
		// use master DB to sort out a likely db-lag related race condition
		$dbw = wfGetDB(DB_MASTER);

		$err = '';
		$numImages = 0;
		$stepsText = '';

		$wikitext = self::getWikitext($dbw, $title);
//debugging
//$t = Title::newFromText('Assess Your Relationship Stage');
//$r = Revision::loadFromTitle($dbw, $t, 7607372);
//$wikitext = ContentHandler::getContentText( $r->getContent() );
		if ($wikitext) {
			list($stepsText, $sectionID) =
				self::getStepsSection($wikitext, true);
		}

		if (!$stepsText) {
			$err = 'Unable to load wikitext';
		} else {
			list($stepsText, $numImages, $err) =
				self::enlargeImagesInWikitext($stepsText, $recenter, $px, false);
			if (!$err) {
				$wikitext = self::replaceStepsSection($wikitext, $sectionID, $stepsText, true);

				$comment = $recenter ?
					'Enlarging and centering Steps photos' :
					'Enlarging Steps photos to ' . $px . ' pixels';

				if ($introPx) {
					$intro = self::getIntro($wikitext);
					list($intro, $introImages, $err) =
						self::enlargeImagesInWikitext($intro, '', $introPx, true);
					$numImages += $introImages;
					$wikitext = self::replaceIntro($wikitext, $intro);

					$comment .= '; enlarging intro image';
				}

				if (!$err) {
					$err = self::saveWikitext($title, $wikitext, $comment);
				}
			}
		}
		return array($err, $numImages);
	}

	/**
	 * Enlarge the images in a section of wikitext.  Currently tested with
	 * both intro and steps sections.
	 */
	private static function enlargeImagesInWikitext($text, $recenter, $px, $isIntro) {
		$orientation = $recenter ? 'center' : '';

		$methods = self::splitAltMethods($text);

		$numImages = 0;

		foreach ($methods as &$method) {
			if (!$isIntro) {
				$steps = self::splitSteps($method);
			} else {
				$steps = array($text);
			}

			foreach ($steps as &$step) {
				if ($isIntro || self::isStep($step, false)) {
					list($tokenText, $images) = self::cutImages($step);

					$step = $tokenText;
					$numImages += count($images);

					foreach ($images as $image) {
						$tag = $image['tag'];
						$modtag = self::changeImageTag($tag, $px, $orientation);
						if ($recenter) {
							$step = str_replace($image['token'], '', $step);
							$step = trim($step);
							// make sure we don't re-add <br> tags in case
							// this article's images were already enlarged
							if (!preg_match('@<br><br>$@', $step)) {
								$step .= "<br><br>";
							}
							$step .= "$modtag\n";
						} else {
							$step = str_replace($image['token'], $modtag, $step);
						}
					}
				}
			}
			self::ensureNewlineTerminatedStrings($steps);
			$method = join('', $steps);
		}

		self::ensureNewlineTerminatedStrings($methods);
		$text = join('', $methods);
		return array($text, $numImages, $err);
	}

	/**
	 * Ensure that all strings in an array of strings are newline terminated.
	 */
	private static function ensureNewlineTerminatedStrings(&$arr) {
		foreach ($arr as &$str) {
			$len = strlen($str);
			if ($len > 0 && $str{$len - 1} != "\n") {
				$str .= "\n";
			}
		}
	}

	public static function getTitleImage($title, $skip_parser = false) {
		global $wgContLang, $wgLanguageCode;

		//resolve redirects
		$r = Revision::newFromTitle($title);
		if (!$r) {
			return "";
		}
		$text = ContentHandler::getContentText( $r->getContent() );
		if ($wgLanguageCode == "zh") {
			$text = $wgContLang->convert($text);
		}
		if (preg_match("/^#REDIRECT \[\[(.*?)\]\]/", $text, $matches)) {
			if ($matches[1]) {
				$title = Title::newFromText($matches[1]);
				if (!$title || !$title->exists()) {
					return '';
				}

				$goodRev = GoodRevision::newFromTitle($title, $title->getArticleId());
				$revId = $goodRev ? $goodRev->latestGood() : 0;
				$rev = $revId ? Revision::newFromId($revId) : Revision::newFromTitle($title);
				if (!$rev) return '';

				$text = ContentHandler::getContentText( $rev->getContent() );
			}
		}

		// Make sure to look for an appropriately namespaced image. Always check for "Image"
		// as a lot of files are in the english image repository
		$nsTxt = "(Image|" . $wgContLang->getNsText(NS_IMAGE) . ")";

		//check the steps
		$matches = '';

		if ($skip_parser) {
			$steps[0] = self::getStepsNoParser($text);
		} else {
			$steps = self::getStepsSection($text, true);
		}

		//[[Image:...]]
		preg_match_all("@\[\[" . $nsTxt . ":([^\|]+)[^\]]*\]\]@im", $steps[0], $matches_img);
		if (!empty($matches_img[2])) {
			$matches = $matches_img[2];
		}
		else {
			//{{largeimage|...}}
			preg_match_all("@\{\{largeimage\|([^\||\}]+)\}\}@im", $steps[0], $matches_lgimg);
			if (!empty($matches_lgimg[1])) $matches = $matches_lgimg[1];
		}

		if (empty($matches)) {
			preg_match_all("@(\{\{whvid\|[^\}]*\}\})@im", $steps[0], $matches_whvid);
			$whvid = array();
			if (!empty($matches_whvid[1])) {
				$matches = array();
				$whvid = $matches_whvid[1];
			}

			// just look through all the elements of the whvid templates
			foreach ( $whvid as $match ) {
				$params = explode( "|", $match );
				$preview = null;
				$img = null;
				$default = null;
				// each param of each {{whvid|...|...}} block
				foreach ( $params as $param ) {
					if ( substr_compare( $param, 'preview.jpg',  -strlen( 'preview.jpg' ) ) === 0 ) {
						$preview = $param;
					} elseif ( substr($param, -4) == ".jpg" ) {
						$default = $param;
					}
				}
				if ( $default == null ) {
					$default = $preview;
				}
				$matches[] = $default;
			}
		}

		if ($matches) {
			//grab the last image that appears in the steps section
			$file = wfFindFile(str_replace(" ", "-", end($matches)));

			if ($file && isset($file)) {
				return $file;
			}
		}
	}

	public static function getDefaultTitleImage($title = null, $wide = false) {
		global $wgLanguageCode;


		$intlSuffix = "";
		if ($wgLanguageCode != "en") {
			$intlSuffix = "_intl";
		}
		if ($wide) {
			$image = mt_rand(0,1) == 0 ?
				 "Default_wikihow_green_wide" . $intlSuffix . ".png" : "Default_wikihow_blue_wide" . $intlSuffix . ".png";
		} else {
			$image = mt_rand(0,1) == 0 ?
				"Default_wikihow_green" . $intlSuffix . ".png" : "Default_wikihow_blue" . $intlSuffix. ".png";
		}
		$file = wfFindFile($image, false);
		if (!$file) {
			$file = wfFindFile("Default_wikihow.jpg");
		}

		if ($file) {
			return $file;
		} else {
			return false;
		}
	}

	/*
	 * currently only used by getTitleImage.  Runs way faster than using parser
	 */
	private static function getStepsNoParser($text) {
		$stepsName = strtolower(wfMessage("steps")->inContentLanguage()->text());
		$parts = preg_split('@^\s*==\s*([^=\s]+)\s*==\s*$@m', $text, 0, PREG_SPLIT_DELIM_CAPTURE);
		for ($i = 0; $i < sizeof($parts); $i++) {
			if (strtolower($parts[$i]) == $stepsName) {
				$result = $parts[$i+1];
				break;
			}
		}
		return $result;
	}

	/*
	 * remove references to a list of given image titles from an article
	 *
	 * @param string $fromTitleText - the origin article's title that we want to remove from
	 * @param array $imageTitles - an array of Title objects that correspond to
	 * 		image File titles to be removed
	 * @return the result of doEditContent for the editing
	 */
	public function removeImageLinksFromTitle($fromTitleText, $imageTitles, $asUser) {
		global $wgUser;
		$tempUser = $wgUser;
		$wgUser = $asUser;
		$fromTitle = Title::newFromText($fromTitleText);
		if (!$fromTitle || !$fromTitle->exists()) return false;
		$revision = Revision::newFromTitle($fromTitle);
		if (!$revision) return false;
		$text = ContentHandler::getContentText( $revision->getContent() );
		foreach ($imageTitles as $imageTitle) {
			$text = preg_replace(
					'@(<\s*br\s*[\/]?>)*\s*\[\['.
					preg_quote( $imageTitle->getFullText() ) .'([^\]]*)\]\]@im',
					'',
					$text);

			$text = preg_replace(
					'@(<\s*br\s*[\/]?>)*\s*\[\[Image:'.
					preg_quote( $imageTitle->getDBKey() ) .'([^\]]*)\]\]@im',
					'',
					$text);

			$text = preg_replace(
					'@(<\s*br\s*[\/]?>)*\s*\{\{largeimage|'.
					preg_quote( $imageTitle->getText() ) .'([^\}]*)\}\}@im',
					'',
					$text);
		}
		$content = ContentHandler::makeContent($text, $fromTitle);
		$summary = "removing unlicensed images";
		$page = WikiPage::factory( $fromTitle );
		$result = $page->doEditContent( $content, $summary, EDIT_FORCE_BOT, false, $asUser);
		$wgUser = $tempUser;
		return $result;
	}
}

