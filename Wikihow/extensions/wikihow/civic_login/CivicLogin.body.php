<?php

use SocialAuth\CivicSocialUser;

class CivicLogin extends UnlistedSpecialPage {

	const LOGIN_TYPE = 'civic';

	public function __construct($source = null) {
		parent::__construct('CivicLogin');
	}

	public function isMobileCapable() {
		return true;
	}

	public function execute($par) {
		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		if ($req->getVal('disconnect')) {
			$this->unlinkCivicAccount();
			return;
		}

		if (!$req->wasPosted()) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$this->authToken = $req->getVal('token', '');
		if (!$this->authToken) {
			$out->addHTML('An error occurred.');
			return;
		}

		$this->prof = CivicApiClient::getProfile($this->authToken);
		if (!$this->prof) {
			$out->addHTML(wfMessage('cl_login_failed')->text());
			return;
		}

		$out->setHTMLTitle(wfMessage('cl_page_title')->text());

		$action = $req->getVal('action');
		if ($action == 'login') {
			$res = SocialLoginUtil::doSocialLogin(
				self::LOGIN_TYPE,
				$this->prof['id'],
				$this->prof['name'],
				$this->prof['email'],
				$this->prof['picture']
			);
			$isSignup = $res == 'signup';
			if ($res == 'error') {
				$out->addHTML(wfMessage('cl_login_failed')->text());
				return;
			} elseif ($isSignup
						|| $user->getBoolOption('is_generated_username')
						|| strpos($user->getName(), "GP_") !== false) {
				$currentPic = Avatar::getAvatarURL($user->getName());
				$defaultPic = Avatar::getDefaultProfile();
				$this->showForm(
					// Suggest a username change
					$this->prof['name'],
					$user->getEmail() ?: $this->prof['email'],
					($currentPic != $defaultPic) ? $currentPic : $this->prof['picture'],
					$isSignup
				);
			} else {
				SocialLoginUtil::redirect($req->getText('returnTo'), $isSignup);
			}
		} elseif ($action == 'updateDetails') {
			$this->processForm();
		} else {
			$out->addHTML(wfMessage('cl_unsupported_action')->text());
		}
	}

	/**
	 * Outputs an html form to allow Civic user to set up a wikiHow account
	 * @param $fullName
	 * @param $email
	 * @param $avatar
	 * @param $isSignup
	 * @param string $error
	 */
	private function showForm($fullName, $email, $avatar, $isSignup, $error = '') {
		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		$formUrl = SpecialPage::getTitleFor('CivicLogin')->getFullURL('', false, PROTO_HTTPS);
		$origname = $fullName;

		// Make sure we have a good username
		$username = SocialLoginUtil::createUsername($fullName);
		if (!$username) {
			$out->addHTML(wfMessage('sl_username_generation_failed')->text());
			return;
		}
		$isMobile = Misc::isMobileMode();
		$tmpl = new EasyTemplate( dirname(__FILE__) );
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
			'isApiSignup' => $user->getOption('is_api_signup')
		));
		$html = $tmpl->execute('civic_login.tmpl.php');

		$out->addModules('ext.wikihow.CivicLogin');
		$out->addModuleStyles('ext.wikihow.CivicLogin.styles');
		if ($isMobile) {
			$out->addModuleStyles('ext.wikihow.mobile.CivicLogin.styles');
		}

		$out->addHtml($html);
	}

	private function processForm() {
		global $wgEmailAuthentication;

		$user = $this->getUser();
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
			$i18nErrorCode = 'cl_username_not_valid';
		} elseif (!SocialLoginUtil::isAvailableUsername($newname)) {
			$i18nErrorCode = 'cl_username_inuse';
		}

		if (isset($i18nErrorCode)) {
			$this->showForm($req->getVal('original_username'),
							$req->getVal('email'),
							$req->getVal('avatar_url'),
							$isSignup,
							wfMessage($i18nErrorCode, $originalName)->text());
			return;
		}

		$dbw->update('user',
			array('user_name' => $newname, 'user_email' => $email),
			array('user_id' => $user->getID()),
			__METHOD__
		);

		$user->invalidateCache();
		$user = User::newFromName($newname);

		$user->setOption('is_generated_username', false);
		$user->setOption('is_api_signup', false);

		// Confirm email for civic users since the email confirmation is already done as part of the civic signup
		if ($wgEmailAuthentication && Sanitizer::validateEmail($user->getEmail())) {
			$user->confirmEmail();
		}

		$user->saveSettings();

		$user->setCookies();

		SocialLoginUtil::redirect($req->getText('returnTo'), $isSignup);
	}

	private function unlinkCivicAccount() {
		global $wgDBname;

		$user = $this->getUser();
		if ($user->getID() == 0) {
			return;
		}

		CivicSocialUser::newFromWhId($user->getID())->unlink();

		// Display confirmation message and temporary password
		$newpass = AdminResetPassword::resetPassword($user->getName());
		$html = '<p><b>Your disconnected login name:</b> ' . $user->getName() . '</p>'.
				'<p><b>Your temporary password:</b> '.$newpass.'</p>'.
				'<p>Copy it down and then use it to <a href="/Special:Userlogin">login here</a>.';
		$this->getOutput()->addHTML($html);
	}

	public static function isEnabled() {
		global $wgLanguageCode;
		return $wgLanguageCode == 'en';
	}

}
