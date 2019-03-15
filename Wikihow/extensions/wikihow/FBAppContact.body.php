<?php

class FBAppContact extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('FBAppContact');
	}

	public function execute($par) {
		global $wgSharedDB;

		$req = $this->getRequest();
		$out = $this->getOutput();

		$out->setArticleBodyOnly(true);

		$accessToken = $req->getVal('token', null);
		if (is_null($accessToken)) {
			return;
		}

		$fbApi = new FacebookApiClient();
		$profile = $fbApi->getProfile($accessToken);
		if (!$profile) {
			return;
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->selectDB($wgSharedDB);
		$fields = array('fc_user_id' => $profile['id'], 'fc_first_name' => $profile['first_name'], 'fc_last_name' => $profile['last_name'], 'fc_email' => $profile['email']);
		$dbw->insert('facebook_contacts', $fields, __METHOD__, array( 'IGNORE' ));
		return;
	}
}
