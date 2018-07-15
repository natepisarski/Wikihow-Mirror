<?php

class WikihowHomepageAdmin extends UnlistedSpecialPage {

	const HP_TABLE = "homepage";

	var $errorTitle;
	var $errorFile;
	var $postSuccessful;

	function __construct() {
		parent::__construct('WikihowHomepageAdmin');
	}

	function execute($par) {
		global $wgOut, $wgUser, $wgRequest;
		global $wgUseAjax, $wgAjaxUploadDestCheck, $wgAjaxLicensePreview;

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ($wgUser->getID() == 0) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if(!in_array('staff', $wgUser->getGroups())) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->errorFile = "";
		$this->errorTitle = "";

		if ($wgRequest->getVal('delete')) {
			$wgOut->setArticleBodyOnly(true);
			$hpid = str_replace('delete_','',$wgRequest->getVal('delete'));
			$html = self::deleteHPImage($hpid);
			$wgOut->addHTML($html);
			return;
		}
		
		
		$this->postSuccessful = true;
		if($wgRequest->wasPosted()) {
			if($wgRequest->getVal("updateActive")) {

				$dbw = wfGetDB(DB_MASTER);
				//first clear them all
				$dbw->update(WikihowHomepageAdmin::HP_TABLE, array('hp_active' => 0, 'hp_order' => 0), '*', __METHOD__);

				$images = $wgRequest->getArray("hp_images");
				$count = 1;
				foreach($images as $image) {
					if (!$image) continue;
					$dbw->update(WikihowHomepageAdmin::HP_TABLE, array('hp_active' => 1, 'hp_order' => $count), array('hp_id' => $image));
					$count++;
				}
			}
			else {
				$title = WikiPhoto::getArticleTitleNoCheck($wgRequest->getVal('articleName'));
				if(!$title->exists()) {
					$this->postSuccessful = false;
					$this->errorTitle = "* That article does not exist.";
				}

				if($this->postSuccessful) {
					//keep going
					$imageTitle = Title::newFromText($wgRequest->getVal('wpDestFile'), NS_IMAGE);
					$file = new LocalFile($imageTitle, RepoGroup::singleton()->getLocalRepo());
					$file->upload($wgRequest->getFileTempName('wpUploadFile'), '', '');
					$filesize = $file->getSize();
					if($filesize > 0) {
						$dbw = wfGetDB(DB_MASTER);
						$dbw->insert(WikihowHomepageAdmin::HP_TABLE, array('hp_page' => $title->getArticleID(), 'hp_image' => $imageTitle->getArticleID()));

						$article = new Article($imageTitle);
						$limit = array();
						$limit['move'] = "sysop";
						$limit['edit'] = "sysop";
						$protectResult = $article->updateRestrictions($limit, "Used on homepage");
					}
					else {
						$this->postSuccessful = false;
						$this->errorFile = "* We encountered an error uploading that file.";
					}
				}
			}
		}


		$useAjaxDestCheck = $wgUseAjax && $wgAjaxUploadDestCheck;
		$useAjaxLicensePreview = $wgUseAjax && $wgAjaxLicensePreview;

		$adc = wfBoolToStr( $useAjaxDestCheck );
		$alp = wfBoolToStr( $useAjaxLicensePreview );
		$wgOut->setPageTitle('WikiHow Homepage Admin');
		$wgOut->addScript( "<script type=\"text/javascript\">
wgAjaxUploadDestCheck = {$adc};
wgAjaxLicensePreview = {$alp};
</script>" );
		$wgOut->addModules('jquery.ui.dialog');
		$wgOut->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/common/ui/js/jquery-ui-1.8.custom.min.js'));
		$wgOut->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/homepage/wikihowhomepageadmin.js'));
		$wgOut->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/homepage/wikihowhomepageadmin.css'));
		$wgOut->addScript(HtmlSnips::makeUrlTag('/skins/common/upload.js'));

		$this->displayHomepageData();

		$this->displayForm();


	}

	function getHomepageData() {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select(WikihowHomepageAdmin::HP_TABLE, '*', '', __METHOD__, array('ORDER BY' => 'hp_active DESC,hp_order'));

		$results = array();
		foreach($res as $item) {
			$item->title = Title::newFromID($item->hp_page);
			$imageTitle = Title::newFromID($item->hp_image);
			if ($imageTitle) {
				$file = wfFindFile($imageTitle->getText());
				if($file) {
					$thumb = $file->getThumbnail(81, 54, true, true, true);
					$item->file = wfGetPad($thumb->getUrl());
					$results[] = $item;
				}
			}
		}

		return $results;
	}

	function displayHomepageData() {
		global $wgOut;

		$results = $this->getHomepageData();

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'items' => $results
		));
		$html = $tmpl->execute('admin.tmpl.php');

		$wgOut->addHTML($html);

	}

	function displayForm() {
		global $wgOut, $wgRequest;

		$articleName = "";
		if($this->errorTitle != "")
			$articleName = $wgRequest->getVal('articleName');

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'errorTitle' => $this->errorTitle,
			'errorFile' => $this->errorFile,
			'articleName' => $articleName
		));
		$html = $tmpl->execute('form.tmpl.php');

		$wgOut->addHTML($html);
	}
	
	function deleteHPImage($hpid) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->delete(WikihowHomepageAdmin::HP_TABLE, array('hp_id' => $hpid), __METHOD__);
		return $res;
	}
}
