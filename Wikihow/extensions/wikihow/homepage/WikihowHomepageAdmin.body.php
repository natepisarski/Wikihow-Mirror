<?php

class WikihowHomepageAdmin extends UnlistedSpecialPage {

	const HP_TABLE = "homepage";

	var $errorTitle;
	var $errorFile;
	var $postSuccessful;

	public function __construct() {
		parent::__construct('WikihowHomepageAdmin');
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
					$file->upload($req->getFileTempName('wpUploadFile'), '', '');
					$filesize = $file->getSize();
					if ($filesize > 0) {
						$dbw = wfGetDB(DB_MASTER);
						$dbw->insert(self::HP_TABLE, array('hp_page' => $title->getArticleID(), 'hp_image' => $imageTitle->getArticleID()));

						$wikiPage = WikiPage::factory($imageTitle);
						$limit = array();
						$limit['move'] = "sysop";
						$limit['edit'] = "sysop";
						$cascade = false;
						$protectResult = $wikiPage->doUpdateRestrictions($limit, [], $cascade, "Used on homepage", $user)->isOK();
					} else {
						$this->postSuccessful = false;
						$this->errorFile = "* We encountered an error uploading that file.";
					}
				}
			}
		}


		$useAjaxDestCheck = $wgAjaxUploadDestCheck;
		$useAjaxLicensePreview = $wgAjaxLicensePreview;

		$adc = wfBoolToStr( $useAjaxDestCheck );
		$alp = wfBoolToStr( $useAjaxLicensePreview );
		$out->setPageTitle('WikiHow Homepage Admin');
		$out->addScript( "<script type='text/javascript'>
wgAjaxUploadDestCheck = {$adc};
wgAjaxLicensePreview = {$alp};
</script>" );
		$out->addModules('jquery.ui.dialog');
		$out->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/common/ui/js/jquery-ui-1.8.custom.min.js'));
		$out->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/homepage/wikihowhomepageadmin.js'));
		$out->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/homepage/wikihowhomepageadmin.css'));
		$out->addScript(HtmlSnips::makeUrlTag('/skins/common/upload.js'));

		$this->displayHomepageData();

		$this->displayForm();
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

	private function displayHomepageData() {
		$results = $this->getHomepageData();

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'items' => $results
		));
		$html = $tmpl->execute('admin.tmpl.php');

		$this->getOutput()->addHTML($html);
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
