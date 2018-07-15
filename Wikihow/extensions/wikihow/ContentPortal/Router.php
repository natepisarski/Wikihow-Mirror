<?php
namespace ContentPortal;

$composerAutoload = __DIR__ . "/vendor/autoload.php";
if (file_exists($composerAutoload)) {
	require $composerAutoload;
} else {
	die("You need to run composer in " . __DIR__ . " for Content Portal to work. $composerAutoload doesn't exist yet.");
}

use MVC\Router as MVCRouter;

class Router extends MVCRouter {
	public $groupWhiteList = ["*"];
	public $rootPath = "articles/dashboard";

	public function userAllowed() {
		return true;
	}
}

