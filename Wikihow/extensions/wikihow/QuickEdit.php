<?php
if ( ! defined('MEDIAWIKI') ) die();
/*
 * An extension to do ajax quick edit on articles
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author Gershon Bialer (wikiHow)
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// NOTE: should probably be converted to an api endpoint - Reuben/wikihow, May 2020
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'QuickEdit',
	'author' => 'Gershon Bialer (wikiHow)',
	'description' => 'Provides REST endpoint for quick editing of articles'
);

$wgSpecialPages['QuickEdit'] = 'QuickEdit';
$wgAutoloadClasses['QuickEdit'] = __DIR__ . '/QuickEdit.body.php';

$wgHooks["UserToolLinksEdit"][] = array("wfAddQuickNoteLink");

// add quick note link
function wfAddQuickNoteLink($userId, $userText, &$items) {
	global $wgTitle, $wgLanguageCode, $wgRequest;
	if (!$wgTitle->inNamespace(NS_SPECIAL) &&
		$wgLanguageCode =='en' &&
		$wgRequest->getVal("diff", ""))
	{
		$items[] = QuickNoteEdit::getQuickNoteLink($wgTitle, $userId, $userText);
	}
	return true;
}
