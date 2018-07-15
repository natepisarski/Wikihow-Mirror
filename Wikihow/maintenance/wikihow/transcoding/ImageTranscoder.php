<?php

global $IP;
require_once "$IP/extensions/wikihow/common/S3.php";
require_once "$IP/extensions/wikihow/DatabaseHelper.class.php";

class ImageTranscoder extends AbsTranscoder {
	public function processTranscodingArticle($pageId, $creator) {
		
	}

	/*
	 * Call this when there are ONLY images in the uploaded zip
	*/
	public function processMedia( $pageId, $creator, $imageList, $warning, $isHybridMedia, $leaveOldMedia = false, $titleChange = false ) {
		$err = '';

		try {
			$videoList = array();
			list($err, $newWarning, $replaced, $gifsAdded) =
				$this->processHybridMedia( $pageId, $creator, $videoList, $imageList, $leaveOldMedia, $titleChange );
			$warning .= $newWarning;
		} catch ( HybridMediaException $e ) {
			$err = $e->getMessage();
		}
		
		if ( $err ) {
			return array($err, $warning, 0);
		}

		return array($err, $warning, $replaced);
	}
	
	public static function addAllMediaWikiImages( $pageId, &$images ) {
		$err = null;
		foreach ($images as &$img) {
			$success = self::addMediawikiImage( $pageId, $img );
			if (!$success) {
				$err = 'Unable to add new image file ' . $img['name'] . ' to wikiHow';
				break;
			} else {
				$imgTitle = Title::newFromText($img['mediawikiName'], NS_IMAGE);
				if ($imgTitle) {
					$file = wfFindFile($imgTitle);
					if ($file) {
						$img['width'] = $file->getWidth();
						$img['height'] = $file->getHeight();
					}
				}
			}
		}
		return $err;
	}
	
    private static function getPhotoLicense( $pageId ) {
        $dbr = wfGetDb( DB_SLAVE );
        $license = '{{' . WikiVisualTranscoder::PHOTO_LICENSE . '}}';

        $table = 'concierge_articles';
        $var = 'ct_tag_list';
        $cond = array( 'ct_page_id' => $pageId, 'ct_lang_code' => 'en' );
        $tagList = $dbr->selectField( $table, $var, $cond, __METHOD__ );

        if ( stristr( $tagList, 'screenshot' ) !== FALSE ) {
            $license = '{{' . WikiVisualTranscoder::SCREENSHOT_LICENSE . '}}';
        }
        return $license;
    }

	/**
	 * Add a new image file into the mediawiki infrastructure so that it can
	 * be accessed as [[Image:filename.jpg]]
	 */
	private static function addMediawikiImage( $pageId, &$image ) {
		// check if we've already uploaded this image
		$dupTitle = DupImage::checkDupImage($image['filename']);
	
		// if we've already uploaded this image, just return that filename
		if ($dupTitle) {
			$image['mediawikiName'] = $dupTitle;
			return true;
		}
	
		// find name for image; change filename to Filename 1.jpg if
		// Filename.jpg already existed
		$regexp = '/[^' . Title::legalChars() . ']+/';
		$first = preg_replace($regexp, '', $image['first']);
		// Replace certain characters that are problematic with underscores
		$first = preg_replace('@[:/]@', '_', $first);
		// Convert multiple spaces into one because this causes problems with the upload
		$first = preg_replace('@\s{2,}@', ' ', $first);
		$ext = $image['ext'];
		$newName = $first . '.' . $ext;
		$i = 1;
		do {
			$title = Title::newFromText($newName, NS_IMAGE);
			if ($title && !$title->exists()) break;
			$newName = $first . ' Version ' . ++$i . '.' . $ext;
		} while ($i <= 1000);
	
		$comment = self::getPhotoLicense( $pageId );

		// next 6 lines taken and modified from
		// extensions/wikihow/imageupload/ImageUpload.body.php
		$title = Title::makeTitleSafe(NS_IMAGE, $newName);
		if (!$title) return false;
		$file = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());
		if (!$file) return false;
		$ret = $file->upload($image['filename'], $comment, $comment);
		if (!$ret->ok) return false;
	
		// instruct later processing about which mediawiki name was used
		$image['mediawikiName'] = $newName;
	
		// Add our uploaded image to the dup table so it's no uploaded again
		DupImage::addDupImage($image['filename'], $image['mediawikiName']);
	
		// Keep a log of where images were uploaded in wikivisual_image_names table
		$dbw = WikiVisualTranscoder::getDB('write');
		$imgname = $pageId . '/' . $image['name'];
		$sql = 'INSERT INTO wikivisual_photo_names SET filename=' . $dbw->addQuotes($imgname) . ', wikiname=' . $dbw->addQuotes($image['mediawikiName']);
		$dbw->query($sql, __METHOD__);
	
		return true;
	}
}
