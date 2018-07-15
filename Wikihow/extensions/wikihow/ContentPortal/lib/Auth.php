<?
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
		$curl = new Curl();
		$curl->post(LOGIN_API, ["lgname" => $username, "lgpassword" => $password]);

		$result = json_decode($curl->response);
		$msg = strtolower($result->login->result);

		if ($msg == 'success') {
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
