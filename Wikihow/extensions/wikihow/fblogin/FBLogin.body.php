<?php

class FBLogin extends UnlistedSpecialPage {

	private $fbApi;
	private $authToken;

	public function __construct($source = null) {
		parent::__construct( 'FBLogin' );
	}

	public function isMobileCapable() {
		return true;
	}

	public function execute($par) {
		global $wgContLang;

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		if (!$req->wasPosted()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$this->authToken = $req->getVal('token', '');
		if (!$this->authToken) {
			$out->addHTML('An error occurred.');
			return;
		}

		$this->fbApi = new FacebookApiClient();
		$profile = $this->fbApi->getProfile($this->authToken);
		if (!$profile) {
			$out->addHtml(wfMessage('fbc_api_error'));
			return;
		}

		$out->setHTMLTitle(wfMessage('fblogin_page_title'));

		$action = $req->getVal('action');
		if ($action == 'login') {
			$res = SocialLoginUtil::doSocialLogin('facebook', $profile['id'], $profile['name'],
				$profile['email'], $this->fbApi->getAvatarUrl($profile['id']));
			$isSignup = ($res == 'signup');
			if ($res == 'error') {
				$out->addHTML('The login process failed');
				return;
			} elseif ($isSignup
						|| $user->getBoolOption('is_generated_username')
						|| strpos($user->getName(), "FB_") !== false) {
				$currentPic = Avatar::getAvatarURL($user->getName());
				$defaultPic = Avatar::getDefaultProfile();
				$this->showForm( // Suggest a username change
					$profile,
					$user->getEmail() ?: $profile['email'],
					($currentPic != $defaultPic) ? $currentPic : $profile['picture'],
					$isSignup
				);
			} else {
				SocialLoginUtil::redirect($req->getText('returnTo'), $isSignup);
			}
		} elseif ($action == 'updateDetails') {
			$this->processForm($profile);
		} else {
			$out->addHTML('Unsupported action.');
		}
	}

	private function showForm(&$profile, $email = '', $avatar = '', $isSignup, $username = null, $error = '') {
		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		$username = $username ?: SocialLoginUtil::createUsername($profile['name']);
		if (!$username) {
			$out->addHTML(wfMessage('sl_username_generation_failed')->text());
			return;
		}
		$email = $email ? $email : $profile['email'];
		$picture = $avatar ?: $this->fbApi->getAvatarUrl($profile['id']);
		$token = $this->authToken;
		$isMobile = Misc::isMobileMode();
		$returnTo = ($isSignup && !$isMobile) ? '' : $req->getText('returnTo');
		//$affiliations = $this->getAffiliations($profile);
		$affiliations = "";
		if (strlen($affiliations)) {
			$affiliations .= ' &middot;';
		}

		$fbicon = wfGetPad('/skins/WikiHow/images/facebook_share_icon.gif');
		$isApiSignup = $user->getOption('is_api_signup');
		$formUrl = SpecialPage::getTitleFor('FBLogin')->getFullURL('', false, PROTO_HTTPS);

		$tmpl = new EasyTemplate(__DIR__);
		$tmpl->set_vars(compact(
			'token', 'returnTo', 'isSignup', 'fbicon', 'username', 'error', 'picture', 'affiliations', 'email', 'isMobile', 'isApiSignup', 'formUrl'
		));
		$out->addHtml($tmpl->execute('FBLogin_form_prefill.tmpl.php'));

		$out->addModules('ext.wikihow.FBLogin');
		$out->addModuleStyles('ext.wikihow.FBLogin.styles');
		if ($isMobile) {
			$out->addModuleStyles('ext.wikihow.mobile.FBLogin.styles');
		}

		$out->addHtml($tags);
		$out->addHtml($html);
	}

	private function processForm(&$profile) {
		$user = $this->getUser();

		$req = $this->getRequest();
		$dbw = wfGetDB(DB_MASTER);
		$userOverride = strlen($req->getVal('requested_username'));
		$newname = $userOverride ? $req->getVal('requested_username') : $req->getVal('proposed_username');
		$newname = $dbw->strencode($newname);
		$email = $dbw->strencode($req->getVal('email'));
		$avatar = $req->getVal('fbc_user_avatar');
		$isSignup = $req->getBool('isSignup');

		$newname = User::getCanonicalName($newname, 'creatable');

		if ($newname == false) {
			$i18nErrorCode = 'fbc_username_not_valid';
		} elseif (!SocialLoginUtil::isAvailableUsername($newname)) {
			$i18nErrorCode = 'fbc_username_inuse';
		}

		if (isset($i18nErrorCode)) {
			$this->showForm($profile, $email, $avatar, $isSignup, $newname, wfMessage($i18nErrorCode, $newname));
			return;
		}

		$authenticatedTimeStamp = wfTimestampNow();
		$dbw->update('user',
			array('user_name' => $newname, 'user_email' => $email, 'user_email_authenticated' => $authenticatedTimeStamp),
			array('user_id' => $user->getID()),
			__METHOD__
			);

		$user->invalidateCache();
		$user->loadFromID();

		$user->setOption('is_generated_username', false);
		$user->setOption('is_api_signup', false);
		$user->saveSettings();
		$user->setCookies();

		if ($user->isEmailConfirmed()) {
			Hooks::run('ConfirmEmailComplete', array($user));
		}

		// All registered. Send them along their merry way
		SocialLoginUtil::redirect($req->getText('returnTo'), $isSignup);
	}

}
