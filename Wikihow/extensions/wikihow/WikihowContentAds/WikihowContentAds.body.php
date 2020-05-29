<?php

class WikihowContentAds extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'WikihowContentAds');
	}

	public function execute($par) {
		global $isDevDomain;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		if ($req->wasPosted()) {

			$action = $req->getVal('action');
			$out->setArticleBodyOnly(true);

			if ('save-email' == $action) {
				$email = $req->getVal('email', '');
				$campaign = $req->getVal('campaign', '');

				if($email != '' && $campaign != '') {
					$spreadsheetId = '1QXrKKyu_XJl81nlahRQMRhweFZ9N9DV8GN_QjPRzix0';
					$newRow = array(
						$campaign,
						$email,
						date('Y-m-d'),
						$isDevDomain ? "dev" : "live",
						$user->isLoggedIn() ? "logged in" : "logged out"
					);

					$sheet = 'homepage (do not edit)';
					GoogleSheets::appendRows($spreadsheetId, $sheet, [$newRow]);
				}
			}
		}
	}

}
