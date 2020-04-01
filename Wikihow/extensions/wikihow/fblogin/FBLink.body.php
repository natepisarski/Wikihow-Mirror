<?php

use SocialAuth\FacebookSocialUser;

class FBLink extends UnlistedSpecialPage {

	private $profile = null;
	private $fbApi;

	public function __construct() {
		parent::__construct('FBLink');
	}

	public function execute($par) {

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		$out->setRobotPolicy('noindex,nofollow');

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
	}

	private function linkAccounts() {
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

	private function showConfirmation() {
		EasyTemplate::set_path(__DIR__.'/');
		$vars = array();
		$this->setVars($vars);
		$html = EasyTemplate::html('FBLink_confirm.tmpl.php', $vars);
		$this->getOutput()->setArticleBodyOnly(true);
		$this->getOutput()->addHtml($html);
	}

	private function setVars(&$vars) {
		global $wgIsDevServer;

		$user = $this->getUser();
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
		$vars['newAcct'] = $user->getName();
		$vars['whPicUrl'] = wfGetPad(Avatar::getAvatarURL($user->getName()));

		$socialUser = FacebookSocialUser::newfromExternalId($result['id']);
		$whId = $socialUser ? $socialUser->getWhUser()->getId() : 0;
		$vars['showWarning'] = $whId ? true : false;
		$vars['oldAcct'] = $this->getUsername($whId);

		$vars['editToken'] = $user->getEditToken();
	}


	private function truncate($string) {
		$string = trim($string);
		if (strlen($string) > 25) {
			$string = substr($string, 0, 25) . "...";
		}
		return $string;
	}

	private function getUsername($uid) {
		$u = User::newFromId($uid);
		return $u->getName();
	}

	public static function showCTAHtml($template = 'FBLink_enable') {
		RequestContext::getMain()->getOutput()->addModules( 'ext.wikihow.FBEnable' );
		$html = "";
		if (self::isCompatBrowser()) {
			EasyTemplate::set_path(__DIR__.'/');
			$vars = array();
			$vars['imgUrl'] = wfGetPad('/skins/WikiHow/images/facebook_48.png');
			$html = EasyTemplate::html($template, $vars);
		}
		return $html;
	}

	// Disabled for IE6 due to css formatting issues
	private static function isCompatBrowser() {
		return !preg_match('@MSIE 6@',$_SERVER['HTTP_USER_AGENT']);
	}
}
