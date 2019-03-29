<?php
function articleCountForRole($role) {
	return ContentPortal\Article::count(['state_id' => $role->id, 'assigned_id' => currentUser()->id]);
}
