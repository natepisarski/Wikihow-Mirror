<?php
/*
*
*/
class FBAppContact extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('FBAppContact');
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgFBAppId, $wgFBAppSecret, $wgSharedDB, $IP;
		$wgOut->setArticleBodyOnly(true);

		$accessToken = $wgRequest->getVal('token', null);
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
		$fields = array ('fc_user_id' => $profile['id'], 'fc_first_name' => $profile['first_name'], 'fc_last_name' => $profile['last_name'], 'fc_email' => $profile['email']);
		$dbw->insert('facebook_contacts', $fields, __METHOD__, array( 'IGNORE' ));
		return;
	}
}
