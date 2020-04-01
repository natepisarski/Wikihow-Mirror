<?php

function labelForGroup($group) {
  $labels = [
    'wrm'         => 'Articles that are WRM',
    'with_errors' => 'Articles with errors that cannot be imported',
    'existing'    => 'Articles that already exist on WikiHow',
    'existing'    => 'Articles that already exist on WikiHow',
    'wrm_with_id' => 'Articles that are marked WRM but already exist on wikiHow'
  ];
  return $labels[$group];
}

function catFromCache($id) {
  return __::find(ContentPortal\Category::allFromCache(), ['id' => $id])->title;
}

function assignedUsername($userId) {
  if (is_null($userId)) return 'Unassigned';
  $user = ContentPortal\User::find($userId);
  return $user ? $user->username : 'Unassigned';
}

function disabled($article) {
  return $article->is_valid() ? '' : 'disabled="true"';
}

function errorsFor($article, $field) {
  if ($article->errors->is_invalid($field)) {
    return "<span class='help-text bg-danger'>{$article->errors->on($field)}</span>";
  }

  return "";
}

function json($article) {
	//table data
	$data = $article->attributes();

	//extra data
	$data['import_notes'] = $article->import_notes;

  return json_encode($data, JSON_PRETTY_PRINT);
}
