<?php

class ImageUploader extends UnlistedSpecialPage {

	private $mustache = null;

	public function __construct() {
		parent::__construct('ImageUploader');
		$this->mustache = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates')]);
	}

	/**
	 * This hook is run just before the edit form is opened on the Advanced Editor
	 * and Guided Editor pages. It's important that the div is inserted before the
	 * edit form (rather than within it), because otherwise we could end up with a
	 * form-within-a-form which is not valid HTML and can break stuff.
	 */
	public static function onEditPageShowEditFormInitial($editPage, $out) {
		$title = $out->getTitle();
		// only add the image uploader dialog and JS when EditPage
		// isn't being initialized via a special page
		if (!$title || !$title->isSpecialPage()) {
			self::bootstrapImageUploader($out);
		}
		return true;
	}

	/**
	 * This hook is run while a POST file upload is being stashed. We need to store this
	 * temporary file on S3 so that it can be accessed on a different app server if the
	 * user ends up doing the "insert image" part of the process there. If this process
	 * fails, it won't necessarily be noticed until we're in a multi-server environment
	 * (like on the production app servers), so be careful.
	 */
	public static function onUploadStashProcessFile($filekey) {
		self::putFileS3( $filekey );
		return true;
	}

	/**
	 * This hook is called by UploadStash to make sure that the file exists on the local
	 * app server, possibly by downloading it via S3. The situation where this file
	 * might not exist comes up in a multi-server environment (like our production app
	 * environment), so it's very important that this functions properly. Testing on
	 * dev (which is a single server), might not show a latent bug, unfortunately.
	 */
	public static function onUploadStashGetFile($filekey) {
		self::getFileS3( $filekey );
		return true;
	}

	private static function bootstrapImageUploader($out) {
		$iu = new ImageUploader();

		$vars = [
			'image_uploader_title' => wfMessage('image-uploader')->text(),
			'image_msg' => wfMessage('iu-image')->text(),
			'license_msg' => wfMessage('eiu-license')->text(),
			'choose_button_msg' => wfMessage('iu-choose-button')->text(),
			'insert_button_msg' => wfMessage('iu-insert-button')->text(),
			'upload_details_msg' => wfMessage('iu-upload-details')->text(),
			'eg_descriptive_file_msg' => wfMessage('iu-eg-descriptive-file')->text(),
			'image_upload_notice_msg' => wfMessage('iu-image-upload-notice')->text(),
			'licenses' => self::getLicenses(),
		];
		$html = $iu->mustache->render('dialog_box.mustache', $vars);
		$out->addHTML($html);

		$out->addModules('jquery.ui.dialog');
		$out->addModules('ext.wikihow.imageupload');
	}

	private static function getFileExtensions() {
		global $wgFileExtensions;
		$disallowed = ['svg','pdf'];
		$copy = array_diff($wgFileExtensions, $disallowed);
		return $copy;
	}

	// Sanitize an input file name to be Mediawiki legal
	private static function legalizeImageName($name) {
		$name = urldecode($name);
		$name = preg_replace('/[^'.Title::legalChars().']|[-:\/\\\\]|\?/', '_', $name);
		$name = preg_replace('@_{2,}@', '_', $name);
		$name = preg_replace('@&amp;@', '&', $name);
		$name = preg_replace('@[ _]{2,}@', ' ', $name);
		$name = preg_replace('@\s+@', ' ', $name);
		$name = trim($name);

		list($first, $ext) = self::splitFilenameExt($name);
		$first = trim($first);
		$ext = strtolower($ext);
		$name = $first . '.' . $ext;

		return $name;
	}

	private static function getLicenses() {
		$licenses = [
			[ 'name' => 'made', 'mwname' => '{{Self}}',
				'text' => wfMessage('iu-license-made')->text() ],
			[ 'name' => 'screenshot', 'mwname' => '{{Screenshot}}',
				'text' => wfMessage('iu-license-screenshot')->text() ],
			[ 'name' => 'found', 'mwname' => '{{No License}}',
				'text' => wfMessage('iu-license-found')->text() ],
		];
		return $licenses;
	}

	/**
	 * Insert an image upload into the mediawiki database tables.  If the
	 * image insert was successful, a page showing the wiki text for their
	 * image is shown.  Otherwise, an error is returned.
	 *
	 * @param string $mwname filename of the file in mediawiki DB
	 * @param string $selectedLicense license string that the user selected
	 * @param string $fileKey reference to the stashed upload image
	 * @return outputs either a wikitext tag or an error message.
	 */
	private function insertImage($mwname, $selectedLicense, $fileKey) {
		$req = $this->getRequest();
		$user = $this->getUser();
		$out = $this->getOutput();

		$licenseWikitext = '';
		$licenses = self::getLicenses();
		foreach ($licenses as $license) {
			if ($license['name'] == $selectedLicense) {
				$licenseWikitext = $license['mwname'];
				break;
			}
		}
		if (!$licenseWikitext) {
			return ['error' => 'Unknown license selected'];
		}

		if (wfReadOnly()) {
			return ['error' => wfMessage('eiu-readonly')->text()];
		}

		$res = $this->checkMediawikiFilename($mwname);
		if (!$res['valid']) {
			return ['error' => $res['error']];
		}
		$title = Title::makeTitleSafe(NS_IMAGE, $mwname);

		// is the target protected?
		$permErrors = $title->getUserPermissionsErrors('edit', $user);
		$permErrorsUpload = $title->getUserPermissionsErrors('upload', $user);

		if ($permErrors || $permErrorsUpload) {
			return ['error' => 'This image is protected'];
		}

		// Get file from stash and try commit to MW under chosen name
		$upload = new UploadFromStash($user);
		$upload->initialize($fileKey, $mwname);
		$file = $upload->getLocalFile();
		if (!$file) {
			return ['error' => 'Unable to upload file'];
		}

		self::getFileS3($file);
		$comment = $this->getFileCommitComment();
		$status = $upload->performUpload(
			$comment,
			$licenseWikitext,
			false, // watch file?
			$user
		);

		if ( !$status->isGood() ) {
			return ['error' =>  $status->getHTML(true)];
		}

		$file = wfFindFile($title);
		if (!is_object($file)) {
			return ['error' => 'Uploaded file not found'];
		}

		$prefixedTitle = $title->getPrefixedText();
		$tag = "[[$prefixedTitle|center]]";

		$thumbWidth = min($file->getWidth(), 670);
		$thumbParams = array('width' => $thumbWidth, 'height' => -1);
		$thumb = $file->transform( $thumbParams, File::RENDER_NOW );

		// Upload thumb to S3 to get around image scaler db lag
		self::uploadThumbS3($thumb);

		// return success
		return ['error' => '', 'tag' => $tag];
	}

	/**
	 * Split a file name such as "foo bar.jpg" into array('foo bar', 'jpg')
	 *
	 * @param $name file name string
	 * @return array with key 0 being the first part and key 1 being the
	 *   extension.
	 */
	private static function splitFilenameExt($name) {
		preg_match('@^(.*)(\.([^.]+))?$@U', $name, $m);
		return [$m[1], $m[3]];
	}

	/**
	 * Accept a request to upload an image via POST data (user upload).
	 *
	 * @return array of image properties
	 */
	private function uploadImage() {
		$error = '';
		$comment = '';
		$fileKey = '';
		$debugInfo = [];

		$req = $this->getRequest();
		$user = $this->getUser();

		$upload = UploadBase::createFromRequest( $req );
		$status = $upload->fetchFile();
		if ( !$status->isOK() ) {
			$error = $this->getOutput()->parse( $status->getWikiText() );
		}

		if ( !$error ) {
			$result = $upload->verifyUpload();
			if ( $result['status'] != UploadBase::OK ) {
				// see SpecialUpload::processVerificationError for full list of possible
				// errors, such as UploadBase::FILE_TOO_LARGE
				$error = wfMessage('eiu-upload-error') . "<br><br>\n" .
					"Technical info: " . print_r($result, true);
			}
		}

		$file = $upload->stashFile();
		$fileKey = $file->getFileKey();
		$origTitle = $upload->getTitle();
		if (!$origTitle) {
			return ['error' => "File has null title"];
		}
		$origname = $origTitle->getText();
		$mwname = self::legalizeImageName($origname);
		list($first, $ext) = self::splitFilenameExt($mwname);
		$isImage = !$error && $file && in_array($file->getMediaType(), ['BITMAP', 'DRAWING', 'UNKNOWN']);
		if (!$isImage) {
			$error = "File '$origname' was not an image file";
		}

		if ($error) {
			return ['error' => $error];
		} else {
			$file = $upload->getLocalFile();
			self::putFileS3($file);

			$thumbWidth = min($file->getWidth(), 670);
			$thumbnail = $file->getThumbnail($thumbWidth, -1);
			$htmlWidth = min($thumbWidth, 340);

			$props = [
				'error' => '',
				'origname' => $origname,
				'mwname' => $mwname,
				'ext' => $ext,
				'width' => $file->getWidth(),
				'height' => $file->getHeight(),
				'image_comment' => $comment,
				'license' => $user->getOption('image_license'),
				'filekey' => $fileKey,
				'url' => $thumbnail->getUrl(),
			];

			return $props;
		}
	}

	private function getFileCommitComment() {
		$commentMsg = $this->msg('eiu-upload', 'edit page image upload');
		$comment = $commentMsg->inContentLanguage()->isBlank()
			? 'Image upload via edit page'
			: $commentMsg->plain();
		return $comment;
	}

	// Upload thumb to S3. We upload it so that DB lag can occur on image
	// scalers, but the correct size of image will already exist
	private static function uploadThumbS3($thumb) {
		$imageS3obj = urldecode( $thumb->getUrl() );
		if (!AwsFiles::fileExists($imageS3obj)) {
			$localPath = $thumb->getLocalCopyPath();
			$mimeType = $thumb->getFile()->getMimeType();
			AwsFiles::uploadFile($localPath, $imageS3obj, $mimeType);
		}
	}

	// Utility function for putFileS3 and getFileS3 below.
	//
	// Note: There must be a better way to get these relative paths
	// from the file or file systems objects from the virtual paths,
	// but I haven't found it.
	private static function convertPathS3($path) {
		$patterns = array(
			'@^mwstore://local-backend/local-public/@',
			'@^mwstore://local/public/@',
			'@^mwstore://local-backend/local-temp/@',
			'@^mwstore://local/temp/@',
		);
		$replacements = array(
			'/images/',
			'/images/',
			'/images/temp/',
			'/images/temp/',
		);
		$imageS3obj = preg_replace( $patterns, $replacements, $path );
		return $imageS3obj;
	}

	private static function putFileS3($file) {
		$path = $file->getPath();
		$imageS3obj = self::convertPathS3($path);
		wfDebugLog('imageupload', "putFileS3, path=$path, imageS3obj=$imageS3obj");
		if (!$imageS3obj || $imageS3obj{0} != '/') {
			wfDebugLog('imageupload', "putFileS3, unparsed getPath result: imageS3obj=$imageS3obj");
			return false;
		}
		if (!AwsFiles::fileExists($imageS3obj)) {
			$localPath = $file->getLocalRefPath();
			$localPathExists = file_exists($localPath);
			wfDebugLog('imageupload', "putFileS3, S3 file does not exist: s3obj=$imageS3obj; uploading from localPath=$localPath; localPathExists=" . (int)$localPathExists);
			$mimeType = $file->getMimeType();
			AwsFiles::uploadFile($localPath, $imageS3obj, $mimeType);
		} else {
			wfDebugLog('imageupload', "putFileS3, imageS3obj=$imageS3obj already exists, ignoring");
		}
		return true;
	}

	private static function getFileS3($file) {
		global $wgUploadDirectory;

		$path = $file->getPath();
		$imageS3obj = self::convertPathS3($path);
		wfDebugLog('imageupload', "getFileS3, path=$path, imageS3obj=$imageS3obj");
		if (!$imageS3obj || $imageS3obj{0} != '/') {
			wfDebugLog('imageupload', "getFileS3, unparsed getPath result: imageS3obj=$imageS3obj");
			return false;
		}
		if (AwsFiles::fileExists($imageS3obj)) {
			//$localPath = $file->getLocalRefPath();
			$localPath = "$wgUploadDirectory/..$imageS3obj";
			wfDebugLog('imageupload', "getFileS3, S3 file exists: s3obj=$imageS3obj; downloading to localPath=$localPath");
			AwsFiles::getFile($imageS3obj, $localPath);

			// We need to clear the stat cache within the backend object
			// so that the new local file can be found.
			$file->getRepo()->getBackend()->clearCache();
		} else {
			wfDebugLog('imageupload', "getFileS3, imageS3obj=$imageS3obj does not exist");
			return false;
		}
		return true;
	}

	private function checkMediawikiFilename($mwname) {
		list($first, $ext) = self::splitFilenameExt($mwname);
		if (!trim($first)) {
			return ['valid' => false, 'error' => wfMessage('iu-invalid-title')->text()];
		}

		$ext = strtolower($ext);
		$exts = self::getFileExtensions();
		if (!in_array($ext, $exts)) {
			$error = wfMessage('iu-files-accepted', join(', ', $exts))->text();
			return ['valid' => false, 'error' => $error];
		}

		$title = Title::makeTitleSafe(NS_IMAGE, $mwname);
		if (!$title) {
			return ['valid' => false, 'error' => wfMessage('iu-invalid-title')->text()];
		} elseif ($title->exists()) {
			return ['valid' => false, 'error' => wfMessage('iu-name-exists')->text()];
		} else {
			return ['valid' => true];
		}
	}

	/**
	 * Executes the ImageUploader special page and all its API methods
	 */
	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$action = $req->getVal('action');
		$out->setArticleBodyOnly(true);

		if (!$req->wasPosted() || $user->isAnon()) {
			$out->addHTML('API endpoint for logged in users');
			return;
		}

		if ($action == 'uploadfile') {
			$props = $this->uploadImage();
			$out->addHTML( json_encode($props) );
		} elseif ($action == 'checkfilename') {
			$mwname = $req->getVal('mwname');
			$props = $this->checkMediawikiFilename($mwname);
			$out->addHTML( json_encode($props) );
		} elseif ($action == 'insertimage') {
			$mwname = $req->getVal('mwname');
			$selectedLicense = $req->getVal('license');
			$fileKey = $req->getVal('filekey');
			$props = $this->insertImage($mwname, $selectedLicense, $fileKey);
			$out->addHTML( json_encode($props) );
		} else {
			$out->addHTML('Unknown action');
		}
	}
}
