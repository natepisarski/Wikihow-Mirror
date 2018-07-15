<?php

use SocialAuth\FacebookSocialUser;

class FBLink extends UnlistedSpecialPage {

	private $profile = null;
	private $fbApi;

	function __construct() {
		parent::__construct('FBLink');
	}

	public function execute($par) {
		wfProfileIn(__METHOD__);

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		$out->setRobotpolicy('noindex,nofollow');

		// Restrict page access
		$groups = $user->getGroups();
		if (!$req->wasPosted() || $user->isAnon() || $user->isBlocked() || !$req->getVal('token')) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->fbApi = new FacebookApiClient();
		$this->profile = $this->fbApi->getProfile($req->getVal('token'));

		if (!$this->profile) {
			Misc::jsonResponse(['error' => wfMessage('fbc_api_error')], 400);
			return;
		}

		$action = $req->getVal('a', '');
		switch ($action) {
			case 'confirm':
				$this->showConfirmation();
				break;
			case 'link':
				$this->linkAccounts();
				break;
			default:
				Misc::jsonResponse(['error' => 'Unrecognized action'], 400);
				return;
		}

		wfProfileOut(__METHOD__);
	}

	function linkAccounts() {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		$editToken = $req->getVal('editToken');
		if (!$user->matchEditToken($editToken)) {
			Misc::jsonResponse(['error' => wfMessage('fbc_api_suspicious')], 400);
			return;
		}

		$fbId = $this->profile['id'];
		$whId = $user->getId();

		// If the FB account was already linked to a WH account, unlink it first
		$socialUser = FacebookSocialUser::newfromExternalId($fbId);
		if ($socialUser) {
			$socialUser->unlink();
		}

		FacebookSocialUser::link($whId, $fbId);
		Misc::jsonResponse(['result' => 'success']);
	}

	function showConfirmation() {
		global $wgOut, $wgUser;
		EasyTemplate::set_path(dirname(__FILE__).'/');
		$vars = array();
		$this->setVars($vars);
		$html = EasyTemplate::html('FBLink_confirm', $vars);
		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHtml($html);
	}

	function setVars(&$vars) {
		global $wgUser, $wgIsDevServer;

		$debug = $wgIsDevServer;
		$vars['js'] = HtmlSnips::makeUrlTag('/extensions/wikihow/fblogin/fblink.js', $debug);
		$vars['css'] = HtmlSnips::makeUrlTag('/extensions/wikihow/fblogin/fblink.css', $debug);

		$result = $this->profile;
		$vars['fbName'] = $this->truncate($result['name']);
		$vars['fbEmployer'] = $this->truncate($result['work'][0]['employer']['name']);
		$vars['fbSchool'] = $this->truncate($result['education'][0]['school']['name']);
		$vars['fbEmail'] = $this->truncate($result['email']);
		$vars['fbLocation'] = $this->truncate($result['location']['name']);

		$vars['fbPicUrl'] = $this->fbApi->getAvatarUrl($result['id'], 'normal');
		$vars['newAcct'] = $wgUser->getName();
		$vars['whPicUrl'] = wfGetPad(Avatar::getAvatarURL($wgUser->getName()));

		$socialUser = FacebookSocialUser::newfromExternalId($result['id']);
		$whId = $socialUser ? $socialUser->getWhUser()->getId() : 0;
		$vars['showWarning'] = $whId ? true : false;
		$vars['oldAcct'] = $this->getUsername($whId);

		$vars['editToken'] = $wgUser->getEditToken();
	}


	function truncate($string) {
		$string = trim($string);
		if (strlen($string) > 25) {
			$string = substr($string, 0, 25) . "...";
		}
		return $string;
	}
	function getInfo(&$result, &$path) {
		$info = "";
		foreach ($path as $node) {
		}
		return $info;
	}

	function getUsername($uid) {
		$u = User::newFromId($uid);
		return $u->getName();
	}

	function showCTAHtml($template = 'FBLink_enable') {
		global $wgOut;
		$wgOut->addModules( 'ext.wikihow.FBEnable' );
		$html = "";
		if (self::isCompatBrowser()) {
			EasyTemplate::set_path(dirname(__FILE__).'/');
			$vars = array();
			$vars['imgUrl'] = wfGetPad('/skins/WikiHow/images/facebook_48.png');
			$html = EasyTemplate::html($template, $vars);
		}
		return $html;
	}

	// Disabled for IE6 due to css formatting issues
	public static function isCompatBrowser() {
		return !preg_match('@MSIE 6@',$_SERVER['HTTP_USER_AGENT']);
	}
}
