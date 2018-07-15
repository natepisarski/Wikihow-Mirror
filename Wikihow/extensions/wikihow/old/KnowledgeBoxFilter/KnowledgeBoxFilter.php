<?
global $wgSpecialPages;
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'KnowledgeBoxFilter',
	'author' => 'David Morrow',
	'description' => 'Tool for working with knowledge box submissions',
];

$wgSpecialPages['KnowledgeBoxFilter'] = "KB\KbRouter";
$wgAutoloadClasses['KB\KbRouter'] = __DIR__ . '/KbRouter.php';
