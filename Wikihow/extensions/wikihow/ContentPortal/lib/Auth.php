<?php
namespace ContentPortal;
use Curl\Curl;
class Auth {

	public $messages = [
		'emptypass' => "You have not provided a password",
		'throttled' => "You have tried too many times. Please try again later.",
		'noname'    => "You must enter a username",
		'notexists' => 'I could not find a user with that username',
		"wrongpass" => "Your password seems to be incorrect."
	];

	public $errors;

	function fromUserPass($username, $password) {
		$msg = '';
		$curl = new Curl();

		// \LoginForm::setLoginToken();
		// $token = \LoginForm::getLoginToken();

		//get token
		$curl->post(LOGIN_API, ["lgname" => $username, "lgpassword" => $password]);
		$result = json_decode($curl->response);
		$msg = $result->login->result;
		$token = isset($result->login->token) ? $result->login->token : '';
		$sessionid = isset($result->login->sessionid) ? $result->login->sessionid : '';
		$cookieprefix = isset($result->login->cookieprefix) ? $result->login->cookieprefix : '';

		// NOTE: this next block of code isn't right now.
		//
		// We disabled token authentication just for daikon.wikiknowhow.com in
		// SpecialUserlogin.php. We did this because we had a hard time getting
		// sessions to work with the API. But we think that the MW upgrade will
		// change this, so that we can undo the core hack for daikon and magically
		// this next bit of code will be used and work, and this comment can be
		// deleted. -Reuben, April 2019
		if ($msg == 'NeedToken' && $token) {
			//use token
			$curl->setCookie('sessionid', $sessionid);
			$curl->setCookie('cookieprefix', $cookieprefix);
			$curl->post(LOGIN_API, ["lgname" => $username, "lgpassword" => $password, "lgtoken" => $token]);
			$result = json_decode($curl->response);
			$msg = $result->login->result;
		}
		if ($msg == 'Success') {
			$user = User::find_by_username($username);
			Session::build($user);
			return $user;
		} else {
		  $this->errors = isset($this->messages[$msg]) ? $this->messages[$msg] : "There was a problem with those credentials.";
		  return null;
		}
	}

	static function findCurrentUser() {
		global $wgUser;

		if (isset($_SESSION[IMPERSONATE_ID])) return User::find($_SESSION[IMPERSONATE_ID]);
		if ($wgUser->isLoggedIn()) return User::find_by_wh_user_id($wgUser->getId());

		$sess = Session::findBySession();
		return $sess ? $sess->user : null;
	}

	static function destroy() {
		global $wgUser;
		if ($wgUser->isLoggedIn()) $wgUser->logout();
		Session::destroy();
	}
}
