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
				$test = $req->getVal('test', '');

				if($email != '' && $campaign != '') {
					if($test == "category") {
						$spreadsheetId = '14-HmTYfwXSChjFqT-LFMtHtKHxQSG1scWpo3rYRwslM';
						$sheet = 'Email Intake (do not edit)';
					} else {
						$spreadsheetId = '1QXrKKyu_XJl81nlahRQMRhweFZ9N9DV8GN_QjPRzix0';
						$sheet = 'homepage (do not edit)';
					}
					$newRow = array(
						$campaign,
						$email,
						date('Y-m-d'),
						$isDevDomain ? "dev" : "live",
						$user->isLoggedIn() ? "logged in" : "logged out"
					);

					GoogleSheets::appendRows($spreadsheetId, $sheet, [$newRow]);
				}
			}
		}
	}

}
