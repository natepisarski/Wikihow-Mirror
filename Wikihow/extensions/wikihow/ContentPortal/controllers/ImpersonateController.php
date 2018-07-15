<?
namespace ContentPortal;
class ImpersonateController extends AppController {

	public $adminOnly = ['create'];

	function create() {
		$user = User::find($_GET['id']);
		$_SESSION[IMPERSONATE_ID] = $user->id;
		setFlash("You are now impersonating {$user->username}. Be careful what you do!", 'warning');
		$this->redirectTo('');
	}

	function delete() {
		unset($_SESSION[IMPERSONATE_ID]);
		setFlash('You are now your normal self.', 'success');
		$this->redirectTo('');
	}

}