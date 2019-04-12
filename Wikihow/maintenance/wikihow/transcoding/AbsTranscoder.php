<?php

global $IP;
require_once "$IP/extensions/wikihow/common/S3.php"; 
require_once "$IP/extensions/wikihow/DatabaseHelper.class.php";
require_once __DIR__ . '/AddGifsToVidTag.php';

class HybridMediaException extends Exception { }
class TranscoderException extends HybridMediaException { }

interface Transcodable {
	//after transcoding is done.
    public function processTranscodingArticle($articleId, $creator);

	//transcode or schedule transcode
    public function processMedia( $pageId, $creator, $imageList, $warning, $isHybridMedia );
}

abstract class AbsTranscoder implements Transcodable {
	const BRTAG = "<br><br>";
	const BRTAG_TO_VID = true;
	const BRTAG_TO_IMG = false;
	const USE_NEW_CUT_STEPS = true;
	private $stepsMsg;
	
	function __construct() {
		$this->stepsMsg = wfMessage('steps');
	}
	
	public static function d( $msg, $val = null ) {
	    WikiVisualTranscoder::log( $msg, false, "DEBUG", $val );
	}

	public static function i( $msg, $val = null ) {
	    WikiVisualTranscoder::log( $msg, true, "INFO", $val );
	}

	/**
	 * Load wikitext and get article URL
	 */
	// TODO stop using this
	public function getArticleDetails($id) {
		$dbr = WikiVisualTranscoder::getDB('read');
		$rev = Revision::loadFromPageId($dbr, $id);
		if ($rev) {
			$text = ContentHandler::getContentText( $rev->getContent() );
			$title = $rev->getTitle();
			$url = $title->getFullURL();
			return array($text, $url, $title);
		} else {
			return array('', '', null);
		}
	}

	/**
	 * Load wikitext from latest revision
	 */
	public function getLatestRevisionText( $pageId ) {
		$dbr = wfGetDB( DB_REPLICA );
		$rev = Revision::loadFromPageId($dbr, $pageId);
		if ( !$rev ) {
			return null;
		}

		return ContentHandler::getContentText( $rev->getContent() );
	}
	
	public function cutStepsSection( $articleText ) {
		$newText = '';
		$token = '';

		$steps = Wikitext::getStepsSection( $articleText, true );
		$steps = Wikitext::stripHeader( trim( $steps[0] ) );

		if ( empty($steps) ) {
			return array( $newText, $steps, $token );
		}

		if ( strpos( $articleText, $steps ) === false ) {
			return array( $newText, $steps, $token );
		}

		$token = Misc::genRandomString();
		$newText = str_replace( $steps, $token, $articleText );

		return array($newText, $steps, $token);
	}
	
	/**
	 * Removes all of the specified templates from the start of the intro of the
	 * wikitext.
	 *
	 * @param $wikitext a string of wikitext
	 * @param $templates an array of strings identifying the templates, like
	 *   array('pictures', 'illustrations')
	 */
	 protected function removeTemplates($wikitext, $templates) {
		global $wgParser;
		$intro = $wgParser->getSection($wikitext, 0);
		$replaced = false;
		foreach ($templates as &$template) {
			$template = strtolower($template);
		}
		$intro = preg_replace_callback(
				'@({{([^}|]+)(\|[^}]*)?}})@',
				function ($m) use ($templates, &$replaced) {
					$name = trim(strtolower($m[2]));
					foreach ($templates as $template) {
						if ($name == $template) {
							$replaced = true;
							return '';
						}
					}
					return $m[1];
				},
				$intro
		);
	
		if ($replaced) {
			$wikitext = $wgParser->replaceSection($wikitext, 0, $intro);
		}
		return $wikitext;
	}

	/**
	 * Save wikitext for an article
	 */
	protected function saveArticleText($id, $wikitext) {
		$saved = false;
		$title = Title::newFromID($id);
		if ($title) {
			$wikiPage = WikiPage::factory($title);
			$content = ContentHandler::makeContent($wikitext, $title);
			$saved = $wikiPage->doEditContent($content, 'Saving new step-by-step photos');
		}
		if (!$saved->isOK()) {
			return 'Unable to save wikitext for article ID: ' . $id;
		} else {
			return '';
		}
	}

	private function checkImageMinWidth( $image, $videoList ) {
		$width = $image['width'];
		$response = '';
		$minWidth = WikiVisualTranscoder::ERROR_MIN_WIDTH;

		if ( !empty( $videoList ) ) {
			$minWidth = WikiVisualTranscoder::ERROR_MIN_WIDTH_VIDEO_COMPANION;
		}

		if ( $width < $minWidth ) {
			$response = "size:{$image['width']}px:{$image['name']}\n";
		}
		return $response;
	}

	public function processHybridMedia( $pageId, $creator, $videoList, $photoList, $leaveOldMedia = false, $titleChange = false ) {
		global $wgIsDevServer;
		$err = '';
		$warning = '';
		$replaced = 0;
		$gifsAdded = false;
		$hybridMediaList = null;
		
		$vidBrTag = self::BRTAG_TO_VID ? self::BRTAG : '';
		$imgBrTag = self::BRTAG_TO_IMG ? self::BRTAG : '';
		
		self::d("parse out steps section replacing it with a token, leaving the above and below wikitext intact");

		// parse out steps section replacing it with a token, leaving
		// the above and below wikitext intact
		$text = $this->getLatestRevisionText($pageId);
		// TODO check for missing title much earler in stack
		if ( !$text ) {
			$err = 'Could not find text for article:' . $pageId;
			self::d("getArticleDetails: err:". $err);
		}

		if (!$err) {
			list($text, $steps, $stepsToken) = $this->cutStepsSection($text);
			if (!$stepsToken || empty($steps)) {
				if (preg_match('@^(\s|\n)*#redirect@i', $text)) {
					$err = 'Could not parse Steps section out of article -- article text is #REDIRECT';
				} else {
					$err = 'Could not parse Steps section out of article';
				}
			}

			// remove any leftover images from the article
			// they will likely be in the intro even though we do not put images in the intro
			// anymore it is still possible
			$text = self::removeImagesFromText( $text );
			// count them as well so we have a count of how many images were replaced
			$replaced = preg_match_all('@(\[\[Image:|\{\{largeimage)@im', $text, $throwAway);
		}

		// try to place videos into wikitext, using tokens as placeholders.
		if ( !$err ) {
			list( $err, $hybridMediaList, $replacedMedia ) =
				$this->placeHybridMediaInSteps( $pageId, $videoList, $photoList, $steps, $creator, $leaveOldMedia, $titleChange );
			$replaced += $replacedMedia;
		}
	
		// detect if no photos and videos were to be processed
		if (!$err) {
			if (count($videoList) == 0 && count($photoList) == 0) {
				$err = 'No photos and videos to process';
			}
		}
	
		// replace the tokens within the video or image tag
		if (!$err && $hybridMediaList && count($hybridMediaList) > 0) {
			$isAllLandscape = true;
			$hadColourProblems = false; 
			$hadSizeProblems = false; 
			$isAllPhotoLandscape = count($photoList) > 0 ? true : false;

			$text = str_replace($stepsToken, rtrim($steps), $text);

            foreach ($hybridMediaList as &$media) {
				$titleChangeSkip = $media['titlechangeskip'];
				$video = null;
				if ( array_key_exists( 'video', $media ) ) {
					$video = $media['video'];
				}
				
				if ( $video ) { // video related validation
					if (!empty($video['width']) && !empty($video['height'])
						&& $video['width'] > $video['height']
					) {
						// nothing
					} else {
						// Log first portrait video
						if (!$isAllLandscape) {
							$warning .= "portrait:{$video['name']}\n";
						}
						$isAllLandscape = false;
					}
		
					// Log pixel width issues
					if (!$hadSizeProblems
					&& !empty($video['width'])
					&& $video['width'] < WikiVisualTranscoder::VIDEO_WARNING_MIN_WIDTH)
					{
						$warning .= "size:{$video['width']}px:{$video['name']}\n";
						$hadSizeProblems = true;
					}
				}
				
				$image = null;
				if ( array_key_exists( 'photo', $media ) ) {
					$image = $media['photo'];
				}

				if ($image) {
					if (!empty($image['width']) && !empty($image['height'])
						&& $image['width'] > $image['height']
					) {
						// nothing
					} else {
						// Log first portrait image
						if (!$isAllPhotoLandscape) {
							$warning .= "portrait:{$image['name']}\n";
						}
						$isAllPhotoLandscape = false;
					}
					
					// Detect colour profile issues
					if (!$hadColourProblems && !empty($image['filename'])) {
						$exifProfile = WikiPhoto::getExifColourProfile($image['filename']);
						if ($exifProfile && WikiPhoto::isBadWebColourProfile($exifProfile)) {
							$warning .= "colour:$exifProfile:{$image['name']}\n";
							$hadColourProblems = true;
						}
					}
					
                    // check for min and max image size
                    if ( !$hadSizeProblems && !empty( $image['width'] ) ) {
						$err .= $this->checkImageMinWidth( $image, $videoList );
						if ( $err ) {
                            $hadSizeProblems = true;
						}
						$maxImgDimen = $image['width'] > $image['height'] ? $image['width'] : $image['height'];
						if ($maxImgDimen > WikiVisualTranscoder::ERROR_MAX_IMG_DIMEN) {
							$err .= "size:{$image['width']}px > max size ". WikiVisualTranscoder::ERROR_MAX_IMG_DIMEN ."px:{$image['name']}\n";
							$hadSizeProblems = true;
						}
                    }
				}
			
				if ( $video ) {
					self::d("video", $video);
				}
				if ( $image ) {
					self::d("image", $image);
				}

				$mediaTag = null;
				// if title change skip, then simply replace the token with nothing
				if ( $titleChangeSkip ) {
					$text = str_replace($video['token'], "", $text);
					$text = str_replace($image['token'], "", $text);
				} else if ($video && !$image) { //video only
					$mediaTag = $vidBrTag.'{{whvid|' . $video['mediawikiName'] . '|' . $video['previewMediawikiName'] . '}}';
					$text = str_replace($video['token'], $mediaTag, $text);
				} elseif (!$video && $image) { //image only
					$mediaTag = $imgBrTag.'[[Image:' . $image['mediawikiName'] . '|center]]';
					$text = str_replace($image['token'], $mediaTag, $text);
				} elseif ($video && $image) { //hybrid
					$mediaTag = $vidBrTag.'{{whvid|' . $video['mediawikiName'] . '|' . $video['previewMediawikiName'] . 
																	   '|' . $image['mediawikiName'] . '}}';
					$text = str_replace($video['token'], $mediaTag, $text);
				}
			}
		}

		// remove certain templates from start of wikitext
		if (!$err) {
			$templates = array('illustrations', 'pictures', 'screenshots');
			$text = $this->removeTemplates($text, $templates);
		}

		// not working on dev right now
		if ( !$wgIsDevServer && !$err && $videoList && count( $videoList ) ) {
			self::d("will convert mp4 to gifs");
			// add the gifs now.. the videos have been downloaded already
			$gifsError = $this->createGifsFromVideos( $pageId );
			if ($gifsError) {
				$warning .= "error making gifs: " . $gifsError;
			}
			$newText = AddGifsToVidTag::addToText( $text );
			if ( $newText != $text ) {
				$gifsAdded = true;
				$text = $newText;
			}
		}

		$text = $this->addStepZeroVideoToText( $videoList, $photoList, $text );

		// write wikitext and add/update wikivideo row
		if (!$err) {
			$err = $this->saveArticleText($pageId, $text);
		}

		// remove transcoding job db entries and s3 URIs
		//self::removeOldTranscodingJobs($pageId);
		
		if ( !$err ) {
			$numPhotos = $photoList ? count($photoList) : 0;
			$numVideos = $photoList ? count($videoList) : 0;
			$title = Title::newFromID( $pageId );
			$url = $title->getFullURL();
			self::i("processed wikitext: $creator $pageId $url ".
				"photos=" . $numPhotos . ", ".
				"videos=" . $numVideos . " $err");
		}

		$result = array( $err, $warning, $replaced, $gifsAdded );
		return $result;
	}

	// TODO do not convert the step 0 video
	private function createGifsFromVideos( $pageId ) {
		global $IP;
		if ( !$pageId ) {
			return "no pageId";
		}

		$path = WikiVisualTranscoder::getGifStagingPath( $pageId );

		$filecount = 0;
		$files = glob($path . "/*.mp4");
		if ($files){
			 $filecount = count($files);
		}
		if ( $filecount < 1 ) {
			return "no mp4 files to convert to gifs";
		}
		self::d( "will convert " . $filecount . " mp4s to gifs and import them" );

		$return_var = 1;
		// we can optionally add the page id to this script to add a watermark to the gifs
		exec( $IP."/../scripts/gifcreation/makeGifsImportGifs.sh -l $path 2>&1", $output, $return_var );
		$lastLine = "";
		foreach ( $output as $line ) {
			self::d($line);
			$lastLine = $line;
		}

		if ( $return_var == 0 ) {
			$error = "";
		} else {
			$error = "error converting and importing gifs: $lastLine";
		}
		return $error;
	}

	private function createStepToken(&$mediaList, &$steps, $tokenPrefix, $stepNum, $i, $m, $substToken) {
		$stepIdx = false;
		if ( !$mediaList ) {
			return false;
		}

		foreach ($mediaList as $j => $media) {
			if ($media['step'] == $stepNum && $media['sub'] == null) {
				$stepIdx = $j;
				break;
			}
		}
		if ($stepIdx !== false && $substToken !== false) {
			$mediaToken = $tokenPrefix . Misc::genRandomString() . '_' . $stepNum;

			// now we can remove the old image/video tag
			$m[3] = self::removeMediaReferenceFromText( $m[3] );

			// any strings in this list of wordExceptions will have their token placed before this string
			$wordExceptions = array();
			$wordExceptions[] = wfMessage('summary_section_notice')->text();

			$exceptionFound = false;
			foreach( $wordExceptions as $word ) {
				if (preg_match('@[\n]+' . $word . '@mi', $m[3])) {
					$steps[$i] = trim($m[1]) . trim(preg_replace('@([\n]+'.$word.')@mi', $mediaToken . '$1', $m[3])) . "\n";
					$exceptionFound = true;
					break;
				}
			}

			if ( $exceptionFound == false ) {
				if (preg_match('@[\n]+=@m', $m[3])) {
					$steps[$i] = trim($m[1]) . trim(preg_replace('@([\n]+=)@m', $mediaToken . '$1', $m[3])) . "\n";
				} else if (preg_match('@[\n]+__parts__|[\n]+__methods__|[\n]+__summarized__@mi', $m[3])) {
					$steps[$i] = trim($m[1]) . trim(preg_replace('@([\n]+__parts|[\n]+__methods__|[\n]+__summarized__)@mi', $mediaToken . '$1', $m[3])) . "\n";
				} else {
					$steps[$i] = trim($m[1]) . trim($m[3]) . $mediaToken . "\n";
				}
			}

			$mediaList[$stepIdx]['token'] = $mediaToken;
			self::d("mediaList[$stepIdx]['token'] : ". $mediaList[$stepIdx]['token']);
			self::d("steps[$i] : ". $steps[$i]);
		}

		return $stepIdx;
	}
	
	private function createSubStepToken(&$mediaList, &$steps, $tokenPrefix, $stepNum, $subNum, $i, $m, $substToken) {
		$stepIdx = false;
		if ( !$mediaList ) {
			return false;
		}

		foreach ($mediaList as $j => $media) {
			if ($media['step'] == ($stepNum - 1) ) {
				if ($media['sub'] != null && $media['sub'] == $subNum) {
					$stepIdx = $j;
					break;
				}
			}
		}

		if ($stepIdx !== false && $substToken !== false) {
			$mediaToken = $tokenPrefix . Misc::genRandomString() . '_' . ($stepNum - 1) . "_" . $subNum;

			// now we can remove the old image/video tag
			$m[3] = self::removeMediaReferenceFromText( $m[3] );

			if (preg_match('@[\n]+=@m', $m[3])) {
				$steps[$i] = trim($m[1]) . trim(preg_replace('@([\n]+=)@m', $mediaToken . '$1', $m[3])) . "\n";
			} else if (preg_match('@[\n]+__parts__|[\n]+__methods__@mi', $m[3])) {
				$steps[$i] = trim($m[1]) . trim(preg_replace('@([\n]+__parts|[\n]+__methods__)@mi', $mediaToken . '$1', $m[3])) . "\n";
			} else {
				$steps[$i] = trim($m[1]) . trim($m[3]) . $mediaToken . "\n";
			}
			$mediaList[$stepIdx]['token'] = $mediaToken;
			self::d("mediaList[$stepIdx]['token'] : ". $mediaList[$stepIdx]['token']);
			self::d("steps[$i] : ". $steps[$i]);
		}

		return $stepIdx;
	}
		
	private function getStepSubStepIdx($stepNum, $subNum) {
		return $stepNum . '_' . $subNum;
	}
	
	//abstract public function addWikiHowVideo($pageId, &$video);
    /**
     * Add a new video file into the mediawiki infrastructure so that it can
     * be accessed as {{whvid|filename.mp4|Preview.jpg}}
     */
    public function addWikiHowVideo($articleId, &$video) {
        // find name for video; change filename to Filename 1.jpg if
        // Filename.jpg already existed
        $regexp = '/[^' . Title::legalChars() . ']+/';
        $first = preg_replace($regexp, '', $video['first']);
        // Let's also remove " and ' since s3 doesn't seem to like
        $first = preg_replace('/["\']+/', '', $first);
        $ext = $video['ext'];
        $newName = $first . '.' . $ext;
        $i = 1;
        do {
			if ( !WikiVideo::fileExists( $newName ) ) {
				break;
			}
            $newName = $first . ' Version ' . ++$i . '.' . $ext;
        } while ($i <= 1000);
    
        // Move the file from one s3 bucket to another
        $ret = WikiVideo::copyFileToProd(WikiVisualTranscoder::AWS_TRANSCODING_OUT_BUCKET, $video['aws_uri_out'], $newName);
		if ( $ret['error'] ) {
			return $ret['error'];
		}
    
        // instruct later processing about which mediawiki name was used
        $video['mediawikiName'] = $newName;
    
        // Add preview image
        $img = $video;
        $img['ext'] = 'jpg';
        $err = Mp4Transcoder::addMediawikiImage($articleId, $img);
        if ($err) {
            return 'Unable to add preview image: ' . $err;
        } else {
            $video['previewMediawikiName'] = $img['mediawikiName'];
            // Cleanup temporary preview image
            if (!empty($img['filename'])) {
                $rmCmd = "rm " . $img['filename'];
                system($rmCmd);
            }
        }
    
        self::d("video['mediawikiName']=". $video['mediawikiName'] .", video['previewMediawikiName']=". $video['previewMediawikiName']);
        // Keep a log of where videos were uploaded in wikivisual_video_names table
        $dbw = WikiVisualTranscoder::getDB('write');
        $vidname = $articleId . '/' . $video['name'];
        $sql = 'INSERT INTO wikivisual_vid_names SET filename=' . $dbw->addQuotes($vidname) . ', wikiname=' . $dbw->addQuotes($video['mediawikiName']);
        $dbw->query($sql, __METHOD__);

        return '';
    }

	
	private static function removeImagesFromText( $text ) {
		$text = preg_replace('@\[\[Image:[^\]]*\]\]@im', '', $text);
		$text = preg_replace('@\{\{largeimage\|[^\}]*\}\}@im', '', $text);
		return $text;
	}

	private static function hasMediaReference( $text ) {
		$count = 0;

		preg_replace( '@'. self::BRTAG .'\{\{whvid\|[^\}]*\}\}@im', '', $text, -1, $count );
		if ( $count > 0  ) {
			return true;
		}
		preg_replace( '@'. self::BRTAG .'\[\[Image:[^\]]*\]\]@im', '', $text, -1, $count );
		if ( $count > 0  ) {
			return true;
		}
		preg_replace( '@'. self::BRTAG .'\{\{largeimage\|[^\}]*\}\}@im', "", $text, -1, $count );
		if ( $count > 0  ) {
			return true;
		}
		preg_replace( '@\{\{whvid\|[^\}]*\}\}@im', '', $text, -1, $count );
		if ( $count > 0  ) {
			return true;
		}
		preg_replace( '@\[\[Image:[^\]]*\]\]@im', '', $text, -1, $count );
		if ( $count > 0  ) {
			return true;
		}
		preg_replace( '@\{\{largeimage\|[^\}]*\}\}@im', "", $text, -1, $count );
		if ( $count > 0  ) {
			return true;
		}
		return false;
	}

	private static function removeMediaReferenceFromText( $text ) {
		$text = preg_replace('@'. self::BRTAG .'\{\{whvid\|[^\}]*\}\}@im', '', $text);
		$text = preg_replace('@'. self::BRTAG .'\[\[Image:[^\]]*\]\]@im', '', $text);
		$text = preg_replace('@'. self::BRTAG .'\{\{largeimage\|[^\}]*\}\}@im', "", $text);
		$text = preg_replace('@\{\{whvid\|[^\}]*\}\}@im', '', $text);
		$text = preg_replace('@\[\[Image:[^\]]*\]\]@im', '', $text);
		$text = preg_replace('@\{\{largeimage\|[^\}]*\}\}@im', "", $text);
		return $text;
	}

	private function addStepZeroVideoToText( $videos, $images, $text ) {
		$result = $text;
		if ( !$videos || !$text ) {
			return $result;
		}

		$zeroStepVideo = null;
		// add the step 0 video to summary section
		foreach ( $videos as $video ) {
			if ( isset( $video['step'] ) && $video['step'] == 0 ) {
				// look for section
				$zeroStepVideo = $video;
				break;
			}
		}
		if ( !$zeroStepVideo ) {
			self::d("no zero step video");
			return $result;
		}


		$summarySectionText = Wikitext::getSummarizedSection( $text );
		if ( !$summarySectionText ) {
			$heading  =  Wikitext::getFirstSummarizedSectionHeading();
			if ( !$heading ) {
				self::i("article has no summary section and no summarized headings found for this lang. will not add summary section");
				return $result;
			}
			$heading = "== " . $heading . " ==";
			// add the heading to be used later for finde/replacing the video
			$summarySectionText = $heading;

			// add the summary section to the text
			$text .= PHP_EOL . PHP_EOL . $heading;
		}
		$lines = explode( PHP_EOL, $summarySectionText );
		$sectionHeader = $lines[0];

		// if any lines have whvid then we need to remove it
		$lastLine = $lines[count($lines) - 1];
		foreach ( $lines as $lineNum => $line ) {
			if ( strstr( $line, '{{whvid' ) ) {
				unset( $lines[ $lineNum ] );
			}
		}
		if ( strpos( $text, $summarySectionText ) === false ) {
			self:i("summary section not found!");
			return $result;
		}

		$summaryIntroImage = $video['previewMediawikiName'];
		$summaryOutroImage = null;

		//look for intro and outro images
		foreach ( $images as $image ) {
			if ( $image['step'] == '0' ) {
				$summaryIntroImage = $image['mediawikiName'];
			}
			if ( $image['step'] == 'outro' ) {
				$summaryOutroImage = $image['mediawikiName'];
			}
		}

		$videoTemplate = '{{whvid|' . $video['mediawikiName'] . '|' . $summaryIntroImage . "}}\n";
		if ( $summaryOutroImage ) {
			$videoTemplate = "{{whvid|" . $video['mediawikiName'] . "|" . $summaryIntroImage .  "|" . $summaryOutroImage . "}}\n";
		}

		// remove the section header, placing the whvid template in the beginning of the lines
		unset($lines[0]);
		$newText = $sectionHeader . "\n" . $videoTemplate . implode( "\n", $lines );
		$result = str_replace( $summarySectionText, $newText, $text );

		return $result;
	}

	/**
	 * Place a set of videos into an article's wikitext. also will add corresponding images
	 * process the list of media to make sure we can understand all filenames
	 * this also adds extra data to the $videos and $images array for each image or video
	 * such as if it is a final step or a bullet step. it will return an error
	 * if there is some badly named image or video
	 * leaveOldMedia - bool - if true we will only replace images that we find matches for
	 */
	private function placeHybridMediaInSteps( $pageId, &$videos, &$images, &$stepsText, $creator, $leaveOldMedia = false, $titleChange = false ) {
        $err = '';
		$hybridMediaList = array();
		$title = Title::newFromID( $pageId );
		$replaced = 0;


		// remove all image and video templates from the steps text by setting this to true
		$removeExistingMediaRefs = !$leaveOldMedia;
		if ( $removeExistingMediaRefs ) {
			$replaced = preg_match_all('@(\{\{whvid\|)@im', $stepsText, $throwAway);
			$replaced += preg_match_all('@(\[\[Image:|\{\{largeimage)@im', $stepsText, $throwAway);
		}
		
		// TODO instead of doing two things in one function
		// we should have a function that adds extra info then after
		// that returns just look through the videos to see if any has a final step name
		$hasFinalStepVid = false;

		list( $err, $hasFinalStepVid ) = $this->addExtraVideoInfo( $title, $videos );
		if ( $err ) {
			self::d("Got error from addExtraVideoInfo: [$err]");
			return array($err, null);
		}

		$hasFinalStepImg = false;
		if ( $images != null && count( $images > 0 ) ) {
			list( $err, $hasFinalStepImg ) = $this->addExtraPhotoInfo( $title, $images );
		}
		if ( $err ) {
			self::d("Got error from addExtraPhotoInfo: [$err]");
			return array($err, null);
		}

		// we want to know if one of the images/videos has final instead of a step num
		self::d("Final step Vid=". (int)$hasFinalStepVid .", Img=". (int)$hasFinalStepImg);
		$hasFinalStep = $hasFinalStepImg || $hasFinalStepVid;

		// split steps based on ^# then add the '#' character back on
		$steps = preg_split('@^\s*#@m', rtrim($stepsText) ."\n");

		for ($i = 1; $i < count($steps); $i++) {
			$steps[$i] = "#" . $steps[$i];
			$steps[$i] = preg_replace('@(<br> *)+$@im', '', $steps[$i]);
		}

		if ( $hasFinalStep ) {
			//also remove last step if it contains only 'Finished'
			$tstep = array_pop( $steps );
			if (strtolower(WikiVisualTranscoder::FINISHED) !=  
				strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", strip_tags(html_entity_decode($tstep))))) {
				$steps[] = $tstep; 
			}
		}

		try {
			// check number of steps vs amount of media we are trying to insert
			$this->assertStepsSize( count( $steps ), $images, $videos );
		} catch ( TranscoderException $e ) {
			$err = $e->getMessage();
			self::d( "assertStepsSize err: [$err]" );
			return array( $err, null, null );
		}

		// place media in steps
		$stepNum = 1;
		for ($i = 1; $i < count($steps); $i++) {
			// only process a step if it current has media reference in the step
			$titleChangeSkip = false;
			if ( $titleChange && !self::hasMediaReference( $steps[$i] ) ) {
				$titleChangeSkip = true;
			}
			if ( $removeExistingMediaRefs == true ) {
				$steps[$i] = self::removeMediaReferenceFromText( $steps[$i] );
			}
			// just take the first 30 characters of the step so the regex will not
			// run into any size limits and put the rest back on after
			$first = $steps[$i];
			$extra = "";
			if ( strlen( $steps[$i] ) > 30 ) {
				$first= substr($steps[$i], 0, 30);
				$extra = substr($steps[$i], 30);
			}

			// this regex checks to make sure that the line begins with #
			if (preg_match('@^(([#*]|\s)+)((.|\n)*)@m', $first, $m)) {
				$m[3] .= $extra;
				$stepIdxVid = false;
				$stepIdxPhoto = false;
				$stripped = preg_replace('@\s+@', '', $m[1]);
				$m[1] = trim($m[1]);
				$levels = strlen($stripped);
				if ($levels == 1) {
					$subNum = 0;

					$stepIdxVid = $this->createStepToken($videos, $steps, 'VID_', $stepNum, $i, $m, true);
					$stepIdxPhoto = $this->createStepToken($images, $steps, 'IMG_', $stepNum, $i, $m, $stepIdxVid === false ? true : false);
					$stepNum++;
				} else if ($levels == 2) {
					//we're in a bullet, check to see if we have a
					//video for this substep
					$subNum++;

					$stepIdxVid = $this->createSubStepToken($videos, $steps, 'VID_', $stepNum, $subNum, $i, $m, true);
					$stepIdxPhoto = $this->createSubStepToken($images, $steps, 'IMG_', $stepNum, $subNum, $i, $m, $stepIdxVid === false ? true : false);
				}

				if ( $stepIdxVid !== false && isset( $videos[$stepIdxVid] ) && $videos[$stepIdxVid] ) {
					self::d( "videos[$stepIdxVid]", $videos[$stepIdxVid] );
				}
				if ( $stepIdxPhoto !== false && isset( $images[$stepIdxPhoto] ) && $images[$stepIdxPhoto] ) {
					self::d( "images[$stepIdxPhoto]", $images[$stepIdxPhoto] );
				}
				$hybridMedia = array(
					'titlechangeskip' => $titleChangeSkip
				);
				if ($stepIdxVid !== false) {
					$hybridMedia['video'] = &$videos[$stepIdxVid];
				}
				if ($stepIdxPhoto !== false) {
					$hybridMedia['photo'] = &$images[$stepIdxPhoto];
				}
				$hybridMediaList[] = $hybridMedia;
			} else {
                self::d("No match preg_match('@^(([#*]|\s)+)((.|\n)*)@m' with text [". $first."]");
			}
		}
	
		// take care of -final step if any
		if (!$err && $hasFinalStep !== false) {
			$finalStepText = "#" . WikiVisualTranscoder::FINISHED .'.';
			$steps[] = $finalStepText;
			$finalStepIdx = array_search($finalStepText, $steps);
			
			if (preg_match('@^(([#*]|\s)+)((.|\n)*)@m', $finalStepText, $m)) {
				$stepIdxVid = false;
				$stepIdxPhoto = false;
				$stepIdxVid = $this->createStepToken($videos, $steps, 'VID_', WikiVisualTranscoder::FINALSTR, $finalStepIdx, $m, true);
				$stepIdxPhoto = $this->createStepToken($images, $steps, 'IMG_', WikiVisualTranscoder::FINALSTR, $finalStepIdx, $m, $stepIdxVid === false ? true : false);
				$hybridMedia = array();
				if ($stepIdxVid !== false) $hybridMedia['video'] = &$videos[$stepIdxVid];
				if ($stepIdxPhoto !== false) $hybridMedia['photo'] = &$images[$stepIdxPhoto];
				$hybridMediaList[] = $hybridMedia;
			}
		}
		
		// were we able to place all videos in the article?
		$notPlaced = array();
		$placed = array();

		if ( !$removeExistingMediaRefs ) {
			$replaced += self::countAddedMedia( $images, $videos);
		}

		// when step contains hybrid then only vid gets placed
		// use this to avoid failure while checking images
		foreach ($videos as $video) {
			if ( !isset( $video['token'] ) && !$video['step'] == 0 ) {
				$notPlaced[] = $video['name'];
			} else {
				$tokenVideos[] = $video;
				$fnames = explode('.',$video['name']);
				$placed[$fnames[0]] = $video['name']; //get filename before 1st '.' as key
			}

			if ( $video['step'] == 0 ) {
				if ( $images ) {
					foreach ($images as $image) {
						$fnames = explode('.', $image['name']);
						if ( substr( $fnames[0], -6 === '-outro' ) ) {
							$placed[$fnames[0]] = $video['name'];
						}
					}
				}
			}

		}

		if ( !$images ) {
			$images = array();
		}

		// were we able to place all images in the article?
		foreach ($images as $image) {
			$fnames = explode('.', $image['name']);
			if (!isset($image['token']) && 
				!array_key_exists($fnames[0], $placed)) {
				$notPlaced[] = $image['name'];
			}
		}
		
		if ($notPlaced) {
			self::d( 'not placed', $notPlaced );
			$err = 'Unable to place media in the wikitext: ' . join(', ', $notPlaced);
		}
	
		// add all these videos to the wikihow mediawiki repos
		if (!$err) {
			$videosAdded = array();

			foreach ($videos as &$vid) {
				$error = $this->addWikiHowVideo($pageId, $vid);
				if (strlen($error)) {
					$err = 'Unable to add new video file ' . $vid['name'] . ' to wikiHow: ' . $error;
				} else {
					$vid['width'] = WikiVisualTranscoder::DEFAULT_VIDEO_WIDTH;
					$vid['height'] = WikiVisualTranscoder::DEFAULT_VIDEO_HEIGHT;

					$videosAdded[] = $vid;
				}
			}

			if ($videosAdded) {
				Hooks::run('WikiVisualS3VideosAdded', array(
					$pageId,
					$creator,
					$videosAdded
				));
			}
		
			// copy a version of the video for use by gif creation
			$error = WikiVisualTranscoder::downloadTranscodedVideos( $pageId, $videos );
			if (strlen($error)) {
				$err = 'Unable to download video for gif creation' . $vid['mediawikiName'] . ' to: ' . WikiVisualTranscoder::getStagingDir() . ' error:' . $error;;
			}

            self::d(">>>>>>>> count(\$images)". count($images));
			if (!$err && $images && count($images) > 0) {
				$err = ImageTranscoder::addAllMediaWikiImages($pageId, $images);

				if ($images && count($images) > 0) {
					Hooks::run('WikiVisualS3ImagesAdded', array(
						$pageId,
						$creator,
						$images
					));
				}
			}
	
			if (!$err) {
				$stepsText = join('', $steps);
				if (count($steps) && trim($steps[0]) == '') {
					$stepsText = "\n" . $stepsText;
				}
			}
		}

		return array( $err, &$hybridMediaList, $replaced );
	}

	
	// count images + videos minus overlapping images(ie static images for videos)
	public static function countMedia( $images, $videos ) {
		// figure out number of media
		$numMedia = 0;
		if ( count( $videos ) == 0 && count( $images ) == 0 ) {
			return;
		} else if ( count( $videos ) == 0 ) {
			$numMedia = count( $images );
		} else if ( count( $images ) == 0 ) {
			$numMedia = count( $videos );
		} else {
			// we need to calculate num media based on videos and images, which may overlap
			$imageSpots = array();
			foreach ( $images as $image ) {
				$key = $image['step'];
				$sub = $image['sub'];
				if ( $sub != null ) {
					$key .= "b" . $sub;
				}

				$imageSpots[$key] = true;
			}
			foreach ( $videos as $video ) {
				$key = $video['step'];
				$sub = $video['sub'];
				if ( $sub != null ) {
					$key .= "b" . $sub;
				}
				unset( $imageSpots[$key] );
			}
			$numMedia = count( $videos ) + count( $imageSpots );
		}
		self::d( "num media is", var_export( $numMedia, true ) );

		return $numMedia;
	}

	public static function countAddedMedia( $images, $videos ) {
		if ( $images == null ) {
			$images = array();
		}
		if ( $videos == null ) {
			$videos = array();
		}

		$tokenImages = array_filter( $images, function ( $x ) {
			return array_key_exists( "token", $x );
		});

		$tokenVideos = array_filter( $videos, function ( $x ) {
			return array_key_exists( "token", $x );
		});

		return self::countMedia( $tokenImages, $tokenVideos);
	}

	// assert number of steps is enough to be able to place the number of media items
	public static function assertStepsSize( $numSteps, $images, $videos ) {
		$numMedia = self::countMedia( $images, $videos );

		if ( $numMedia > $numSteps  ) {
			throw new TranscoderException( __METHOD__ . ": parsed $numSteps step(s) in wikitext but trying to add $numMedia items" );
		}
	}

	// process the list of images to make sure we can understand all filenames
	public function addExtraPhotoInfo($title, &$images) {
		$hasFinalStep = false;
		$err = null;
		foreach ($images as &$img) {
			if (!preg_match('@^((.*)-\s*)?([0-9b]+|final|outro)\.(' . join('|', WikiVisualTranscoder::$imgExts) . ')$@i', $img['name'], $m)) {
				$err .= 'Filename not in format Name-1.jpg: ' . $img['name'] . '. ';
                self::d("Filename not in format Name-1.jpg: " . $img['name'] . '. ');
			} else {
                self::d("preg_match m1=$m[1], m2=$m[2], m3=$m[3], m4=$m[4]");
				$hasFinalStep = $this->addExtraMediaInfo($title, $img, $m);			
			}
		}
	    return array($err, $hasFinalStep);	
	}

	// process the list of videos to make sure we can understand all filenames
	public function addExtraVideoInfo($title, &$videos) {
		$hasFinalStep = false;
		$err = null;
		foreach ($videos as &$vid) {
			$vid['name'] = explode("/", $vid['aws_uri_out']);
			$vid['name'] = end($vid['name']);

			if (!preg_match('@^((.*)-\s*)+?([0-9b]+|final)\.(360p\.' . join('|', WikiVisualTranscoder::$videoExts) . ')$@i', $vid['name'], $m)) {
				$err .= "Filename not in format Name-1.mp4: " . $vid['name'] . ". ";
			} else {
				$hasFinalStep = $this->addExtraMediaInfo($title, $vid, $m);
			}
		}
	    return array($err, $hasFinalStep);	
	}
	
	// looks at a video or image file name and figures out
	// if it has special naming such as final or is a bullet image or video
	// returns true if it is has 'final' in it, false otherwise.. normally it would just be a number
	private function addExtraMediaInfo( $title, &$media, $m ) {
		$hasFinalStep = false;

		// future video title
		$media['first'] = $title->getText();

		// bullet number
		$media['sub'] = null;

		// video extension
		$media['ext'] = strtolower($m[4]);

		$bulletpos = strrpos($m[3], "b");
		if ($bulletpos !== false) {
			$media['step'] = substr($m[3], 0, $bulletpos); //step number
			$media['sub'] = substr($m[3], $bulletpos + 1);
		} else {
			$media['step'] = strtolower($m[3]);  //step number
		}
		
		
		if ($media['step'] == WikiVisualTranscoder::FINALSTR) {
			$media['first'] .= ' '.WikiVisualTranscoder::FINALSTRLBL;
			$hasFinalStep = true;
		} else if ( $media['step'] == 'outro' ) {
			$media['first'] .= ' Summary Outro';
		} else {
			$media['first'] .= ' Step ' . $media['step'];
			if ($media['sub'] !== null) {
				$media['first'] .= "Bullet" . $media['sub'];
			}
		}
		
		return $hasFinalStep;
	}
}
