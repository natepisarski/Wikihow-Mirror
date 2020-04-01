<?php
namespace ContentPortal;
use MVC\Controller;
use __;
class AppController extends Controller {

	public $adminOnly = [];

	function beforeRender() {
		$this->currentUser = Auth::findCurrentUser();
		$this->buildRoutes();

		if (!$this->currentUser) {
			if ($this->isStaffUser()) {
				$this->createUserForStaff();
			} elseif ($this->isEditorPersonalUser()) {
				$this->createUserForEditorPersonal();
			} else {
				$this->render('session/new', 'session');
				$this->continue = false;
				$this->redirectTo('https://daikon.wikiknowhow.com/index.php?title=Special:UserLogin&returnto=Special:ContentPortal');
			}

		} elseif ($this->currentUser->disabled) {
			$this->render404();

		} else {
			$this->currentUser->touch();
			$this->ensureAdmin();
		}
	}

	public function render404() {
		$this->out->setStatusCode(404);
		$this->render('shared/404', false);
		$this->continue = false;
		return;
	}

	function buildRoutes() {
		$this->routes = [
			'users_filter'        => url('users/filter'),
			'articles_suggest'    => url('articles/suggest', ['title_search' => 'QUERY']),
			'articles_assign'     => url('articles/assign'),
			'articles_search'     => url('articles/search'),
			'export_form'         => url('exports/form'),
			'import_create'       => url('imports/create'),
			'reservations_create' => url('reservations/create')
		];
	}

	function ensureAdmin() {
		if ($this->isAdminPage() && !$this->currentUser->isAdmin()) {
			$this->render404();
		}
	}

	function isEditorPersonalUser() {
		global $wgUser;
		return in_array('editorpersonal', $wgUser->getGroups());
	}

	function isStaffUser() {
		global $wgUser;
		return in_array('staff', $wgUser->getGroups());
	}

	function createUserForStaff() {
		global $wgUser;
		$this->currentUser = User::create([
			'wh_user_id' => $wgUser->getId(),
			'username' => $wgUser->getName(),
			'category_id' => Category::first()->id
		], false);

		UserRole::create(['user_id' => $this->currentUser->id, 'role_id' => Role::findByTitle('admin')->id]);
		$this->redirectTo('users/edit', ['id' => $this->currentUser->id, 'from_staff' => true]);
	}

	function createUserForEditorPersonal() {
		global $wgUser;
		$this->currentUser = User::create([
			'wh_user_id' => $wgUser->getId(),
			'username' => $wgUser->getName(),
			'category_id' => Category::first()->id
		], false);

		UserRole::create(['user_id' => $this->currentUser->id, 'role_id' => Role::findByTitle('editor')->id]);
		$this->redirectTo('users/edit', ['id' => $this->currentUser->id, 'from_staff' => false]);
	}

	public function info() {
		// Reuben, 3/2019: dumping out this phpinfo() is a terrible idea for security. it
		// can include server-side passwords, etc
		print "No info here! " . __METHOD__;
		exit();
	}

	function isAdminPage() {
		return in_array($this->action, $this->adminOnly) || in_array('*', $this->adminOnly);
	}

}
