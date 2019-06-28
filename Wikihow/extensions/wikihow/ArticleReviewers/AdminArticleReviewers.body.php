<?php

class AdminArticleReviewers extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminArticleReviewers');
	}

	public function execute($par) {
		global $wgCanonicalServer;

		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		$out->setRobotPolicy( 'noindex,nofollow' );

		if ($user && $user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ( !$user || $user->getID() == 0 || !in_array('staff', $user->getGroups()) || Misc::isIntl() ) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		// lame way to load this javascript, but resource loader was breaking it for some reason
		$out->addHeadItem('uploadify_script',
			'<script src="/extensions/wikihow/common/uploadify/jquery.uploadify.min.js"></script>');
		$out->addModules("ext.wikihow.adminarticlereviewers");

		$this->postSuccessful = true;
		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);

			$tempFile = $_FILES['Filedata']['tmp_name'];
			$fileTypes = array('jpg','jpeg','gif','png'); // File extensions
			$fileParts = pathinfo($_FILES['Filedata']['name']);
			if (in_array($fileParts['extension'],$fileTypes)) {
				$imageTitle = Title::newFromText($_FILES['Filedata']['name'], NS_IMAGE);
				if ($imageTitle->getArticleID() > 0) {
					$result['success'] = false;
					$result['message'] = "Image name already exists";
				} else {
					$file = new LocalFile($imageTitle, RepoGroup::singleton()->getLocalRepo());
					$file->upload($tempFile, '', '');
					$filesize = $file->getSize();
					if ( $filesize > 0 ) {
						$limit = array();
						$limit['move'] = "sysop";
						$limit['edit'] = "sysop";
						$wikiPage = WikiPage::factory($imageTitle);
						$cascade = false;
						$protectResult = $wikiPage->doUpdateRestrictions($limit, [], $cascade, "Used for Article Reviewers", $user)->isOK();
						$result['url'] = $wgCanonicalServer . $imageTitle->getLocalURL();
						$result['success'] = true;
					} else {
						$result['message'] = 'Unknown uploading error.';
						$result['success'] = false;
					}
				}
				print(json_encode($result));
				return;
			} else {
				$result['message'] = 'Invalid file type.';
				$result['success'] = false;
				print(json_encode($result));
				return;
			}

		}
		$out->setPageTitle('Article Reviewer Admin');

		$this->displayForm();
	}

	function displayForm() {
		global $wgOut;

		$tmpl = new EasyTemplate( __DIR__ );
		$html = $tmpl->execute('adminform.tmpl.php');

		$wgOut->addHTML($html);
	}
}
