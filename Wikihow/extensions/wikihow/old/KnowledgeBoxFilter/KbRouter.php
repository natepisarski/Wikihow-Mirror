<?
namespace KB;
global $IP;
require_once "$IP/extensions/wikihow/ext-utils/mvc/Router.php";
use MVC\Router;
require_once __DIR__ . "/KbModel.php";

class KbRouter extends Router {
	public $groupWhiteList = ["staff", 'sysop', 'editfish', 'editorpersonal'];
	public $toolName = "KnowledgeBoxFilter";
	public $rootPath = "kb/index";
}
