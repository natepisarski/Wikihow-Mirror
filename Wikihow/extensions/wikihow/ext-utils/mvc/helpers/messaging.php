<?php

function hasFlash() {
	return isset($_SESSION['flash']);
}

function setFlash($msg, $class="danger") {
	$_SESSION['flash'] = ["message" => $msg, "class" => $class];
}

function getFlash() {
	$msg = $_SESSION['flash'];
	unset($_SESSION['flash']);
	return $msg;
}
