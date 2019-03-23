<?php

class UserCompletedImages extends UnlistedSpecialPage {

	const UPVOTES = 3;
	const DOWNVOTES = 2;

	const MOBILE_THUMB_WIDTH = self::SIDEBAR_THUMB_WIDTH;
	const MOBILE_THUMB_HEIGHT = self::SIDEBAR_THUMB_HEIGHT;

	const THUMB_WIDTH = 225;
	const THUMB_HEIGHT = 225;

	const SIDEBAR_THUMB_WIDTH = 126;
	const SIDEBAR_THUMB_HEIGHT = 120;

	const LB_THUMB_WIDTH = 670;
	const LB_THUMB_HEIGHT = -1;

	const CONFIG_KEY = "picture_patrol_whitelist";
	const BLACKLIST_CONFIG_KEY = "picture_patrol_blacklist";

	const UCI_CACHE = true;

	const UCI_TABLE = 'user_completed_images';

	public function __construct() {
		parent::__construct('UserCompletedImages');
	}

	//[sc] 12/2018 - removing UCI from mobile
	// public function isMobileCapable() {
	// 	return true;
	// }

	public function execute($par) {
		if ($this->getUser()->isBlocked()) {
			throw new PermissionsError( 'usercompletedimages' );
		}

		$this->getOutput()->setArticleBodyOnly(true);
		header('Content-type: application/json');

		$result = array();
		$toDelete = $this->getRequest()->getVal('delete');
		if ($toDelete) {
			$result = $this->deleteImage($toDelete);
		} else {
			$result = $this->uploadImage();
		}

		//$mobile = $this->getRequest()->getFuzzyBool( 'mobile' );
		//if ( $mobile === false ) {
			//$result['uploadResponse'] = SocialProofStats::getSidebarVerifyHtml();
		//}

		echo json_encode($result);
	}

	private static function splitFilenameExt($name) {
		preg_match('@^(.*)(\.([^.]+))?$@U', $name, $m);
		return array($m[1], $m[3]);
	}

	protected function addToDB($data) {
		$user = $this->getUser();

		$uci_row_data = array(
			'uci_image_name' => $data['titleDBkey'],
			'uci_image_url' => $data['fileURL'],
			'uci_user_id' => intval($user->getId()),
			'uci_user_text' => $user->getName(),
			'uci_timestamp' => $data['timestamp'],
			'uci_on_whitelist' => 1,
			'uci_article_id' => intval($data['titleArtID']),
			'uci_article_name' => $data['fromPage']);

		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		$dbw->insert(
			'user_completed_images',
			$uci_row_data,
			__METHOD__,
			array());
		$dbw->commit();
	}

	protected function uploadImage() {
		//global $wgImageMagickConvertCommand, $wgServer;

		$request = $this->getRequest();
		$result = array();

		$fromPage = $request->getVal('viapage');

		// sanity check on the page to link to
		$title = Title::newFromText($fromPage, NS_MAIN);
		if (!$title || !$title->exists()) {
			$result['error'] = "Error: No article $fromPage found to link image.";
			return $result;
		}

		// try to get a unique file name by appending a suffix and the current time to the save name here
		$dateTime = new DateTime();

		$webUpload = $request->getUpload('wpUploadImage');
		$info = new SplFileInfo($webUpload->getName());
		$ext = $info->getExtension();
		$info = new SplFileInfo($request->getVal("name"));
		for ($i = 0; $i < 100; $i++) {
			$fromPage_safe = str_replace('/', '-', $fromPage);
			$saveName = "User Completed Image {$fromPage_safe} {$dateTime->format('Y.m.d H.i.s')}.$i.{$ext}";
			$title = Title::newFromText($saveName, NS_IMAGE);
			if (!$title->getArticleID()) {
				break;
			}
		}

		// do not allow pdf file extensions
		if (UploadBase::checkFileExtensionList( array( $ext ), array( 'pdf' ) ) ) {
			$result['error'] = 'Error: pdf files are not allowed.  Please upload an image.';
			return $result;
		}

		// if the title still exists, show an error
		if ($title->getArticleID()) {
			$result['error'] = 'Error: file with this name already exists.';
			return $result;
		}

		$upload = new UploadFromFile();
		$upload->initialize($saveName, $webUpload);
		$verification = $upload->verifyUpload();
		if ( $verification['status'] !== UploadBase::OK ) {
			$result['error'] = "verification error: "
				.$verification['status'].": "
				.UploadBase::getVerificationErrorCode($verification['status']);
			return $result;
		}

		$warnings = $upload->checkWarnings();
		if ( $warnings) {
			$result['warnings'] = $warnings;

			if ($warnings['duplicate']) {
				$result['debug'][] = $warnings['duplicate-archive'];
				//  this is useful for testing out errors in uploads
				//$result['error'] = "Sorry, this file was already uploaded.  Please try uploading a new image.";
				//return $result;
			}
		}

		$license = '{{Self}}';
		$status = $upload->performUpload( $license, $license, true, $this->getUser() );
		if ( !$status->isGood() ) {
			$error = $status->getErrorsArray();
			$result['error'] = wfMessage('uciuploaderror')->text();
			$result['debug'] = json_encode($error);
			return $result;
		}

		$upload->cleanupTempFile();

		// todo - do this part after the single file upload
		// Image orientation is a bit wonky on some mobile devices; use ImageMagick's auto-orient to try fixing it.
		//$tempFilePath = $temp_file->getPath();
		//$cmd = $wgImageMagickConvertCommand . ' ' . $tempFilePath . ' -auto-orient ' . $tempFilePath;
		//exec($cmd);

		$file = $upload->getLocalFile();
		$thumb = $file->getThumbnail(200, -1, true, true);
		if (!$thumb) {
			$result['error'] = 'file thumbnail does not exist';
			$file->delete('');
			return $result;
		}

		$fileTitle = $file->getTitle();
		$result['titleText'] = $fileTitle->getText();
		$result['titleDBkey'] = substr($fileTitle->getDBkey(), 21); // Only keep important info
		$result['titlePreText'] = '/' . $fileTitle->getPrefixedText();
		$result['titleArtID'] = $fileTitle->getArticleID();
		$result['timestamp'] = wfTimestamp(TS_MW);
		$result['fromPage'] = $request->getVal('viapage');
		$result['thumbURL'] = $thumb->getUrl();
		$result['fileURL'] = $file->getUrl();
		$result['fileWidth'] = $file->getWidth();
		$this->addToDB($result);

		//this field breaks the json for mobile safari, and we don't need it, so get rid of it.
		unset($result['timestamp']);

		$success_msg = Misc::isMobileMode() ? 'uploadSuccessMessage_mobile' : 'uploadSuccessMessage';
		$result['successMessage'] = wfMessage($success_msg)->text();

		return $result;
	}

	protected function deleteImage($imgName) {
		$user = $this->getUser();

		$localRepo = RepoGroup::singleton()->getLocalRepo();
		$file = $localRepo->findFile($imgName);
		if (!$file || !$file->exists()) {
			$result['error'] = 'Error: File not found.';
			return $result;
		}

		$fileTitle = $file->getTitle();
		$comment = 'UCI undo';
		$imgDBKey = substr($fileTitle->getDBkey(), 21);

		$userGroups = $user->getGroups();
		if (!in_array('staff', $userGroups) && $user->getName() != "G.bahij") {
			// If not staff, make sure the user deleting their own image and not someone else's
			$userID = $user->getID();
			$userDBKey = $userID > 0 ? 'uci_user_id' : 'uci_user_text';
			$userDBVal = $userID > 0 ? $userID : $user->getName();

			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select(
				'user_completed_images',
				array('uci_user_id', 'uci_user_text'),
				array('uci_image_name' => $imgDBKey),
				__METHOD__);
			if ($res->numRows() == 0) {
				$result['error'] = 'Could not find image in database.';
				return $result;
			}
			$userCompletedImage = $res->fetchRow();

			if ($userCompletedImage[$userDBKey] != $userDBVal) {
				$result['error'] = 'You do not have permission to delete this image.';
				return $result;
			}
		} else {
			$comment = '[Staff] ' . $comment;
		}

		$file->delete($comment);
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		$dbw->delete(
			'user_completed_images',
			array('uci_image_name' => $imgDBKey),
			__METHOD__);
		$dbw->commit();

		$result['success'] = 1;

		return $result;
	}
	private static $whiteListWords = array("Make");

	public static function hasUCI($title, $purge = false) {
		$result = false;
		if (!$title->exists() || !$title->inNamespace(NS_MAIN)) {
			return false;
		}

		$width = self::THUMB_WIDTH;
		$height =self::THUMB_HEIGHT;
		$thumbs = self::getUCIThumbs($title, $width, $height, $purge);
		if (!$thumbs || count($thumbs) == 0) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	public static function onWhitelist($title) {
		// first check the new blacklist for articles that will never be whitelisted
		$id = $title->getArticleId();
		if ( ArticleTagList::hasTag( self::BLACKLIST_CONFIG_KEY, $id ) ) {
			return false;
		}

		// now check for titles matching the whitelist words
		$wlWords = preg_split('/[\ \n\,]+/', wfMessage("uciwhitelistwords"));
		if ( in_array( strtok( $title->getText(), " " ), $wlWords) ) {
			return true;
		}

		if ( ArticleTagList::hasTag( self::CONFIG_KEY, $id ) ) {
			return true;
		}

		return false;
	}

	private static function getImagesHTMLSized( $title, $offset, $limit, $width, $height, $purge = false ) {
		$thumbs = self::getUCIThumbs( $title, $width, $height, $purge );
		$totalCount = count($thumbs);
		$crop = false;
		$lightboxThumbs = self::getUCIThumbs( $title, self::LB_THUMB_WIDTH, self::LB_THUMB_HEIGHT, $purge, $crop );

		$result = array();
		$result['end'] = false;
		$result['html'] = "";
		$result['data'] = array();
		$result['count'] = 0;
		$result['lbData'] = array();

		if ( ($offset + $limit) >= count($thumbs)) {
			$result['end'] = true;
		}
		if ( !$thumbs || count( $thumbs ) == 0 ) {
			return $result;
		}

		$html = "";
		$i = 0;

		$thumbs = array_slice( $thumbs, $offset, $limit, true );
		$data = array();
		foreach ( $thumbs as $pageId => $thumb ) {
			$lbSrc = wfGetPad($lightboxThumbs[$pageId]['url']);
			$src = wfGetPad($thumb['url']);
			$timeago = wfTimeAgo($thumb['ts']);
			$img = Misc::getMediaScrollLoadHtml( 'img', [ 'src' => $src, 'alt' => '', 'class' => 'whcdn' ] );
			$html .= "<a class='uci_thumbnail uci_thumbnail_steps swipebox' pageid='$pageId' href='$lbSrc'>$img</a>\n";
			$data[] = array('pageId'=>$pageId, 'lbSrc'=>$lbSrc, 'src' => $src, 'timeago'=> $timeago, 'img' => $img );
		}

		$lbData = array();
		foreach ( $lightboxThumbs as $pageId => $thumb ) {
			$src = $thumb['url'];
			$lbData[] = array( "pageId" => $pageId, 'href'=>$src );
		}

		$result['html'] = $html;
		$result['data'] = $data;
		$result['count'] = $totalCount;
		$result['lbData'] = $lbData;

		return $result;
	}

	public static function getImagesHTML( $title, $offset, $limit, $purge = false ) {
		$width = self::THUMB_WIDTH;
		$height = self::THUMB_HEIGHT;
		return  self::getImagesHTMLSized( $title, $offset, $limit, $width, $height, $purge );
	}

	// get the thumbnail and lightbox data to be inserted into templates
	public static function getUCIData( $context, $title = null, $offset = 0, $limit = 6 , $purge = false, $width = 0, $height = 0 ) {
		if ( $title == null ) {
			$title = $context->getTitle();
		}

		if (!self::onWhitelist($title)) {
			return null;
		}

		$headerName = wfMessage('ucisidebarname')->text();

		if ($width == 0) {
			$width = self::MOBILE_THUMB_WIDTH;
		}

		if ($height == 0) {
			$height = self::MOBILE_THUMB_HEIGHT;
		}

		if ($context->getRequest()->getVal("purgeuci") == "true") {
			$purge = true;
		}
		$thumbs = self::getImagesHTMLSized( $title, $offset, $limit, $width, $height, $purge );
		$data = array( "thumbs" => $thumbs['data'],
			"end" => $thumbs['end'],
			"totalCount" => $thumbs['count'],
			"lightboxthumbs" => json_encode( $thumbs['lbData'] ),
			"addPhotoMessage" => wfMessage( "addphotomessage_desktop" )->text(),
			"loadingMessage" => wfMessage( "loadingmessage" )->text(),
			"uciupload_instructions" => wfMessage( "uciupload_instructions" )->text(),
			'headername' => $headerName );
		return $data;
	}

	public static function getDesktopSidebarHtml( $context, $title = null, $offset = 0, $limit = 7 ) {
		return "";
		$result = null;
		$data = self::getUCIData( $context, $title, $offset, $limit );
		if ( $data ) {
			EasyTemplate::set_path(__DIR__.'/');
			$result = EasyTemplate::html( 'usercompletedimages.desktop.sidebar.tmpl.php', $data );
		}
		return $result;
	}

	private static function getUCIThumbsCacheKey($pageTitle, $width, $height) {
		$pageTitle = str_replace( ' ', '-', $pageTitle );
		return wfMemcKey('ucithumbs1', $pageTitle, $width, $height);
	}

	public static function removeImageFromPage($hostPageTitle, $pageId) {
		self::removeFromCache($hostPageTitle, $pageId, self::THUMB_WIDTH, self::THUMB_HEIGHT);
		self::removeFromCache($hostPageTitle, $pageId, self::LB_THUMB_WIDTH, self::LB_THUMB_HEIGHT);
		self::removeFromCache($hostPageTitle, $pageId, self::SIDEBAR_THUMB_WIDTH, self::SIDEBAR_THUMB_HEIGHT);
	}

	private static function removeFromCache($hostPageTitle, $pageId, $width, $height) {
		global $wgMemc;

		$key = self::getUCIThumbsCacheKey($hostPageTitle, $width, $height);

		$thumbs = $wgMemc->get($key);
		if (!$thumbs || !is_array($thumbs)) {
			return;
		}

		unset($thumbs[$pageId]);
		$wgMemc->set($key, $thumbs);
	}

	public static function addImageToPage($pageId, $hostPageTitle, $image) {
		// adds a thumbnail image to the cache of images using default width and height
		$crop = true;
		self::addImageToCache( $pageId, $hostPageTitle, $image, self::THUMB_WIDTH, self::THUMB_HEIGHT, $crop);

		// adds a thumbnail image to the cache of images using lightbox width and height
		// we do not cop this image
		$crop = false;
		self::addImageToCache( $pageId, $hostPageTitle, $image, self::LB_THUMB_WIDTH, self::LB_THUMB_HEIGHT, $crop);
	}

	private static function addImageToCache($pageId, $hostPageTitle, $image, $width, $height, $crop=true) {
		global $wgMemc;

		if (!self::UCI_CACHE ) {
			return;
		}

		$key = self::getUCIThumbsCacheKey($hostPageTitle, $width, $height);
		MWDebug::log("key is ".$key);
		$thumbs = $wgMemc->get($key);
		if (!$thumbs || !is_array($thumbs)) {
			$thumbs = array();
		}

		if (!isset($thumbs[$pageId])) {
			$thumb = self::getUCICacheData($pageId, $image, $width, $height, $crop);
			if ($thumb) {
				$thumbs[$pageId] = $thumb;
			}
		}
		$wgMemc->set($key, $thumbs);
	}

	// query for images shown on the page
	public static function getUCIQuery($pageTitle) {
		return(	array(
			"uci_article_name" => $pageTitle,
			"uci_upvotes >= ".self::UPVOTES,
			"uci_downvotes < ". self::DOWNVOTES,
			"uci_copyright_violates = 0",
			"uci_copyright_error = 0",
			"uci_copyright_checked = 1"
			));
	}
	// gets the list of user competed image files for a given page
	private static function getUCIForPage($pageTitle) {
		if (!$pageTitle) {
			return array();
		}

		$pageTitle = str_replace( ' ', '-', $pageTitle );

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			'user_completed_images',
			'*',
			self::getUCIQuery($pageTitle),
			__METHOD__,
			[
				'ORDER BY' => 'uci_timestamp DESC'
			]
		);

		$files = array();
		foreach ( $res as $row ) {
			$files[$row->uci_article_id] = self::fileFromRow($row);
		}
		return $files;
	}

	// gets an image given the user_completed_images row
	public static function fileFromRow($row) {
		MWDebug::log("will look for file: ".$row->uci_image_name);
		return wfFindFile("User-Completed-Image-".$row->uci_image_name);
	}

	public static function getUCICacheData($pageId, $image, $width, $height, $crop = true) {
		if (!$image) {
			return null;
		}
		$render = false;
		$thumb = $image->getThumbnail($width, $height, $render, $crop);
		if (!$thumb) {
			return null;
		}

		$data = array(
			"url"=>$thumb->getUrl(),
			"width"=>$width,
			"height"=>$height,
			"ts"=>$image->getTimestamp()
		);

		return $data;
	}

	private static function getUCIThumbs($pageTitle, $width, $height, $purge, $crop = true) {
		global $wgMemc;

		if (!$pageTitle) {
			return array();
		}

		$key = self::getUCIThumbsCacheKey($pageTitle, $width, $height);

		$thumbs = $wgMemc->get($key);
		if (self::UCI_CACHE && is_array($thumbs) && !$purge) {
			return $thumbs;
		}

		$images = self::getUCIForPage($pageTitle);
		$thumbs = array();
		foreach ($images as $pageId=>$image) {
			if (!$image) {
				continue;
			}
			$thumb = self::getUCICacheData($pageId, $image, $width, $height, $crop);
			$thumbs[$pageId] = $thumb;
		}

		$wgMemc->set($key, $thumbs);

		return $thumbs;
	}

	public static function getDesktopSectionHtml( $context, $title = null, $offset = 0, $limit = 5 ) {
		$result = '';

		if (!$context) {
			return '';
		}
		$title = $context->getTitle();
		if ( !$title || !$title->exists() ) {
			return '';
		}

		if (!self::onWhitelist($title)) {
			return '';
		}

		$purge = false;
		if ($context->getRequest()->getVal("purgeuci") == "true") {
			$purge = true;
		}

		$data = self::getUCIData( $context, $title, $offset, $limit, $purge, self::THUMB_WIDTH, self::THUMB_HEIGHT );
		if ( $data ) {
			if ( !$data['totalCount'] ) {
				$data['headerextraclass'] = 'nouciimages';
			}
			EasyTemplate::set_path(__DIR__.'/');
			$result = EasyTemplate::html( 'usercompletedimages.desktop.section.tmpl.php', $data );

		}
		return $result;
	}

	// adds the "Made Recently" section to the current php query document
	// * needs to be added here for the defer images to work
	public static function addDesktopSection($context) {
		if (!$context) {
			return '';
		}

		$title = $context->getTitle();
		if ( !$title || !$title->exists() || $title->isRedirect() ) {
			return '';
		}

		if ($context->getLanguage()->getCode() != 'en') {
			return '';
		}

		if (!$title->inNamespace(NS_MAIN) || $context->getRequest()->getVal('action', 'view') != 'view' || $title->getText() == wfMessage('mainpage')->inContentLanguage()->text()) {
			return '';
		}

		$html = self::getDesktopSectionHTML($context);

		if ($html) {
			$context->getOutput()->addModules('ext.wikihow.usercompletedimages');
			pq("#bodycontents")->append($html);
		}
	}

	//[sc] 12/2018 - removing UCI from mobile
	// public static function getMobileSectionHTML($context) {
	// 	if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
	// 		return '';
	// 	}

	// 	if (!$context) {
	// 		return '';
	// 	}

	// 	$title = $context->getTitle();

	// 	if ( !$title || !$title->exists() ) {
	// 		return '';
	// 	}

	// 	if (!self::onWhitelist($title)) {
	// 		return '';
	// 	}

	// 	$offset = 0;
	// 	$limit = 7;
	// 	$data = self::getUCIData( $context, $title, $offset, $limit );
	// 	if ( $data ) {
	// 		EasyTemplate::set_path(__DIR__.'/');
	// 		$result = EasyTemplate::html( 'mobile-image-upload.tmpl.php', $data );
	// 	}

	// 	return $result;
	// }

	private static function updateWhitelist($pageIds, $val) {
		$dbw = wfGetDB(DB_MASTER);
		$table = "user_completed_images";
		$values = array("uci_on_whitelist" => $val);

		foreach ($pageIds as $pageId) {
			if (!$pageId) {
				continue;
			}
			$articleName = Title::nameOf($pageId);
			$conds = array("uci_article_name" => $articleName);
			$dbw->update($table, $values, $conds);
		}
	}

	public static function removeFromWhitelist($pageIds) {
		self::updateWhitelist($pageIds, 0);
	}

	public static function addToWhitelist($pageIds) {
		self::updateWhitelist($pageIds, 1);
	}

	//[sc] 12/2018 - removing UCI from mobile
	// public static function onAddMobileTOCItemData($wgTitle, &$extraTOCPreData, &$extraTOCPostData) {
	// 	if (self::onWhitelist($wgTitle)) {
	// 		$extraTOCPostData[] = [
	// 			'anchor' => 'uci_header',
	// 			'name' => 'Reader Pictures',
	// 			'priority' => 1600,
	// 			'selector' => '.section#uci_section',
	// 		];
	// 	}

	// 	return true;
	// }
}
