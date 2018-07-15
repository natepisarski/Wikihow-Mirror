<?php
if ( ! defined('MEDIAWIKI') ) die();
/*
 * An extension to do ajax quick edit on articles
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 *
 * @author Gershon Bialer <gershon@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'QuickEdit',
	'author' => 'Gershon Bialer',
	'description' => 'Provides ajax for quick editing of articles'
);

$wgSpecialPages['QuickEdit'] = 'QuickEdit';
$wgAutoloadClasses['QuickEdit']      = dirname( __FILE__ ) . '/QuickEdit.body.php';

$wgHooks["UserToolLinksEdit"][] = array("wfAddQuickNoteLink");

//add quick note link
function wfAddQuickNoteLink($userId, $userText, &$items) {
	global $wgTitle, $wgLanguageCode, $wgRequest;
	if ($wgTitle->getNamespace() != NS_SPECIAL &&
		$wgLanguageCode =='en' &&
		$wgRequest->getVal("diff", ""))
	{
		$items[] = QuickNoteEdit::getQuickNoteLink($wgTitle, $userId, $userText);
	}
	return true;
}
