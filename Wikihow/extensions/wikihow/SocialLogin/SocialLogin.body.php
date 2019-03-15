<?php

use SocialAuth\SocialUser;

/**
 * Endpoint to perform automated social logins/signups
 */
class SocialLogin extends UnlistedSpecialPage {

	private static $jsonpCallback = 'wh_jsonp_social';

	public function __construct() {
		parent::__construct('SocialLogin');
	}

	public function isMobileCapable() {
		return true;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();

		if ($req->getVal('callback') != self::$jsonpCallback) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$action = $req->getVal('action');
		if ($action == 'login') {
			$this->apiLogin($req);
		} else {
			$this->apiError([1, "Unrecognized action '$action'"]);
		}
	}

	private function apiLogin($req) {
		global $wgUser;

		if ($wgUser->isLoggedIn()) {
			$this->apiError([2, "Already logged in"]);
			return;
		}

		$type = $req->getVal('type');
		if ($type == 'facebook') {
			$loginResult = $this->facebookLogin($req);
		} elseif ($type == 'google') {
			$loginResult = $this->googleLogin($req);
		} elseif ($type == 'civic') {
			$loginResult = $this->civicLogin($req);
		}
		else {
			$this->apiError([3, "Unrecognized login platform '$type'"]);
			return;
		}

		if ($loginResult == 'error') {
			$this->apiError([4, "The social login process failed"]);
		} else {
			if ($loginResult == 'signup') {
				// Set a temporary flag to show different text in the FB/G signup pages
				$wgUser->setOption('is_api_signup', true);
				$wgUser->saveSettings();
			}
			self::apiLoginSuccess($loginResult, $type);
		}
	}

	private function facebookLogin($req) {
		$token = $req->getVal('authToken', '');
		$fbApi = new FacebookApiClient();
		$profile = $fbApi->getProfile($token);
		if (!$profile) {
			$this->apiError([5, "Token validation failed"]);
		}

		return SocialLoginUtil::doSocialLogin('facebook', $profile['id'],
			$profile['name'],
			$profile['email'],
			$fbApi->getAvatarUrl($profile['id'])
		);
	}

	private function googleLogin($req) {
		$token = $req->getVal('authToken', '');
		$profile = GoogleApiClient::getProfile($token);
		if (!$profile) {
			$this->apiError([5, "Token validation failed"]);
		}

		return SocialLoginUtil::doSocialLogin('google', $profile['id'],
			$profile['name'],
			$profile['email'],
			$profile['picture']
		);
	}

	private function civicLogin($req) {
		$token = $req->getVal('authToken', '');
		$profile = CivicApiClient::getProfile($token);
		if (!$profile) {
			$this->apiError([5, "Token validation failed"]);
		}

		return SocialLoginUtil::doSocialLogin('civic', $profile['id'],
			$profile['name'],
			$profile['email'],
			$profile['picture']
		);
	}

	/**
	 * Return a 200 response to the client, with a JSON-encoded object as the message body
	 */
	private function apiLoginSuccess(string $result, string $type) {
		global $wgUser;

		$data = [
			'result' => $result,
			'type' => $type,
			'user' => [
				'userId' => $wgUser->getId(),
				'username' => $wgUser->getName(),
				'realName' => $wgUser->getRealName(),
				'avatarUrl' => Avatar::getAvatarURL($wgUser->getName())
			]
		];
		Misc::jsonResponse($data, 200, self::$jsonpCallback);
	}

	/**
	 * Write a API error in JSON format to the output
	 *
	 * @param array $error  e.g. [123, 'Error message']
	 *     The message is shown only in dev servers, and the numeric error in production,
	 *     so we can debug live issues without giving away information to attackers.
	 */
	private function apiError(array $error) {
		global $wgIsDevServer;

		$data = ['result' => 'error', 'msg' => ($wgIsDevServer ? $error[1] : $error[0])];
		Misc::jsonResponse($data, 400, self::$jsonpCallback);
	}
}

