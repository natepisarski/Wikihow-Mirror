<?php

class AdminImageLists extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminImageLists');
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute($par) {

		$userGroups = $this->getUser()->getGroups();
		$out = $this->getOutput();
		$request = $this->getRequest();

		if (!in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($request->wasPosted()) {
			$articles = $request->getVal('urls');
			$out->setArticleBodyOnly(true);

			$this->getAllResults($articles);

			return;

		} else {
			$options = array(
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
			);
			$m = new Mustache_Engine($options);

			$out->addHtml($m->render('adminimagelists.mustache'));
			$out->setPageTitle("Image List Maker");
		}

	}

	function getAllResults($articles) {
		$urls = explode("\n", $articles);

		if (count($urls) > 0) {

			$date = date('Y-m-d');
			$this->getOutput()->disable();
			header('Content-type: application/force-download');
			header('Content-disposition: attachment; filename="images_' . $date . '.xls"');

			echo "Page ID\tURL\tImage page\tImage Url\n";

			$dbr = wfGetDB(DB_REPLICA);
			foreach ($urls as $url) {
				$url = trim($url);
				$title = Misc::getTitleFromText(urldecode($url));
				if(!$title || !$title->exists() || $title->isRedirect()) continue;

				$id = $title->getArticleID();

				$res = $dbr->select('imagelinks', ['il_to'], ['il_from' => $id], __METHOD__);

				foreach($res as $row) {
					if($row->il_to == "LinkFA-star.jpg") continue;
					$imageTitle = Title::newFromText($row->il_to, NS_IMAGE);
					$file = wfFindFile($imageTitle);
					echo "{$id}\t{$url}\t{$imageTitle->getFullURL()}\t{$file->getFullUrl()}\n";
				}
			}
		}
	}
}
