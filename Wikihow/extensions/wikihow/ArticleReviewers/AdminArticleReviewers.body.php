<?php

class AdminArticleReviewers extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminArticleReviewers');
	}

	public function doesWrites() {
		return true;
	}

	private static $imgTitle = null;

	/**
	 * Hook called from LocalFile.php when a new file is uploaded
	 */
	public static function onFileUpload( LocalFile $localFile, bool $reupload, bool $titleExists ) {
		global $wgUser;

		$imgTitle = $localFile->getTitle();
		if ( !self::$imgTitle || !self::$imgTitle->equals($imgTitle) ) {
			return;
		}

		$imgWikiPage = WikiPage::factory($imgTitle);
		$limit = [ 'move' => 'sysop', 'edit' => 'sysop' ];
		$expiry = [];
		$cascade = false;
		$reason = 'Used for Article Reviewers';

		$imgWikiPage->doUpdateRestrictions($limit, $expiry, $cascade, $reason, $wgUser);
	}

	public function execute($par) {
		global $wgCanonicalServer;

		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		$out->setRobotPolicy( 'noindex,nofollow' );

		// Is the user authorized?

		if ($user && $user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ( !$user || $user->getID() == 0 || !in_array('staff', $user->getGroups()) || Misc::isIntl() ) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		// GET

		if ( !$req->wasPosted()) {
			$out->setPageTitle('Article Reviewer Admin');
			$out->addModules("ext.wikihow.adminarticlereviewers");
			$this->displayForm();
			return;
		}

		// POST

		$imgInfo = $_FILES['ImageUploadFile'];
		$imgName = $imgInfo['name'];
		$imgExtension = pathinfo($imgName)['extension'];
		$validExtensions = [ 'jpg', 'jpeg', 'gif', 'png' ];
		$imgTitle = Title::newFromText($imgName, NS_IMAGE);

		$success = false;
		$errorMsg = '';
		$imgLink = '';
		if ( $imgInfo['error'] != UPLOAD_ERR_OK ) {
			$errorMsg = "There was a problem uploading the image (error code: {$imgInfo['error']})";
		}
		elseif ( $imgInfo['size'] == 0 ) {
			$errorMsg = "The image is empty";
		}
		elseif ( !in_array($imgExtension, $validExtensions) ) {
			$errorMsg = "Invalid file type";
		}
		elseif ($imgTitle->getArticleID() > 0) {
			$errorMsg = "Image name already exists";
		}
		else {
			$comment = 'Created with AdminArticleReviewers';
			$localFile = new LocalFile($imgTitle, RepoGroup::singleton()->getLocalRepo());
			$uploadStatus = $localFile->upload($imgInfo['tmp_name'], $comment, '');
			if ( !$uploadStatus->isGood() ) {
				$errorMsg = $uploadStatus->getHTML();
			} else {
				$href = $wgCanonicalServer . $imgTitle->getLocalURL();
				$success = true;
				$imgLink = Html::element('a', ['href'=>$href, 'target'=>'_blank'], $href);
				self::$imgTitle = $imgTitle;
			}
		}
		Misc::jsonResponse( compact('success', 'errorMsg', 'imgLink') );
		return;
	}

	function displayForm() {
		global $wgOut;

		$tmpl = new EasyTemplate( __DIR__ . '/templates' );
		$html = $tmpl->execute('adminform.tmpl.php');

		$wgOut->addHTML($html);
	}
}
