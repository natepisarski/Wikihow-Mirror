<?php

use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\TemporaryPasswordAuthenticationRequest;

class AdminResetPassword extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminResetPassword');

		$this->mustache = new \Mustache_Engine( [
			'loader' => new \Mustache_Loader_FilesystemLoader( __DIR__ )
		] );
	}

	/**
	 * Resets a user's password (account found by username). The Logic here
	 * was lifted from LoginReminder.body.php (but it wasn't generalized
	 * there -- it was for email only).
	 *
	 * NOTE: this is called by CivicLogin and GoogleLogin too.
	 *
	 * @param $username string, the username
	 * @return a temporary password string to give to user
	 */
	public function resetPassword($username) {
		$performingUser = $this->getUser();
		$user = User::newFromName($username, false);
		if ($user && $user->getID() > 0) {
			$req = TemporaryPasswordAuthenticationRequest::newRandom();
			$req->username = $user->getName();
			$req->mailpassword = false;
			$req->caller = $performingUser->getName();
			$authManager = AuthManager::singleton();
			$status = $authManager->allowsAuthenticationDataChange( $req, true );
			if ( ! $status->isGood() || $status->getValue() === 'ignored' ) {
				return [ 'result' =>
					'Computer says <a href="https://www.youtube.com/watch?v=0n_Ty_72Qds">no</a>. ' .
					print_r($status->getErrors(), true) ];
			}

			// This is adding a new temporary password, not intentionally changing anything
			// (even though it might technically invalidate an old temporary password).
			$authManager->changeAuthenticationData( $req, /* $isAddition */ true );
			$newPassword = $req->password;
			return $newPassword;
		} else {
			return '';
		}
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$username = $req->getVal('username', '');
			$out->setArticleBodyOnly(true);
			$newPass = $this->resetPassword($username);
			if ($newPass) {
				$url = 'https://www.wikihow.com/Special:UserLogin';
				$params = [
					'username' => $username,
					'newPass' => $newPass,
					'url' => $url,
				];
				$htmlResponse = $this->mustache->render( 'reset_response.mustache', $params );
				$result = ['result' => $htmlResponse];
			} else {
				$result = ['result' => "error: user '{$username}' not found"];
			}
			$out->addHtml( json_encode($result) );
			return;
		}

		$out->setHTMLTitle('Admin - Reset User Password - wikiHow');
		$out->addModules('ext.wikihow.adminresetpassword');
		$out->addHTML( $this->mustache->render( 'adminresetpassword.mustache', [] ) );
	}
}
