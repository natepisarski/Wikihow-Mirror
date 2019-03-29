<?php

class WikitextDownloader extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('WikitextDownloader');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		if (!self::isAuthorized()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$r = Revision::loadFromPageId($dbr, $req->getInt('pageid'));
		if ($r) {
			$title = $r->getTitle()->getText();
			Misc::outputFile("$title.txt", $r->getText(), "application/force-download");
		}
	}

	public static function isAuthorized() {
		$user = RequestContext::getMain()->getUser();
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('translator', $userGroups)) {
			return false;
		} else {
			return true;
		}
	}

}
