<?php

class WikihowArticleStream extends ContextSource {

	var $viewer, $articles, $current;
	var $cache;

	const CHUNK_SIZE = 8;

	const DESKTOP = 0;
	const MOBILE = 1;
	const TABLET = 2;

	public function __construct($articleStream, IContextSource $context, $startArticle = 0) {
		$this->setContext($context);

		$this->viewer = $articleStream;
		$this->viewer->clearState();
		$this->viewer->doQuery();

		if (isset($this->viewer->articles_fa) && is_array($this->viewer->articles_fa)) {
			$this->articles = array_merge($this->viewer->articles_fa, $this->viewer->articles);
		} else {
			$this->articles = $this->viewer->articles;
		}
		$this->current = $startArticle;
		$this->cache = array();
	}

	public function getStreamPosition() {
		return $this->current;
	}

	// Returns either CHUNK_SIZE articles, or however many are left to consume.
	// Each article has a title object, a representative image, and dimensions of
	// that image.
	private function peekNext() {
		// Fill more articles into cache
		for ($i = $this->current + count($this->cache);
			 $i < count($this->articles)
			 && $i < $this->current + self::CHUNK_SIZE;
			 $i++)
		{
			$link = $this->articles[$i];
			if (preg_match('@title="([^"]+)"@', $link, $matches)) {
				$title = Title::newFromText($matches[1]);
				if ($title) {
					$image = Wikitext::getTitleImage($title);

					// Make sure there aren't any issues with the image.
					//Filenames with question mark characters seem to cause some problems
					// Animatd gifs also cause problems.  Just use the default image if image is a gif
					if (!($image && $image->getPath() && strpos($image->getPath(), "?") === false)
						|| preg_match("@\.gif$@", $image->getPath())) {
						$image = Wikitext::getDefaultTitleImage($title);
					}

					$this->cache[] = array('title' => $title, 'image' => $image);
				}
			}
		}
		return $this->cache;
	}

	// Consume n articles from the stream
	private function consume($consumed) {
		$n = count($consumed);
		$this->current += $n;
		if ($this->cache) {
			$this->cache = array_slice($this->cache, $n);
		}
	}

	public function getChunks($numChunks, $singleWidth, $singleSpacing, $singleHeight, $device = WikihowArticleStream::DESKTOP) {
		$html = '';

		switch ($device) {
			case WikihowArticleStream::MOBILE:
				$rowWidth = 2;
				break;
			default:
				$rowWidth = 4;
				break;
		}

		while ($numChunks--) {
			$articles = $this->peekNext();
			if ($articles) {
				list($layout, $consumed) = WikihowBlockLayout::choose($articles, $device);
				$this->consume($consumed);

				$across1 = 0;
				$doneAcross1 = false;
				$across2 = 0;
				$html .= "<table cellpadding='0' cellspacing='0' width='100%'><tr>";
				foreach ($layout as $item) {
					if (!isset($item['title']) || !$item['title']) {
						//$html .= "- image=(null) dims={$item['dims']}<br>\n";
					} else {
						$dims = explode("x", $item['dims']);
						$xUnits = intval($dims[0]);
						$yUnits = intval($dims[1]);
						$html .= "<td colspan='{$xUnits}' rowspan='{$yUnits}' class='image_map'>";
						$html .= $this->getArticleThumbWithPath($item['title'], $xUnits*$singleWidth + $singleSpacing*($xUnits-1), $yUnits*$singleHeight + $singleSpacing*($yUnits-1), $item['image']);
						$html .= "</td>";

						if ($across1 < $rowWidth) {
							//we're still on the first row
							$across1 += $xUnits;
							if ($yUnits > 1)
								$across2 += $xUnits;
						}
						else {
							//we're on the second row
							$across2 += $xUnits;
						}

						if ($across1 == $rowWidth && !$doneAcross1) {
							$html .= "</tr><tr>";
							$doneAcross1 = true;
						}
					}
				}
				$html .= "</table>";
			}
		}
		if ($html) $html .= '<script>gScrollContext = ' . $this->current . ';</script>';
		return $html;
	}

	public function getFlatChunk($numChunks, $singleWidth, $singleSpacing, $singleHeight, $device = WikihowArticleStream::DESKTOP) {
		$html = '';

		switch ($device) {
			case WikihowArticleStream::MOBILE:
				$rowWidth = 2;
				break;
			default:
				$rowWidth = 4;
				break;
		}

		while ($numChunks--) {
			$articles = $this->peekNext();
			if ($articles) {
				list($layout, $consumed) = WikihowBlockLayout::choose($articles, $device);
				$this->consume($consumed);

				$across1 = 0;
				$doneAcross1 = false;
				$across2 = 0;
				$html .= "<table cellpadding='0' cellspacing='0' width='100%'><tr>";
				foreach ($layout as $item) {
					if (!isset($item['title']) || !$item['title']) {
						//$html .= "- image=(null) dims={$item['dims']}<br>\n";
					} else {
						$dims = explode("x", $item['dims']);
						$xUnits = intval($dims[0]);
						$yUnits = intval($dims[1]);
						$html .= "<td colspan='{$xUnits}' rowspan='{$yUnits}' class='image_map'>";
						$html .= $this->getArticleThumbWithPath($item['title'], $xUnits*$singleWidth + $singleSpacing*($xUnits-1), $yUnits*$singleHeight + $singleSpacing*($yUnits-1), $item['image']);
						$html .= "</td>";

						if ($across1 < $rowWidth) {
							//we're still on the first row
							$across1 += $xUnits;
							if ($yUnits > 1)
								$across2 += $xUnits;
						}
						else {
							//we're on the second row
							$across2 += $xUnits;
						}

						if ($across1 == $rowWidth && !$doneAcross1) {
							$html .= "</tr><tr>";
							$doneAcross1 = true;
						}
					}
				}
				$html .= "</table>";
			}
		}
		//if ($html) $html .= '<script>gScrollContext = ' . $this->current . ';</script>';
		return $html;
	}

	public function getArticleThumbWithPath($t, $width, $height, $file) {
		global $wgContLang, $wgLanguageCode, $wgTitle;

        $mediaSrc = '';
        // check if a video thumb is available
        $videoUrl = ArticleMetaInfo::getVideoSrc( $t );
        $sourceWidth = $file->getWidth();
        $sourceHeight = $file->getHeight();
        $xScale = ($sourceWidth == 0) ? $xScale = 1 : $width/$sourceWidth;
        if ( $height > $xScale*$sourceHeight ) {
            $heightPreference = true;
        } else {
            $heightPreference = false;
        }
        $thumb = WatermarkSupport::getUnwatermarkedThumbnail($file, $width, $height, true, true, $heightPreference);
        $thumbSrc = wfGetPad( $thumb->getUrl() );
        //$mediaElement = Html::element( 'img', ['src' => $thumbSrc ] );
        $attributes = ['src' => $thumbSrc];
        $mediaElement = Misc::getMediaScrollLoadHtml( 'img', $attributes );

        $thumbnailClasses = array( 'thumbnail' );
        if ( $height >= 254 ) {
            $thumbnailClasses[] = 'd-height';
        } else {
            $thumbnailClasses[] = 's-height';
        }
        if ( $width >= 254 ) {
            $thumbnailClasses[] = 'd-width';
        } else {
            $thumbnailClasses[] = 's-width';
        }
        //removed the fixed width for now
        if ( $videoUrl && $wgTitle->isMainPage() ) {
			$attributes = [
				'src' => $videoUrl,
				'data-poster' => $thumbSrc,
			];
            $mediaElement = Misc::getMediaScrollLoadHtml( 'video', $attributes );
        }
		$articleName = $t->getText();
		if ($wgLanguageCode == "zh") {
			$articleName = $wgContLang->convert($articleName);
		}

		$textBlock = '';

		// Show how-to message for main namespace articles
		// but prefixed title with no lead for other namespaces

		if ( $t->inNamespace( NS_MAIN ) ) {
			$msg = wfMessage('howto_prefix');
			$howToPrefix = $msg->exists() ? ($msg->text() . '<br>') : '';
			$howToSuffix = wfMessage('howto_suffix')->showIfExists();
			$textBlock =  $howToPrefix . '<span>' . $articleName . $howToSuffix . '</span>';
		} else {
			$textBlock = "<br/><span>" . $t->getFullText() . "</span>";
		}

        $vars = array(
            'thumbnailClasses' => implode( ' ', $thumbnailClasses ),
            'titleUrl' => $t->getFullUrl(),
            'mediaElement' => $mediaElement,
            'textBlock' => $textBlock,
        );

        $tmpl = new EasyTemplate( __DIR__ );
        $tmpl->set_vars($vars);
        $html = $tmpl->execute('ArticleViewerThumb.tmpl.php');

		return $html;
	}
}

class WikihowBlockLayout {

	const SQUARE_WIDTH_PIXELS = 240;
	const SQUARE_HEIGHT_PIXELS = 175;
	const SQUARE_WHITE_SPACE_PIXELS = 10;

	const MAX_TALLER_PEEK = 5;

	private static function getWideWidth() {
		$desiredWidth = 2 * self::SQUARE_WIDTH_PIXELS + self::SQUARE_WHITE_SPACE_PIXELS;
		return $desiredWidth;
	}

	private static function getTallHeight() {
		$desiredHeight = 2 * self::SQUARE_HEIGHT_PIXELS + self::SQUARE_WHITE_SPACE_PIXELS;
	}

	private static function getPossibleTallFormats() {
		$possibleTallFormats = array(
			array('1x2', '2x1',    '', '1x1',
				'', '1x1', '1x1', '1x1'),

			array('2x2',    '', '1x1', '1x2',
				'',    '', '1x1',    ''),

			array('1x1', '2x1', '',    '1x2',
				'1x1', '1x1', '1x1',    ''),

			array('1x1', '1x2', '1x1', '1x1',
				'1x1',    '', '2x1',    ''),
		);
		return $possibleTallFormats;
	}

	private static function getPossibleMobileFormats() {
		$possibleMobileFormats = array(
			array('1x1', '1x1', '2x1', ''),
		);

		return $possibleMobileFormats;
	}

	private static function getPossibleWideFormats() {
		$possibleWideFormats = array(
			array('2x1',    '', '2x2',    '',
				'1x1', '1x1',    '',    ''),

			array('2x1',    '', '2x2',    '',
				'1x1', '1x1',    '',    ''),

			array('1x1', '2x2',    '', '1x1',
				'1x1',    '',    '', '1x1'),

			array('1x1', '2x2',    '', '1x1',
				'1x1',    '',    '', '1x1'),

			array('1x1', '2x1',    '', '1x1',
				'1x1', '1x1', '1x1', '1x1'),

			array('1x1', '2x1',    '', '1x1',
				'1x1', '1x1',    '1x1', '1x1'),

			array('2x2',    '', '1x1', '1x1',
				'',    '', '2x1',    ''),

			array('2x2',    '', '2x1',    '',
				'',    '', '1x1', '1x1'),

			array('2x1',    '', '1x1', '1x1',
				'1x1', '1x1', '2x1',    ''),
		);

		return $possibleWideFormats;
	}


	// We consider an image "taller" if its height is 40% bigger than
	// its width
	private static function isTallerImage($image) {
		$width = $image->getWidth();
		$height = $image->getHeight();
		return $height >= 1.4 * $width;
	}

	// A REALLY rough way of getting a consistent number from a
	// sha1 string
	private static function stringToNumber($str) {
		$num = 0;
		foreach (str_split($str) as $char) {
			$num += ord($char);
		}
		return $num;
	}

	private static function sortByFlatness($articles, $consumed) {
		$desiredWidth = self::getWideWidth();
		$desiredHeight = self::SQUARE_HEIGHT_PIXELS;

		$arr = array();
		foreach ($articles as $i => $article) {
			if (in_array($i, $consumed)) continue;
			$arr[] = array('i' => $i, 'a' => $article['image']);
		}

		$sortFunc = function($a, $b)
		use($desiredWidth, $desiredHeight)
		{
			$aw = $a['a']->getWidth();
			$bw = $b['a']->getWidth();
			$ah = $a['a']->getHeight();
			$bh = $b['a']->getHeight();
			if ($aw >= $desiredWidth && $bw >= $desiredWidth
				&& $ah >= $desiredHeight && $bh >= $desiredHeight)
			{
				$ar = $aw / $a['a']->getHeight();
				$br = $bw / $b['a']->getHeight();
				return $ar - $br;
			}
			if ($aw < $desiredWidth) return 1;
			if ($bw < $desiredWidth) return -1;
			if ($ah < $desiredHeight) return 1;
			if ($bh < $desiredHeight) return -1;
			return $aw - $bw;
		};
		uasort($arr, $sortFunc);
		$keys = array();
		foreach ($arr as $a) {
			$keys[] = $a['i'];
		}
		return $keys;
	}

	private static function findFirstTall($articles) {
		$desiredWidth = self::SQUARE_WIDTH_PIXELS;
		$desiredHeight = self::getTallHeight();

		foreach ($articles as $i => $article) {
			// Never peak more than this many elements ahead
			// because it could lead to out-of-order unconsumed
			// articles.
			if ($i > self::MAX_TALLER_PEEK - 1) break;

			$image = $article['image'];
			if ($image->getHeight() >= $desiredHeight
				&& $image->getWidth() >= $desiredWidth
				&& self::isTallerImage($image))
			{
				return $i;
			}
		}
		return -1;
	}

	private static function findFirstLarge(&$articles, $consumed) {
		$desiredHeight = self::getTallHeight();
		$desiredWidth = self::getWideWidth();
		foreach ($articles as $i => $article) {
			if (in_array($i, $consumed)) continue;
			$image = $article['image'];
			if ($image->getWidth() >= $desiredWidth && $image->getHeight() >= $desiredHeight) {
				return $i;
			}
		}
		return -1;
	}

	public static function choose($articles, $device) {
		global $wgTitle;
		$consumed = array();
		$consumeArticle = function(&$spot, $i)
		use(&$articles, &$consumed)
		{
			$spot = array('title' => @$articles[$i]['title'],
				'image' => @$articles[$i]['image'],
				'dims' => $spot);
			$consumed[] = $i;
		};

		$taller = self::findFirstTall($articles);
		$large = self::findFirstLarge($articles, $consumed);

		if ($device == WikihowArticleStream::MOBILE) {
			$possibleFormats = self::getPossibleMobileFormats();
		}
		else {
			// Get formats based on whether there is an image that could
			// work as a 1x2 image
			// Disable Tall formats for now since they are causing problems
			$possibleFormats = self::getPossibleWideFormats();
		}

		$seed = self::stringToNumber($articles[0]['image']->getSha1());
		mt_srand($seed);
		$pos = mt_rand(0, count($possibleFormats) - 1);
		$format = $possibleFormats[$pos];

		// Only consider the first count($articles) - $trim articles
		// from the stream because we don't want to leave out of order
		// elements unconsumed.
		$spots = 0;
		foreach ($format as $spot) {
			if ($spot) $spots++;
		}
		if (count($articles) > $spots) {
			array_splice($articles, $spots);
		}

		// If we have a suitable 1x2 image, use it first -- there is at
		// most one 1x2 image per format
		if ($taller >= 0) {
			foreach ($format as &$spot) {
				// find the taller image spot
				if ($spot == '1x2') {
					$consumeArticle($spot, $taller);
					break;
				}
			}
		}

		// If we have a suitable 2x2 image, use that next -- this is at
		// most one 2x2 image per format
		if ($large >= 0) {
			foreach ($format as &$spot) {
				if (is_string($spot) && $spot == '2x2') {
					$consumeArticle($spot, $large);
					break;
				}
			}
		}

		// Next, sort the images by "flatness" to put the appropriate
		// images into 2x1 slots. There can be multiple 2x1 slots in a
		// format.
		$flatness = self::sortByFlatness($articles, $consumed);
		foreach ($format as &$spot) {
			if (count($flatness) == 0) break;
			if (is_string($spot) && $spot == '2x1') {
				$flat = array_shift($flatness);
				// Make the default image the wide one
				if (preg_match("@Default_wikihow_(green|blue)(_intl)?.png@", $articles[$flat]['image']->getPath())) {
					$articles[$flat]['image'] = Wikitext::getDefaultTitleImage($articles[$flat]['title'], true);
				}
				$consumeArticle($spot, $flat);
			}
		}

		// Fill the rest of the spots with the rest of the articles --
		// this should only be 1x1 elements now.
		$i = 0;
		foreach ($format as &$spot) {
			// skip already consumed articles
			while (in_array($i, $consumed)) $i++;

			if (!$spot || !is_string($spot)) {
				// nothing
			} elseif ($i < count($articles)) {
				$consumeArticle($spot, $i);
				$i++;
			} else {
				$spot = array('title' => null, 'image' => null, 'dims' => $spot);
			}
		}

		return array($format, $consumed);
	}

}
