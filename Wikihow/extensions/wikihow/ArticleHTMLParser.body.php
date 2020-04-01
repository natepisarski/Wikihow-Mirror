<?php

/**
 * Static functions that can be used to pull out parts of an article
 * for use in mobile html builder and ios api
 *
 */
class ArticleHTMLParser {

	const INDEX_THUMB_MAX_WIDTH = 600;
	const INDEX_THUMB_MAX_HEIGHT = 600;
	const INDEX_THUMB_LARGE_MAX_WIDTH = 1200;
	const INDEX_THUMB_LARGE_MAX_HEIGHT = 1200;

	private static function getImageObj($imgName) {
		if (!$imgName) return null;

		// Remove dashes and namespace prefix from image name
		$tmpTitle = Title::newFromText($imgName);
		if (!$tmpTitle) {
			// Retry load image name after urldecode()
			$imgName = urldecode($imgName);
			$tmpTitle = Title::newFromText($imgName);
		}

		if (!$tmpTitle) return null;
		$title = Title::newFromText($tmpTitle->getText(), NS_IMAGE);

		$image = null;
		if ($title) {
			$image = RepoGroup::singleton()->findFile($title);
		}

		return $image;
	}

	public static function removeEmptyNodes(&$stepNode) {
		foreach (pq('*', $stepNode) as $node) {
			if ($node->nodeName == 'img' || pq('img', $node)->length) continue;
			if ($node->nodeName == 'video' || pq('video', $node)->length) continue;
			if ($node->nodeName == 'source' || pq('source', $node)->length) continue;
			$pq = pq($node);
			$text = $pq->text();
			$class = $pq->attr('class');
			if ($class == 'template') {
				$text = trim($text);
			}
			if (!$text) {
				$pq->remove();
			}
		}
	}

	public static function getThumbnailDimensions($thumb) {
		$result = array();
		if (!$thumb || $thumb->fileIsSource()) {
			$result['width'] = 0;
			$result['height'] = 0;
		} else {
			$result['width'] = intval($thumb->getWidth());
			$result['height'] = intval($thumb->getHeight());
		}
		return $result;
	}

	public static function unparse_url($parsed_url) {
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

		return "$scheme$user$pass$host$port$path$query$fragment";
	}

	public static function uriencode($url) {
		$url = urldecode($url);
		$parsed = parse_url($url);
		$parsed['path'] = implode("/", array_map("rawurlencode", explode("/", $parsed['path'])));
		return self::unparse_url($parsed);
	}

	public static function getImageDetails($image) {
		global $wgIsDevServer;

		$urlPrefix = "";
		if ( $wgIsDevServer ) {
		    $urlPrefix = "https://www.wikihow.com";
		}
		$result = array('obj' => '', 'url' => '');
		if ($image) {
			$result['obj'] = $image;
			$thumb = WatermarkSupport::getUnwatermarkedThumbnail($image, self::INDEX_THUMB_MAX_WIDTH);
			if ($thumb && !($thumb instanceof MediaTransformError)) {
				$result['url'] = self::uriencode(wfGetPad($urlPrefix.$thumb->getUrl()));
				$dim = self::getThumbnailDimensions($thumb);
				$result['width'] = $dim['width'];
				$result['height'] = $dim['height'];
			}

			$thumb = WatermarkSupport::getUnwatermarkedThumbnail($image, self::INDEX_THUMB_LARGE_MAX_WIDTH);
			if ($thumb && !($thumb instanceof MediaTransformError)) {
				$largeUrl = self::uriencode(wfGetPad($thumb->getUrl()));
				if ($largeUrl != $result['url']) {
					$result['large'] = self::uriencode(wfGetPad($urlPrefix.$thumb->getUrl()));
					$dim = self::getThumbnailDimensions($thumb);
					$result['large_width'] = $dim['width'];
					$result['large_height'] = $dim['height'];
				}
			}

			$original = self::uriencode(wfGetPad($image->url));
			if ($original != $largeUrl) {
				$result['original'] = self::uriencode(wfGetPad($urlPrefix.$image->getUrl()));
				$result['original_width'] = intval($image->getWidth());
				$result['original_height'] = intval($image->getHeight());
			}
		}
		return $result;
	}


	public static function pullOutImage(&$imgNode, $imageNsText, $remove = true, $getImageDetails = true) {
		$imgUrl = $imgNode->attr('href');
		$imgName = preg_replace('@^/(Image|' . $imageNsText . '):@', '', $imgUrl);
		$image = self::getImageObj($imgName);

		if ($image && $getImageDetails) {
			$image = self::getImageDetails($image);
		}

		// Remove image child now
		if ($remove) {
			$imgNode->parents('.mwimg:first')->remove();
		}

		return $image;
	}

	// create a random div to be used on the page..makes sure it does not already exist
	private static function randomDivId($depth=0) {
		if ($depth > 10) {
			 throw new MWException("Call to ".__METHOD__." cannot find div that does not exist");
		}

		for ($i = 0; $i < 7; $i++) {
			$id = chr(97 + mt_rand(0, 25));
		}

		// make sure the div with this id doesn't already exist
		$node = pq("#$id");

		if ($node->length() > 0 || empty($id)) {
			return self::randomDivId($depth+1);
		}
		return $id;
	}

	// processes the intro section but will not create any thumbnails
	public function processMobileIntro($imageNsText) {
		return self::processIntro($imageNsText, false);
	}

	// processes a section type before a specified next element
	// used for processing the intro
	// if $processImage is true it will also create thumbnails of the intro image if applicable
	public static function processIntro($imageNsText, $processImage = true) {
		//remove any TOC that will mess up our h2 logic
		if (pq('div#toc')->length) {
			pq('div#toc')->remove();
		}

		// the intro is before the first h2
		$before = "h2";

		$result = array('html'=>'');
		$id = self::randomDivId();

		$first = pq("$before:first");
		if (pq($first)->length() > 0) {
			pq($first)->prevAll()->reverse()->wrapAll("<div id='".$id."' class='section'></div>");
		}

		// only allow intro images if they have introimage class type
		pq("#$id .mwimg:not(.introimage)")->remove();

		// find any now valid intro images
		$imgNode = pq("#$id a.image:first");
		$image = self::pullOutImage($imgNode, $imageNsText, true, $processImage);

		// see if first child is just one containing div if so remove it
		$node = pq("#$id");
		$children = $node->children();
		if ($children->length() == 1 && $children->is('div')) {
			$node = $children;
		}

		// clean up and set the html
		self::removeEmptyNodes($node);
		$html = trim($node->html());

		// remove our intro node
		pq("#$id")->remove();

		if ($html) {
			$result['html'] = $html;
		}
		if ($image) {
			$result['image'] = $image;
		}

		return $result;
	}
}
