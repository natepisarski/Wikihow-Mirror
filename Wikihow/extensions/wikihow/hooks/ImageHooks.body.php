<?php

class ImageHooks {

	// AG - method signature changed due to how to check if file exists in lastest MW
	public static function onImageConvertNoScale($image, $params) {
		global $wgIsImageScaler;

		if ( $wgIsImageScaler || Misc::percentileRollout( strtotime( 'June 4, 2018 12pm' ), 3 * 24 * 60 * 60 ) ) {
			// Trevor, 5/15/18 - use conversion when requesting images at their physical sizes so they
			// are treated as thumbnails, ensuring watermarking, compression and transcoding are performed
			if ( $params['physicalWidth'] == $params['clientWidth'] && $params['physicalHeight'] == $params['clientHeight'] ) {
				return false;
			}
		}

		// edge case...if the image will not actually get watermarked because it's too small, just return true
		if (WatermarkSupport::validImageSize($params['physicalWidth'], $params['physicalHeight']) == false) {
			return true;
		}

		// return false here..we want to create the watermarked file!
		// AG - TODO trying to figure out why we check if the file exists here..doesn't
		// seem necessary or should be inverted
		if ( @$params[WatermarkSupport::ADD_WATERMARK] || $image->getRepo()->fileExists($params['dstPath']) ) {
			return false;
		}

		return true;
	}

	public static function onImageConvertComplete($params) {
		global $wgJpegTran, $wgOptiPngCommand, $wgCwebpBinary;

		$dstPath = $params['dstPath'];

		// First, add any watermarks to generated image
		if (@$params[WatermarkSupport::ADD_WATERMARK]) {
			if ( $params['pageId'] ) {
				WatermarkSupport::addTitleBasedWatermark( $dstPath, $dstPath, $params['physicalWidth'], $params['physicalHeight'], $params['pageId'] );
			} elseif ( $params['version'] >= 3 ) {
				WatermarkSupport::addWatermarkV3($dstPath, $dstPath, $params['physicalWidth'], $params['physicalHeight']);
			} else {
				WatermarkSupport::addWatermark($dstPath, $dstPath, $params['physicalWidth'], $params['physicalHeight']);
			}
		}

		$quality = $params['quality'] ?: 80;

		// If we're requesting a WEBP image, we convert to that format using cwebp command
		$isWebp = preg_match('@\.webp$@', $params['dstUrl']) > 0;
		if ( $isWebp ) {
			$tmpDestPath = $dstPath . '.webp';
			$cmd = wfEscapeShellArg($wgCwebpBinary) . ' -q ' . wfEscapeShellArg($quality) . ' ' . wfEscapeShellArg($dstPath) .
				' -o ' . wfEscapeShellArg($tmpDestPath);
			wfDebugLog('imageconvert', __METHOD__ . " [cwebp] $cmd");
			$err = wfShellExec( $cmd, $retval );
			// log $err and $retval here if debugging
			if ( file_exists($tmpDestPath) ) {
				rename($tmpDestPath, $dstPath);
			}
		}

		// Then try to optimize the output image for size while keeping
		// display properties the same by using optipng and jpegtran
		if (@$params['mimeType'] == 'image/jpeg' && file_exists($wgJpegTran) && $wgJpegTran) {
			$cmd = wfEscapeShellArg( $wgJpegTran ) . ' -copy none -outfile ' .
				wfEscapeShellArg( $dstPath ) . ' ' . wfEscapeShellArg( $dstPath ) .
				' >> /tmp/imgopt.log 2>&1';
			wfDebugLog('imageconvert', __METHOD__ . " [jpegoptim] $cmd");
			$before = file_exists($dstPath) ? filesize($dstPath) : 'f';
			wfDebug( __METHOD__.": running jpegtran: $cmd\n");
			$err = wfShellExec( $cmd, $retval );
			$after = file_exists($dstPath) ? filesize($dstPath) : 'f';
			$currentDate = `date`;
			// debugging output
			wfErrorLog(trim($currentDate) . " $cmd b:$before " .
				"a:$after ret:$retval\n", '/tmp/imgopt.log');
		} elseif (@$params['mimeType'] == 'image/png' && file_exists($wgOptiPngCommand) && $wgOptiPngCommand) {
			$cmd = wfEscapeShellArg($wgOptiPngCommand) . " " .
				wfEscapeShellArg($dstPath) . " >> /tmp/imgopt.log 2>&1";
			wfDebugLog('imageconvert', __METHOD__ . " [optipng] $cmd");
			$before = file_exists($dstPath) ? filesize($dstPath) : 'f';
			wfDebug( __METHOD__.": running jpegoptim: $cmd\n");
			$err = wfShellExec( $cmd, $retval );
			$after = file_exists($dstPath) ? filesize($dstPath) : 'f';
			$currentDate = `date`;
			// debugging output
			wfErrorLog(trim($currentDate) . " $cmd b:$before " .
				"a:$after ret:$retval\n", '/tmp/imgopt.log');
		}

		return true;
	}

	public static function onFileTransform($image, &$params) {
		if ( $image->getUser("text")
			&& WatermarkSupport::isWikihowCreator($image->getUser('text'))
			&& (!isset($params[WatermarkSupport::NO_WATERMARK])
				|| $params[WatermarkSupport::NO_WATERMARK] != true))
		{
			$params[WatermarkSupport::ADD_WATERMARK] = true;
		}

		if (!isset($params[WatermarkSupport::FORCE_TRANSFORM])
			|| $params[WatermarkSupport::FORCE_TRANSFORM] != true)
		{
			return true;
		}

		return false;
	}

	public static function onBitmapDoTransformScalerParams($params, &$scalerParams) {
		if ( isset($params[WatermarkSupport::ADD_WATERMARK]) ) {
			 $scalerParams[WatermarkSupport::ADD_WATERMARK] = $params[WatermarkSupport::ADD_WATERMARK];
		}
		if ( isset($params[WatermarkSupport::PAGE_ID]) ) {
			 $scalerParams[WatermarkSupport::PAGE_ID] = $params[WatermarkSupport::PAGE_ID];
		}
		if ( isset($params["version"]) ) {
			$scalerParams["version"] = $params['version'];
			if ( $params['version'] > 3 ) {
				$scalerParams['sharpen'] = true;
				$scalerParams['quality'] = 85;
			}
		}

		if ( isset($params['quality']) ) {
			$scalerParams['quality'] = $params['quality'];
		}

		return true;
	}

	// AG changed the signature of this method to take the rawParams as well as normalised params
	//  so that we can add the crop to the filename if needed (which uses rawParams)
	public static function onFileThumbName($image, $rawParams, $params, &$thumbName) {

		// In our development environment, we don't want to keep a full
		// copy of all wikihow files, so we download them on demand from prod,
		// and on international, we only want the logo without the page title
		global $wgIsDevServer, $wgLanguageCode;
		if ($wgIsDevServer) {
			DevImageHooks::onFileThumbName($image);
		}

		if (!$rawParams) {
			$rawParams = $params;
		}

		if ( is_array( $params ) && isset( $params['mArticleID'] ) && WatermarkSupport::isRestricted( $params['mArticleID'] ) ) {
			$params[WatermarkSupport::NO_WATERMARK] = true;
		}

		if ( is_array( $params ) && isset( $params['mArticleID'] ) && class_exists( 'AlternateDomain' ) && AlternateDomain::onNoBrandingDomain() ) {
			$params[WatermarkSupport::NO_WATERMARK] = true;
		}

		if ( $image->getUser('text')
			&& WatermarkSupport::isWikihowCreator($image->getUser('text'))
			&& isset($params[WatermarkSupport::NO_WATERMARK])
			&& $params[WatermarkSupport::NO_WATERMARK] == true)
		{
			$wm = 'nowatermark-';
			$thumbName = $image->getHandler()->makeParamString( $params ) . '-' . $wm . $image->getName();
		}

		if (isset($params['crop']) && $params['crop'] == 1) {
			// if the requested width was passed in, we have a thumbnail name where the crop width
			// didn't match the "px" width. The crop width is the one used in generating the image,
			// but we need to reflect the requested "px" width in the URL generated by these functions
			// because it must match the input "canonical" url.
			// Example: /images/thumb/8/87/Manage-Fats-and-Sugars-on-the-Volumetrics-Diet-Step-12.jpg/-crop-342-254-300px-nowatermark-Manage-Fats-and-Sugars-on-the-Volumetrics-Diet-Step-12.jpg

			// we set the height param to be '' if the height of 0 or -1 was requested,
			// because the height in the generated URL needs to match this param
			// Example: /images/thumb/1/10/User-Completed-Image-Identify-a-Brown-Recluse-2014.07.09-16.42.16.0.jpg/-crop-200--200px-User-Completed-Image-Identify-a-Brown-Recluse-2014.07.09-16.42.16.0.jpg
			$height = $rawParams['height'] > 0 ? $rawParams['height'] : '';
			if (isset($params['reqwidth']) && $params['reqwidth'] > 0) {
				$wm = isset($params[WatermarkSupport::NO_WATERMARK])
					&& $params[WatermarkSupport::NO_WATERMARK]
						? 'nowatermark-'
						: '';
				$thumbName = "-crop-{$rawParams['width']}-{$height}-"
					. "{$params['reqwidth']}px-"
					. $wm . $image->getName();
			} else {
				$thumbName = "-crop-{$rawParams['width']}-{$rawParams['height']}-" . $thumbName;
			}
		}

		// check if we got a quality hint that we should add
		if ( self::addQualityToThumbName( $params ) ) {
			$quality = $params['quality'];
			if ($quality) {
				$qualityNamePart = 'q' . $quality . '-';
				$thumbName = $qualityNamePart . $thumbName;
			}
		}

		// add any prefixes which affect the watermark like version or aid (for title based watermark)
		$prefix = WatermarkSupport::getThumbPrefix( $image, $params );
		$thumbName = $prefix . $thumbName;

		// when we need a thumbnail in WEBP format instead, we see urls like this:
		// /images/thumb/7/76/Kiss-Step-1-Version-5.jpg/aid2053-728px-Kiss-Step-1-Version-5.jpg.webp
		if ( isset( $params['webp'] ) && $params['webp'] ) {
			$thumbName .= '.webp';
		}

		return true;
	}

	/**
	 * Do we have a quality parameter we can add to the thumbnail name?
	 *
	 * @return bool
	 */
	private static function addQualityToThumbName($params) {
		if ( !$params || !is_array( $params ) || !isset($params['quality']) || !$params['quality'] ) {
			return false;
		}

		return true;
	}

	public static function onConstructImageConvertCommand($params, &$quality) {
		if ($params['quality']) {
			$isWebp = preg_match('@\.webp$@', $params['dstUrl']) > 0;

			$quality = ['-quality', $isWebp ? 95 : $params['quality']];
		}

		return true;
	}

	public static function onThumbnailBeforeProduceHTML($thumbnailImage, &$attribs, $linkAttribs) {
		if ($attribs && is_array($attribs) && !empty($attribs['src'])) {
			if (!empty($attribs['class'])) {
				$attribs['class'] .= ' whcdn';
			} else {
				$attribs['class'] = 'whcdn';
			}
			// NOTE, this used to be: $attribs['src'] = wfGetPad($attribs['src']);
			// but for the HTTPS rollout, we no longer want to cache the results
			// of wfGetPad in memcache, since it's context dependent. We use
			// phpQuery to match all "whcdn" classes and call wfGetPad as a
			// post-processing step.
		}
		return true;
	}

	// aaron - linker hook before images are produced
	public static function onImageBeforeProduceHTML(&$dummy, &$title, &$file, &$frameParams, &$handlerParams, &$time, &$res) {
		if (!$file) {
			MWDebug::warning("could not load file from title: $title");
			return false;
		}

		return true;

	}

	/*
	 * Parse the first part of a thumbnail name such as: "670px", "crop-127-100-127px",
	 * or "-nowatermark-670px". ImageHooks::onFileThumbName generates these thumbnail
	 * prefixes and this method parses them at a later step when they are requested.
	 * @param $str thumbnail name with the file name removed so only parameters remain
	 * @param $params this is set by the current function so used as a sort of return value
	 * @return boolean always returns true as to follow the convention for hooks
	 */
	public static function onImageHandlerParseParamString( $str, &$params ) {
		// the parameters are all separated by - and contain any values next to their name
		// the only exception for now is the crop param which is followed by the height and width
		$result = array();
		$parts = explode('-', $str);
		while ( count( $parts ) ) {
			$param = array_shift( $parts );
			if ( !$param ) {
				continue;
			}
			// if we have a crop param then grab the next two values for width and height
			if ( $param == "crop" ) {
				$result['width'] = ( int )array_shift( $parts );
				$result['height'] = ( int )array_shift( $parts );
				$result['crop'] = 1;
			} elseif ( $param == "nowatermark" ) {
				$result[WatermarkSupport::NO_WATERMARK] = true;
			} elseif ( substr( $param, 0, 1 ) == 'v' ) {
				$result['version'] = substr( $param, 1 );
			} elseif ( substr( $param, 0, 1 ) == 'q' ) {
				$result['quality'] = ( int )substr( $param, 1 );
			} elseif ( substr( $param, 0, 3 ) == 'aid' ) {
				$result['pageId'] = $param;
			} elseif ( substr( $param, -2, 2 ) == 'px' ) {
				$width = ( int )substr( $param, 0, strlen( $param ) - 2 );
				if ( $result['crop'] == 1 && $result['width'] > 10 ) {
					$result['reqwidth'] = $width;
				} else {
					$result['width'] = $width;
				}
			}
		}

		if ( $result ) {
			$params = $result;
		}

		return true;
	}

	private static function parseQualityParameter(&$parts, &$normalizedParams) {
		// check for quality
		if ( count($parts) && preg_match('/^q\d+$/', $parts[0])) {
			$quality = (int)substr(array_shift($parts), 1);
			if ($quality) {
				$normalizedParams['quality'] = $quality;
			}
		}
	}

	// Detect whether an otherwise normal thumbnail request ended with .webp. For ex:
	// /images/thumb/7/76/Kiss-Step-1-Version-5.jpg/aid2053-728px-Kiss-Step-1-Version-5.jpg.webp
	public static function onExtractThumbParameters($thumbname, &$params) {
		if ( preg_match('@\.webp$@', $thumbname) ) {
			$params['webp'] = 1;
		}
		return true;
	}

	public static function onUnitTestsList( &$files ) {
		global $IP;
		$files = array_merge( $files, glob( __DIR__ . '/tests/*Test.php' ) );
		return true;
	}
}

