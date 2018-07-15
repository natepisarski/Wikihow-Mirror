<?
global $wgSpecialPages, $IP;
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'Content Portal',
	'author' => 'David Morrow',
	'description' => 'Tool for managing workflow of Chocolate Thor process',
];

$wgSpecialPages['ContentPortal'] = "ContentPortal\Router";
$wgAutoloadClasses['ContentPortal\Router'] = __DIR__ . '/Router.php';