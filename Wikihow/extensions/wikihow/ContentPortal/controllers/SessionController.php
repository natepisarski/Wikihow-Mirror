<?
namespace ContentPortal;
class SessionController extends AppController {

	public $postRoutes = ['create'];
	public $layout = "session";

	function beforeRender() {}

	function _new() {
		if (Auth::findCurrentUser()) $this->redirectTo('');
	}

	function create() {
		$auth = new Auth();
		$user = $auth->fromUserPass($this->params('user[username]'), $this->params('user[password]'));

		if ($user) {
			$this->redirectTo(isset($_SESSION[REDIRECT_URL]) ? $_SESSION[REDIRECT_URL] : '');
		} else {
			setFlash($auth->errors, 'danger');
			$this->render('session/new', $this->layout);
		}
	}

	function destroy() {
		Auth::destroy();
		$this->redirectTo(LOGIN_PATH);
	}
}
