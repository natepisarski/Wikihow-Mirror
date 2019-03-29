<?php
use ContentPortal\Role;

function userArticleCategories($articles, $toString=true) {
	if (empty($articles)) {
		return $toString ? currentUser()->category->title : [currentUser()->category->title];
	}
	$catIds = __::chain($articles)->pluck('category_id')->uniq()->value();
	$cats = ContentPortal\Category::all(['conditions' => ['id in (?)', $catIds]]);
	$titles = __::pluck($cats, 'title');
	return $toString ? implode(', ', $titles) : $cats;
}

function isImpersonated() {
	return isset($_SESSION['impersonate_user_id']);
}

function adminUsers() {
	return Role::admin()->users;
}

