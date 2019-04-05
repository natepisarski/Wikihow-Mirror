<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Spellchecker',
	'author' => 'Jordan Small',
	'description' => 'Tool to help users find and correct spelling mistakes',
);

$wgSpecialPages['Spellchecker'] = 'Spellchecker';
$wgSpecialPages['Spellcheckerwhitelist'] = 'Spellcheckerwhitelist';
$wgSpecialPages['SpellcheckerArticleWhitelist'] = 'SpellcheckerArticleWhitelist';
$wgAutoloadClasses['Spellchecker'] = __DIR__ . '/Spellchecker.body.php';
$wgAutoloadClasses['MobileSpellchecker'] = __DIR__ . '/MobileSpellchecker.body.php';
$wgAutoloadClasses['wikiHowDictionary'] = __DIR__ . '/Spellchecker.body.php';
$wgAutoloadClasses['Spellcheckerwhitelist'] = __DIR__ . '/Spellchecker.body.php';
$wgAutoloadClasses['SpellcheckerArticleWhitelist'] = __DIR__ . '/Spellchecker.body.php';
$wgExtensionMessagesFiles['Spellchecker'] = __DIR__ . '/Spellchecker.i18n.php';

$wgLogTypes[] = 'spellcheck';
$wgLogTypes[] = 'whitelist';
$wgLogNames['spellcheck'] = 'spellchecker';
$wgLogNames['whitelist'] = 'spellchecker whitelist';
$wgLogHeaders['spellcheck'] = 'spellcheck_log';
$wgLogHeaders['whitelist'] = 'whitelist_log';

$wgHooks["PageContentSaveComplete"][] = "wfCheckspelling";
$wgHooks["ArticleDelete"][] = "wfRemoveCheckspelling";
$wgHooks["ArticleUndelete"][] = "wfUndeleteCheckpelling";
$wgHooks["IsEligibleForMobileSpecial"][] = array("MobileSpellchecker::onIsEligibleForMobileSpecial");
$wgHooks['NABMarkPatrolled'][] = 'Spellchecker::onMarkNabbed';
$wgHooks['NABArticleDemoted'][] = 'Spellchecker::onArticleDemoted';

function wfCheckspelling(&$wikiPage, &$user, $content, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision) {
	if ($wikiPage->mTitle->inNamespace(NS_MAIN))
		Spellchecker::markAsDirty($wikiPage->getID());

	return true;
}

function wfRemoveCheckspelling($wikiPage, $user, $reason) {
	if ($wikiPage->getTitle()->inNamespace(NS_MAIN))
		Spellchecker::markAsIneligible($wikiPage->getId());

	return true;
}

function wfUndeleteCheckpelling( $title, $create) {
	if (!$create && $title->inNamespace(NS_MAIN))
		Spellchecker::markAsDirty($title->getArticleID());

	return true;
}

$wgResourceModules['ext.wikihow.spellchecker'] = array(
	'scripts' => array('../ext-utils/anon_throttle.js', 'spellchecker.js'),
	'styles' => array('spellchecker.css'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/spelltool',
	'messages' => array('spch-loading-next','spch-qe-summary', 'spch-error-noarticles', 'spch-error-noarticles-mobile', 'spch-msg-anon-limit1', 'spch-login', 'spch-signup'),
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' ),
);


$wgResourceModules['ext.wikihow.mobile.spellchecker'] = $wgResourceModules['ext.wikihow.spellchecker'];
$wgResourceModules['ext.wikihow.mobile.spellchecker']['styles'][] = 'mobilespellchecker.css';
$wgResourceModules['ext.wikihow.mobile.spellchecker']['dependencies'] = array('mobile.wikihow', 'ext.wikihow.MobileToolCommon');
$wgResourceModules['ext.wikihow.spellchecker']['dependencies'] = array('mediawiki.page.ready');
