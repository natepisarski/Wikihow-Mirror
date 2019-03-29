<?php
use MVC\Router;
use MVC\Config;
use MVC\Controller;

function url($path, $params=[]) {
	$mvcRouter = Router::getInstance();
	$url = "/Special:" . APP_NS . "/$path";

	if (!empty($params)) {
		$url .= "?" . http_build_query($params);
	}
	return $url;
}

function absUrl($path, $params=[]) {
	return "http://" . Config::getInstance()->domain . url($path, $params);
}

function currentUrl($add=[]) {
	return url("{$_GET['controller']}/{$_GET['action']}", array_merge(params(), $add));
}

function params($paramKey=null, $default=null) {
	return Controller::getInstance()->params($paramKey, $default);
}
