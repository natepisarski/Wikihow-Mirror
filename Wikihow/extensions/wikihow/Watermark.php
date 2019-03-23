<?php

if ( !defined('MEDIAWIKI') ) die();

class WatermarkSupport {
	const NO_WATERMARK = "noWatermark";
	const ADD_WATERMARK = "addWatermark";
	const PAGE_ID = "pageId";
	const FORCE_TRANSFORM = "forcetransform";
	const CONFIG_STORAGE_EXCLUDE_KEY = "watermark-exclude";

	public static function isWikihowCreator($userName) {
		global $wgWatermarkUsers;
		return (is_array($wgWatermarkUsers) && in_array($userName, $wgWatermarkUsers));
	}

	// NOTE: Reuben deprecated the $heightPreference param -- it no longer does anything
	public static function getUnwatermarkedThumbnail( $image, $width, $height=-1, $render = true, $crop = false, $heightPreference = false ) {
		$params = array( 'width' => $width );
		if ( $height != -1 ) {
			$params['height'] = $height;
		}
		if ($crop) {
			$params['crop'] = 1;
		}
		$params[self::NO_WATERMARK] = true;
		$params['heightPreference'] = $heightPreference;
		// NOTE: Reuben removed use of the RENDER_NOW param because it makes no
		// effect if not using the transformVia404 Mediawiki functionality
		//$flags = $render ? File::RENDER_NOW : 0;
		$flags = 0;
		return $image->transform( $params, $flags );
	}

	public static function validImageSize($width, $height) {
		if ($width < 250 || $height < 169) {
			return false;
		}
		return true;
	}

	public static function isCMYK($srcPath) {
		global $wgImageMagickIdentifyCommand;

		$cmd = wfEscapeShellArg($wgImageMagickIdentifyCommand) . " -format '%r' ".wfEscapeShellArg($srcPath)." | grep CMY";
		wfShellExec( $cmd, $retval );
		return $retval == 0;
	}

	/**
	 * Add a watermark to an image which includes the title name of the page it appears on
	 *
	 * @param string $srcPath path to image
	 * @param string $dstPath path to final output image
	 * @param int $width width of image
	 * @param int $height height of image
	 * @param int $pageId the page id of the title to put on the image
	 * @param int $scale the scale of the watermark in percent on top of the image (default is 15)
	 */
	public static function addTitleBasedWatermark( $srcPath, $dstPath, $width, $height, $pageId, $scale = 15, $dissolve = 70) {
		global $IP, $wgImageMagickConvertCommand, $wgImageMagickCompositeCommand;

		// do not add a watermark if the image is too small
		if (self::validImageSize($width, $height) == false) {
			return;
		}

		$cmd =  "";
		// make sure image is rgb format so the watermark applies correctly
		if (self::isCMYK($srcPath)) {
			$cmd = wfEscapeShellArg($wgImageMagickConvertCommand)." ".
					wfEscapeShellArg($srcPath)." ".
					"-colorspace RGB ".
					wfEscapeShellArg($dstPath).";";
			$srcPath = $dstPath;
		}

		// regular wikihow dark green
		$green="#93b874";
		$color = $green;

		$wm = $IP.'/skins/WikiHow/images/WH_logo.svg';
		$font = $IP.'/skins/WikiHow/fonts/helvetica-neue-bold.ttf';

		$name = Title::nameOf( substr( $pageId, 3 ) );
		$label = "to ".str_replace( "-", " ", $name );
		$label = wfEscapeShellArg($label);

		$dissolveSetting = "";
		if ( $dissolve < 100 ) {
			$dissolveSetting = "-dissolve $dissolve";
		}

		$cmd = $cmd . wfEscapeShellArg( $wgImageMagickConvertCommand ) .
			" \( -size 118x148 xc:none -fill '$color' -quality 100 -draw \"polygon 118,0 118,148 0,148\" +append \) ".
			"\( -bordercolor '$color' -border 0x36 -quality 100 -background '$color' ". wfEscapeShellArg($wm) . " +append \) ".
			"\( -border 28x0 -splice 0x23 -font " . wfEscapeShellArg($font) . " -size x110 -pointsize 90 -fill white -quality 100 label:".$label." +append \) ".
			"-background '$color' +append -quality 100 -scale $scale% ".
			"miff:- | " . wfEscapeShellArg($wgImageMagickCompositeCommand) . " $dissolveSetting -gravity southeast -sampling-factor 4:2:0 - " . wfEscapeShellArg( $srcPath )  . " " .
			wfEscapeShellArg( $dstPath ) . " 2>&1";

		wfDebugLog('imageconvert', __METHOD__ . " [imagemagick] $cmd");
		$before = file_exists($dstPath) ? filesize($dstPath) : 'f';
		wfDebug( __METHOD__.": running ImageMagick: $cmd\n");
		$err = wfShellExec( $cmd, $retval );
		$after = file_exists($dstPath) ? filesize($dstPath) : 'f';
		$currentDate = `date`;
		wfErrorLog(trim($currentDate) . " $cmd b:$before a:$after\n", '/tmp/watermark.log');
	}

	public static function addWatermark($srcPath, $dstPath, $width, $height) {
		global $IP, $wgImageMagickConvertCommand, $wgImageMagickCompositeCommand;

		// do not add a watermark if the image is too small
		if (self::validImageSize($width, $height) == false) {
			return;
		}

		$wm = $IP.'/skins/WikiHow/images/watermark.svg';
		$watermarkWidth = 1074.447;
		$targetWidth = $width / 8;
		$density = 72 * $targetWidth / $watermarkWidth;

		// we have a lower limit on density so the watermark is readable
		if ($density < 4.0) {
			$density = 4.0;
		}

		$cmd =  "";
		// make sure image is rgb format so the watermark applies correctly
		if (self::isCMYK($srcPath)) {
			$cmd = wfEscapeShellArg($wgImageMagickConvertCommand)." ".
					wfEscapeShellArg($srcPath)." ".
					"-colorspace RGB ".
					wfEscapeShellArg($dstPath).";";
			$srcPath = $dstPath;
		}

		// We now have a newer version of ImageMagick (version 6.7.7+), which
		// requires an additional param to do alpha blending.
		$imageMagick5678param = " -blend 50";

		$cmd = $cmd . wfEscapeShellArg($wgImageMagickConvertCommand) . " -density $density -background none " . wfEscapeShellArg($wm) .
				" miff:- | " . wfEscapeShellArg($wgImageMagickCompositeCommand) . $imageMagick5678param .
				" -gravity southeast -quality 100 -geometry +8+10 - " . wfEscapeShellArg($srcPath) . " " .
				wfEscapeShellArg($dstPath) . " 2>&1";
		wfDebugLog('imageconvert', __METHOD__ . " [imagemagick] $cmd");

		$before = file_exists($dstPath) ? filesize($dstPath) : 'f';
		wfDebug( __METHOD__.": running ImageMagick: $cmd\n");
		$err = wfShellExec( $cmd, $retval );
		$after = file_exists($dstPath) ? filesize($dstPath) : 'f';
		$currentDate = `date`;
		wfErrorLog(trim($currentDate) . " $cmd b:$before a:$after\n", '/tmp/watermark.log');
	}

	// adds version 3 watermark to the image
	public static function addWatermarkV3( $srcPath, $dstPath, $width, $height, $scale = 15, $dissolve = 70) {
		global $IP, $wgImageMagickConvertCommand, $wgImageMagickCompositeCommand;

		// do not add a watermark if the image is too small
		if (self::validImageSize($width, $height) == false) {
			return;
		}

		$cmd =  "";
		// make sure image is rgb format so the watermark applies correctly
		if (self::isCMYK($srcPath)) {
			$cmd = wfEscapeShellArg($wgImageMagickConvertCommand)." ".
					wfEscapeShellArg($srcPath)." ".
					"-colorspace RGB ".
					wfEscapeShellArg($dstPath).";";
			$srcPath = $dstPath;
		}

		// regular wikihow dark green
		$green="#93b874";
		$color = $green;

		$wm = $IP.'/skins/WikiHow/images/WH_logo.svg';

		$dissolveSetting = "";
		if ( $dissolve < 100 ) {
			$dissolveSetting = "-dissolve $dissolve";
		}

		$cmd = $cmd . wfEscapeShellArg( $wgImageMagickConvertCommand ) .
			" \( -size 118x148 xc:none -fill '$color' -quality 100 -draw \"polygon 118,0 118,148 0,148\" +append \) ".
			"\( -bordercolor '$color' -border 30x36 -quality 100 -background '$color' ". wfEscapeShellArg($wm) . " +append \) ".
			"-background '$color' +append -quality 100 -scale $scale% ".
			"miff:- | " . wfEscapeShellArg($wgImageMagickCompositeCommand) . " $dissolveSetting -gravity southeast -sampling-factor 4:2:0 - " . wfEscapeShellArg( $srcPath )  . " " .
			wfEscapeShellArg( $dstPath ) . " 2>&1";

		wfDebugLog('imageconvert', __METHOD__ . " [imagemagick] $cmd");
		$before = file_exists($dstPath) ? filesize($dstPath) : 'f';
		wfDebug( __METHOD__.": running ImageMagick: $cmd\n");
		$err = wfShellExec( $cmd, $retval );
		$after = file_exists($dstPath) ? filesize($dstPath) : 'f';
		$currentDate = `date`;
		wfErrorLog(trim($currentDate) . " $cmd b:$before a:$after\n", '/tmp/watermark.log');
	}


	/**
	 * checks if this image is on a list which restricts watermarks
	 *
	 * @param $params the image transform params
	 * @return bool| true if the article id param exists and it is on the exclude list
	 */
	public static function isRestricted( $pageId ) {
		$excluded = ArticleTagList::hasTag( self::CONFIG_STORAGE_EXCLUDE_KEY, $pageId );
		return $excluded;
	}

	/**
	 * returns a prefix to prepend to the thumbnail name we are generating
	 * or empty string if none should be added
	 *
	 * @param $image the image we will be making a thumbmail from
	 * @param $params the image transform params
	 * @return string | the prefix to prepend to the thumb name
	 */
	public static function getThumbPrefix( $image, $params ) {
		global $wgLanguageCode;

		$prefix = '';
		// make sure we have params to work with
		if ( !$params || !is_array( $params ) ) {
			return "";
		}
		// check if this is coming from thumb.php and coming from a 404 request to create
		// params from a thumbnail file name
		if ( isset( $params['rel404'] ) ) {
			if ( strstr( $params['rel404'], $params['pageId'] ) !== false ) {
				$prefix .= $params['pageId'] . '-';
			}
			if ( isset( $params[ 'version' ] ) ) {
				$version = $params['version'];
				$prefix .= "v$version-";
			}

			return $prefix;
		}
		// otherwise we are going to create a prefix for use on an article page
		// no prefix on crop images
		if (isset($params['crop']) && $params['crop'] == 1) {
			return "";
		}
		// no prefix on non wikihow images
		if ( !$image->getUser( 'text' ) || !self::isWikihowCreator( $image->getUser( 'text' ) ) ) {
			return "";
		}
		if ( isset($params[self::NO_WATERMARK]) ) {
			return "";
		}
		// for now we do not want to add this watermark to png files since the
		// way we overlay it fades the image
		$ext = strrchr( $params['descriptionUrl'], '.' );
		if ( $ext == '.png' ) {
			return "";
		}

		$pageId = isset($params['mArticleID']) ? $params['mArticleID'] : 0;

		if ( $pageId && $wgLanguageCode == 'en' ) {
			$prefix = 'aid' . $pageId . '-';
		}

		$prefix .= 'v4-';
		return $prefix;
	}

	// given an image file (local file object) delete the thumbnails from s3
	// functionality gotten from FileRepo.php quickpurgebatch and LocalFile.php purgethumblist
	public static function recreateThumbnails($file) {

		$thumbnails = $file->getThumbnails();

		// take out the directory from the list of thumbnails
		array_shift( $thumbnails );

		foreach ( $thumbnails as $thumbnail ) {
			// Check that the base file name is part of the thumb name
			// This is a basic sanity check to avoid acting on unrelated directories
			if ( strpos( $thumbnail, $file->getName() ) !== false ||
					strpos( $thumbnail, "-thumbnail" ) !== false ) {

				$vPath = $file->getThumbPath($thumbnail);
				$thumbPath = $file->repo->getLocalReference($vPath)->getPath();
				$thumbUrl = $file->getThumbUrl().'/'.$thumbnail;

				// make sure the file is the right format
				$imageSize = getimagesize($thumbPath);
				if ($imageSize["mime"] != $file->getMimeType()) {
					continue;
				}

				$params = array();
				$params["width"] = $imageSize[0];
				if (strpos($thumb, "crop") != false) {
					$params['crop'] = 1;
					if (strpos($thumb, "--") == false) {
						$params["height"] = $imageSize[1];
					}
				}

				$params[self::FORCE_TRANSFORM] = true;
				$params[self::ADD_WATERMARK] = true;

				$result = $file->getHandler()->doTransform($file, $thumbPath, $thumbUrl, $params);
				if ( get_class($result) == 	MediaTransformError) {
					print "there was an error processing this file \n";
					print $result->toText();
				}
			}
		}
	}
}
