<?php
namespace ContentPortal;
use MVC\Paginator;
use __;

class UsersController extends AppController {

	public $adminOnly = ['*'];

	function beforeRender() {
		parent::beforeRender();

		if (!in_array($_GET['action'], ['index', 'filter'])) {
			$this->roles = __::filter(Role::all(), function ($role) {
				return $role->public || $role->key == Role::ADMIN_KEY;
			});
			$this->categories = Category::find('all', ['order' => 'title ASC']);
		}
	}

	function index() {

		Paginator::$perPage = PER_PAGE;
		Paginator::$total = User::count(['disabled' => $this->params('disabled', false)]);

		$this->users = User::all([
			'conditions' => ['disabled' => $this->params('disabled', false)],
			'include'    => ['category', 'active_articles', 'user_roles' => ['role']],
			'limit'      => Paginator::$perPage,
			'offset'     => Paginator::getOffset(),
			'order'      => Paginator::getSort('username ASC')
		]);

		$this->fullScreen = true;
		$this->users = Paginator::sort($this->users);
	}

	function _new() {
		$this->user = new User();
	}

	function edit() {
		$this->user = User::find($_GET['id']);
	}

	function filter() {
		if ($this->params('role_id')) {

			$role = Role::find($this->params('role_id'));
			$users = $role->enabledUsers();

			$this->renderJSON([
				"enabled"  => in_array($role->key, [Role::COMPLETE_VERIFIED_KEY, Role::COMPLETE_KEY]) ? false : true,
				"users"    => __::invoke($users, 'attributes'),
				"role"     => $role->attributes(),
				"admins"   => __::invoke(Role::admin()->users, 'attributes')
			]);

		} else {
			$this->render404();
		}
	}

	function info() {
		$this->user = User::find($this->params('id'));
		$this->render('users/info', false);
	}

	function update() {
		$this->user = User::find($_GET['id']);

		if ($this->user->update_attributes($_POST['user'])) {
			$this->user->save();
			$this->updateAssoc();
			$this->updateMediaWikiUser($this->user);
			setFlash('Your changes have been saved', 'success');
			$this->redirectTo('users');
		} else {
			$this->errors = $this->user->errors->get_raw_errors();
			$this->render('users/edit');
		}
	}

	function create() {
		$this->user = new User($_POST['user']);

		if ($this->user->save()) {
			$this->updateAssoc();
			$this->updateMediaWikiUser($this->user);
			setFlash('Your changes have been saved', 'success');
			$this->redirectTo('users', ['category' => $this->article->category_id, 'state' => $this->article->state_id]);
		} else {
			$this->errors = $this->user->errors->get_raw_errors();
			$this->render('users/new');
		}
	}

	function delete() {
		if ($_GET['id'] == $this->currentUser->id) {
			setFlash('You cannot disable yourself.');
			$this->index();
			return;
		}

		$user = User::find($_GET['id']);
		$user->disable();
		$this->updateMediaWikiUser($user);

		$this->redirectTo("users");
	}

	// private functions

	private function updateAssoc() {
		$currentRoleIds = __::pluck($this->user->user_roles, 'role_id');
		$new            = array_diff( $this->params('roles', []), $currentRoleIds);
		$deleted        = array_diff($currentRoleIds, $this->params('roles', []));

		foreach ($new as $roleId) {
			UserRole::create(['role_id' => $roleId, 'user_id' => $this->user->id]);
		}

		foreach ($deleted as $roleId) {
			UserRole::find_by_user_id_and_role_id($this->user->id, $roleId)->delete();
		}

		$this->user->reload();
		UserArticle::unassignIfRoleAbsent($this->user);
	}

	/**
	 * Add Content Portal editors to the "editor_team" user group,
	 * or remove them from it if they lost the "editor" CP role.
	 */
	private function updateMediaWikiUser(User $cpUser) {
		$whUser = \User::newFromId($cpUser->wh_user_id);
		$whGroup = 'editor_team';
		$hasWHGroup = $whUser->hasGroup($whGroup);
		$isStaff = $whUser->hasGroup('staff');
		$isCPEditor = !$cpUser->disabled && $cpUser->hasRoleKey(Role::EDIT_KEY);
		if ($isCPEditor && !$hasWHGroup && !$isStaff) {
			$whUser->addGroup($whGroup);
		} elseif (!$isCPEditor && $hasWHGroup) {
			$whUser->removeGroup($whGroup);
		}
	}
}
