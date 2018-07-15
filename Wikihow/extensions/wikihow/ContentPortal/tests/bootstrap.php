<?
require __DIR__ . "./../../ext-utils/mvc/tests/bootstrap.php";
require __DIR__ . '/Helpers.php';
require 'Stubs.php';
require 'MonkeyPatch.php';
require SERVER_ROOT . "/extensions/wikihow/ContentPortal/vendor/autoload.php";

global $wgUser;
$wgUser = new User;
$_COOKIE['wiki_shared_session'] = 666;
if (!isset($_GET)) $_GET = [];

ContentPortal\Event::$silent = true;
ContentPortal\Config::getInstance();
ContentPortal\Helpers::setCurrentUser(ContentPortal\Helpers::getAdmin());
ContentPortal\Router::getInstance()->execute('');

