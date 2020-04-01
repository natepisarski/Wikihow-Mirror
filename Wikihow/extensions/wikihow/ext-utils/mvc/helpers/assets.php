<?php
use MVC\Controller;
use MVC\Router;

function scripts($package="assets", $bustCache=true) {
	global $IP, $mvcController;
	if (hasCompiledAsset($package, 'js')){
		return scriptTag(compiledAsset($package, 'js', true), false);
	} else {
		$data = json_decode(file_get_contents(APP_DIR . "/$package.json"), true);
		return scriptTag($data['js'], $bustCache);
	}
}

function imgPath($filename) {
	return APP_HTTP_PATH . "/assets/img/$filename";
}

function styles($package="assets", $bustCache=true) {
	global $IP;
	if (hasCompiledAsset($package, 'css')) {
		return stylesheetTag(compiledAsset($package, 'css', true), false);
	} else {
		$data = json_decode(file_get_contents(APP_DIR . "/$package.json"), true);
		return stylesheetTag($data['css'], $bustCache);
	}
}

function scriptTag($path, $bustCache=true) {
	$mvcController = Controller::getInstance();
	$path = is_array($path) ? $path : [$path];
	$prefix = APP_HTTP_PATH;
	$str = '';
	$nocache = $bustCache ? nocache() : '';
	foreach($path as $file) {
		$str .= "<script src='$prefix/$file$nocache'></script>\n";
	}
	return $str;
}

function hasCompiledAsset($package, $type) {
	return file_exists(APP_DIR . '/'. compiledAsset($package, $type, false));
}

function compiledAsset($package, $type, $siteRev=true) {
	$mvcRouter = Router::getInstance();
	if ($siteRev) {
		$version = file_get_contents(APP_DIR . '/assets/compiled/version.txt');
	}
	$suffix = $siteRev ? "?rev=" . $version : '';
	return "assets/compiled/" . APP_NS . "-$package.min.$type{$suffix}";
}

function nocache() {
	$date = new DateTime();
	return "?nocache=" . $date->getTimestamp();
}

function stylesheetTag($path, $bustCache=true) {
	$mvcController = Controller::getInstance();
	$path = is_array($path) ? $path : [$path];
	$str = '';
	foreach($path as $file) {
		$prefix = APP_HTTP_PATH;
		$nocache = $bustCache ? nocache() : '';
		$str .= "<link rel='stylesheet' type='text/css' href='$prefix/$file$nocache'>\n";
	}
	return $str;
}
