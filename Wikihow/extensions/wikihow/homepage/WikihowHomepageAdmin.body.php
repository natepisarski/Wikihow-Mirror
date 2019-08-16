<?php

class WikihowHomepageAdmin extends UnlistedSpecialPage {

	const HP_TABLE = "homepage";

	private $errorTitle;
	private $errorFile;
	private $postSuccessful;
	private $reload = false; // if true, admin.tmpl.php is rendered with an AJAX call

	private static $articleTitle = null;
	private static $wpDestFile = null;

	/**
	 * Hook called from LocalFile.php when a new file is uploaded
	 */
	public static function onFileUpload( LocalFile $localFile, bool $reupload, bool $titleExists ) {
		global $wgUser;

		$imageTitle = $localFile->getTitle();
		if ( self::$wpDestFile !== $imageTitle->getText() ) {
			return;
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(self::HP_TABLE, [
			'hp_page' => self::$articleTitle->getArticleID(),
			'hp_image' => $imageTitle->getArticleID()
		]);

		$wikiPage = WikiPage::factory($imageTitle);
		$limit = [ 'move' => 'sysop', 'edit' => 'sysop' ];
		$expiry = [];
		$cascade = false;
		$reason = 'Used on homepage';
		$wikiPage->doUpdateRestrictions($limit, $expiry, $cascade, $reason, $wgUser);

		return;
	}

	public function __construct() {
		parent::__construct('WikihowHomepageAdmin');
	}

	public function doesWrites() {
		return true;
	}

	public function execute($par) {
		global $wgAjaxUploadDestCheck, $wgAjaxLicensePreview;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ($user->getID() == 0) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if (!in_array('staff', $user->getGroups())) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->errorFile = "";
		$this->errorTitle = "";

		if ($req->getVal('delete')) {
			$out->setArticleBodyOnly(true);
			$hpid = str_replace('delete_','',$req->getVal('delete'));
			$html = self::deleteHPImage($hpid);
			$out->addHTML($html);
			return;
		}


		$this->postSuccessful = true;
		if ($req->wasPosted()) {
			if ($req->getVal("updateActive")) {
				$dbw = wfGetDB(DB_MASTER);
				//first clear them all
				$dbw->update(self::HP_TABLE, array('hp_active' => 0, 'hp_order' => 0), '*', __METHOD__);

				$images = $req->getArray("hp_images");
				$count = 1;
				foreach ($images as $image) {
					if (!$image) continue;
					$dbw->update(self::HP_TABLE, array('hp_active' => 1, 'hp_order' => $count), array('hp_id' => $image));
					$count++;
				}
			} elseif ($req->getVal("reload")) {
				Misc::jsonResponse($this->getAdminPanelHtml());
				return;
			} else {
				$title = WikiPhoto::getArticleTitleNoCheck($req->getVal('articleName'));
				if (!$title || !$title->exists()) {
					$this->postSuccessful = false;
					$this->errorTitle = "* That article does not exist.";
				}

				if ($this->postSuccessful) {
					//keep going
					$imageTitle = Title::newFromText($req->getVal('wpDestFile'), NS_IMAGE);
					$file = new LocalFile($imageTitle, RepoGroup::singleton()->getLocalRepo());
					$comment = 'Created with WikihowHomepageAdmin';
					$status = $file->upload($req->getFileTempName('wpUploadFile'), $comment, '');
					if ($status->isGood()) {
						self::$articleTitle = $title;
						self::$wpDestFile = $req->getVal('wpDestFile');
						$this->reload = true;
					} else {
						$this->postSuccessful = false;
						$this->errorFile = $status->getHTML();
					}
				}
			}
		}


		$useAjaxDestCheck = $wgAjaxUploadDestCheck;
		$useAjaxLicensePreview = $wgAjaxLicensePreview;

		$adc = wfBoolToStr( $useAjaxDestCheck );
		$alp = wfBoolToStr( $useAjaxLicensePreview );
		$rel = json_encode($this->reload);
		$out->setPageTitle('WikiHow Homepage Admin');
		$out->addScript( "<script type='text/javascript'>
wgAjaxUploadDestCheck = {$adc};
wgAjaxLicensePreview = {$alp};
window.WH.HPAdminReload = {$rel};
</script>" );
		$out->addModules('ext.wikihow.WikihowHomepageAdmin');

		$this->displayForm();
		$out->addHTML($this->getAdminPanelHtml());
	}

	private function getHomepageData() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(self::HP_TABLE, '*', '', __METHOD__, array('ORDER BY' => 'hp_active DESC,hp_order'));

		$results = array();
		foreach ($res as $item) {
			$item->title = Title::newFromID($item->hp_page);
			$imageTitle = Title::newFromID($item->hp_image);
			if ($imageTitle) {
				$file = wfFindFile($imageTitle->getText());
				if ($file) {
					$thumb = $file->getThumbnail(81, 54, true, true, true);
					$item->file = wfGetPad($thumb->getUrl());
					$results[] = $item;
				}
			}
		}

		return $results;
	}

	private function getAdminPanelHtml(): string {
		if ($this->reload) {
			return '<div class="hp_admin_panel"><hr class="divider"><i>Loading...</i></div>';
		}

		$results = $this->getHomepageData();

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'items' => $results
		));

		return $tmpl->execute('admin.tmpl.php');
	}

	private function displayForm() {
		$req = $this->getRequest();
		$out = $this->getOutput();

		if ($this->errorTitle || $this->errorFile) {
			$articleName = $req->getVal('articleName');
			$destFile = $req->getVal('wpDestFile');
		} else {
			$articleName = $destFile = '';
		}

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'errorTitle' => $this->errorTitle,
			'errorFile' => $this->errorFile,
			'articleName' => $articleName,
			'destFile' => $destFile
		));
		$html = $tmpl->execute('form.tmpl.php');

		$out->addHTML($html);
	}

	private static function deleteHPImage($hpid) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->delete(self::HP_TABLE, array('hp_id' => $hpid), __METHOD__);
		return $res;
	}
}
