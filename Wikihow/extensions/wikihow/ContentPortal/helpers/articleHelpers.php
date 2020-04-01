<?php
use ContentPortal\Article;
use ContentPortal\Role;

function countByState($articles, Role $state) {
	return empty($articles) ? '' : blankIfZero(
		count(
			__::filter($articles, ['state_id' => $state->id])
		)
	);
}

function filterByRole($articles, $role) {
	return __::filter($articles, ['state_id' => $role->id]);
}

function approvedMessage($assoc) {
	$msg = $assoc->role->nextStep()->approved_msg;
	$search = ['*user*', '*title*', '*time*'];
	$approvedBy = $assoc->approvedBy() ? $assoc->approvedBy()->username : '';
	$replace = [$approvedBy, $assoc->article->title, timeAgo($assoc->updated_at)];

	return str_replace($search, $replace, $msg);
}

function titleToUrl(String $title) {
	return URL_PREFIX . Title::newFromText($title)->getPartialUrl();
}

function canComplete($article) {
	if ($article->state->key == ContentPortal\Role::VERIFY_KEY) {
		if (is_null($article->assigned_user)) {
			return true;
		} else {
			return $article->assigned_user->username == DANIEL;
		}
	}
	return false;
}

function redirectsUrl() {
	$params = [
		'sort' => 'is_redirect',
		'sort_dir' => 'DESC',
		'field' => 1
	];
	return url('articles/index', $params);
}

function deletesUrl() {
	$params = [
		'sort' => 'is_deleted',
		'sort_dir' => 'DESC',
		'field' => 1
	];
	return url('articles/index', $params);
}

function articlesUsername($article) {
	if ($article->isUnassigned()) {
		$username = 'Unassigned';
	}
	else {
		if ($article->assigned_user) {
			$username = $article->assigned_user->username;
		}
		else {
			$username = 'Error: Bad User';
		}
	}
	return $username;
}

function stateDisabled($article) {
	if ($article->state->key == Role::VERIFY_KEY) return '';
	return ($article->id && !$article->isfinished()) ? 'disabled="true"' : '';
}

function compatableUsers(Article $article, Array $users=null) {
	$users = $users ? $users : Role::find($article->state_id)->users;
	$users = __::chain($users)->select(['disabled' => 0])->filter(function ($user) use ($article) {
		return $user->hasRoleId($article->state_id);
	})->value();
	return $users;
}

function rolesForForm() {
	return __::filter(Role::allButAdmin(), function($role) {
		return !$role->is_on_hold && $role->public;
	});
}

function ifComplete($assoc, $class) {
	return $assoc->complete ? $class : '';
}

function canReject($article) {
	return $article->state->prevStep();
}

function shouldShowDoc($article) {
	return $article->state->needs_doc || $article->documents;
}

function exists() {
	return !is_null($this->wh_article_id);
}

function showEditLink($article) {
	return $article->exists() && $article->state->key == Role::EDIT_KEY;
}

function rejectLabel($article) {
	if ($article->state->key == ContentPortal\Role::VERIFY_KEY) {
		$label = "Implement Feedback";
	}
	else {
		$label = "Send back to {$article->state->revert_step->present_tense}";
	}
	return $label;
}

function canTurnDown($article) {
	return $article->state->can_decline;
}

function cardClasses($article) {
	return $article->rejected ? 'rejected' : '';
}

function docsCount($article) {
	$count = 0;
	$count += $article->hasWritingDoc() ? 1 : 0;
	$count += count($article->verifyDocs());
	return $count;
}

function doneWarning($article) {
	return "Press this button when you are completely done " . strtolower($article->state->present_tense) . " and are ready to submit your work to us. You will not be able to revisit the article after this button is pressed.";
}

function isArticleState(Role $role, $article) {
	return $role->id == $article->state_id;
}

//return if an article is in a general category (includes HP, MP, LP versions)
function isArticleInCat($article, $cat) {
	$cats = ContentPortal\Category::all(['conditions' => ['title LIKE (?)','%'.$cat]]);
	$catIds = __::pluck($cats, 'id');
	return in_array($article->category_id, $catIds);
}

//determines if we can show the done button
function canShowDone($article) {
	if (isArticleState(Role::verify(), $article) && !$article->hasHadVerifierFeedback()) return false;
	return true;
}
