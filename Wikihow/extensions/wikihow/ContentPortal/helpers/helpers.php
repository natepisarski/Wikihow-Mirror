<?php

function containerClass() {
	$full = array_key_exists('fullScreen', controller()->viewVars) && controller()->viewVars['fullScreen'] == true;
	return $full ? 'container-fluid' : 'container';
}

function gridWidth($num) {
	return ceil(12 / $num);
}

function stateDescrip($state) {
	return is_null($state) ? 'Unassigned' : $state->present_tense;
}

function alert($msg, $type="danger", $row=false) {
	$str = "<div class='alert alert-$type'>" .
		'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' .
		"<p>$msg</p></div>\n";
	return $row ? "<div class='col-md-12'>$str</div>\n" : $str;
}

function isCurrentUser(ContentPortal\User $user) {
	return currentUser()->id == $user->id;
}

function currentUser() {
	return isset(controller()->viewVars['currentUser']) ? controller()->currentUser : ContentPortal\Auth::findCurrentUser();
}

function controller() {
	return MVC\Controller::getInstance();
}

function avatar(ContentPortal\User $user) {
	global $wgIsDevServer;
	$prefix = $wgIsDevServer ? '' : 'http://www.wikihow.com';
	return $prefix . Avatar::getAvatarURL($user->username);
}
