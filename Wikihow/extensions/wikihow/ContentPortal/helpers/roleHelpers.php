<?

function allButAdminRoles() {
	return ContentPortal\Role::allButAdmin();
}

function publicRoles() {
	return ContentPortal\Role::all(['conditions' => ['public' => true], 'order' => 'step']);
}

function getArticleRoles($state_id) {
	return ContentPortal\Role::getArticleRoles($state_id);
}
