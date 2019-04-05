<?php

use SocialAuth\GoogleSocialUser;

class GPlusLogin extends UnlistedSpecialPage {

	public function __construct($source = null) {
		parent::__construct( 'GPlusLogin' );
	}

	public function isMobileCapable() {
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute($par) {
		global $wgLanguageCode, $wgContLang, $wgUser;

		$out = $this->getOutput();
		$req = $this->getRequest();

		if ($req->getVal('disconnect')) {
			$this->unlinkGoogleAccount();
			return;
		}

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

		$this->prof = GoogleApiClient::getProfile($this->authToken);
		if (!$this->prof) {
			$out->addHTML(wfMessage('google_login_failed')->text());
			return;
		}

		$out->setHTMLTitle(wfMessage('gpl_page_title'));

		$action = $req->getVal('action');
		if ($action == 'login') {
			$res = SocialLoginUtil::doSocialLogin('google', $this->prof['id'], $this->prof['name'] ?? '',
				$this->prof['email'], $this->prof['picture']);
			$isSignup = ($res == 'signup');
			if ($res == 'error') {
				$out->addHTML('The login process failed');
				return;
			} elseif ($isSignup
						|| $wgUser->getBoolOption('is_generated_username')
						|| strpos($wgUser->getName(), "GP_") !== false) {
				$currentPic = Avatar::getAvatarURL($wgUser->getName());
				$defaultPic = Avatar::getDefaultProfile();
				$this->showForm( // Suggest a username change
					$this->prof['name'],
					$wgUser->getEmail() ?: $this->prof['email'],
					($currentPic != $defaultPic) ? $currentPic : $this->prof['picture'],
					$isSignup
				);
			} else {
				SocialLoginUtil::redirect($req->getText('returnTo'), $isSignup);
			}
		} elseif ($action == 'updateDetails') {
			$this->processForm();
		} else {
			$out->addHTML('Unsupported action.');
		}
	}

	// The form for Google+ login user to select their wikiHow user stuff
	private function showForm($fullName, $email, $avatar, $isSignup, $error = '') {
		global $wgUser;
		$out = $this->getOutput();
		$req = $this->getRequest();

		$formUrl = SpecialPage::getTitleFor('GPlusLogin')->getFullURL('', false, PROTO_HTTPS);
		$origname = $fullName;
		// Make sure we have a good username
		$username = SocialLoginUtil::createUsername($fullName);
		if (!$username) {
			$out->addHTML(wfMessage('sl_username_generation_failed')->text());
			return;
		}
		$isMobile = Misc::isMobileMode();
		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'formUrl' => $formUrl,
			'username' => $username,
			'origname' => $origname,
			'avatar' => $avatar,
			'email' => $email,
			'error' => $error,
			'isMobile' => $isMobile,
			'token' => $this->authToken,
			'returnTo' => ($isSignup && !$isMobile) ? '' : $req->getText('returnTo'),
			'isSignup' => $isSignup,
			'isApiSignup' => $wgUser->getOption('is_api_signup')
		));
		$html = $tmpl->execute('gplusform.tmpl.php');

		$out->addModules('ext.wikihow.GPlusLogin');
		$out->addModuleStyles('ext.wikihow.GPlusLogin.styles');
		if ($isMobile) {
			$out->addModuleStyles('ext.wikihow.mobile.GPlusLogin.styles');
		}

		$out->addHtml($html);
	}

	private function processForm() {
		global $wgEmailAuthentication, $wgUser;

		$req = $this->getRequest();

		$dbw = wfGetDB(DB_MASTER);

		$userOverride = strlen($req->getVal('requested_username'));
		$originalName = $userOverride
			? $req->getVal('requested_username')
			: $req->getVal('proposed_username');
		$newname = $dbw->strencode($originalName);
		$email = $dbw->strencode($req->getVal('email'));

		$newname = User::getCanonicalName($newname, 'creatable');
		$isSignup = $req->getBool('isSignup');

		if ($newname == false) {
			$i18nErrorCode = 'gplusconnect_username_not_valid';
		} elseif (!SocialLoginUtil::isAvailableUsername($newname)) {
			$i18nErrorCode = 'gplusconnect_username_inuse';
		}

		if (isset($i18nErrorCode)) {
			$this->showForm($req->getVal('original_username'),
							$req->getVal('email'),
							$req->getVal('avatar_url'),
							$isSignup,
							wfMessage($i18nErrorCode, $originalName));
			return;
		}
		$dbw->update('user',
			array('user_name' => $newname, 'user_email' => $email),
			array('user_id' => $wgUser->getID()),
			__METHOD__
		);

		$wgUser->invalidateCache();
		$wgUser = User::newFromName($newname);

		$wgUser->setOption('is_generated_username', false);
		$wgUser->setOption('is_api_signup', false);
		$wgUser->saveSettings();

		$wgUser->setCookies();

		if ($wgEmailAuthentication && Sanitizer::validateEmail($wgUser->getEmail())) {
			$wgUser->sendConfirmationMail();
		}

		SocialLoginUtil::redirect($req->getText('returnTo'), $isSignup);
	}

	private function unlinkGoogleAccount() {
		global $wgDBname, $wgUser;

		if ($wgUser->getID() == 0) {
			return;
		}

		$googleSocialUser = GoogleSocialUser::newFromWhId($wgUser->getID());
		if ($googleSocialUser) {
			$googleSocialUser->unlink();
		}

		// Display confirmation message and temporary password
		$newpass = AdminResetPassword::resetPassword($wgUser->getName());
		$html = '<p><b>Your disconnected login name:</b> '.$wgUser->getName().'</p>'.
				'<p><b>Your temporary password:</b> '.$newpass.'</p>'.
				'<p>Copy it down and then use it to <a href="/Special:Userlogin">login here</a>.';
		$this->getOutput()->addHTML($html);
	}

}
