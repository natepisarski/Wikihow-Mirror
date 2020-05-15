<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 * An extension notifies users on certain events
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 * @author Vu Nguyen (wikiHow)
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AuthorEmailNotification',
	'author' => 'Vu Nguyen (wikiHow)',
	'description' => 'Notifies by email on certain events',
);

$wgExtensionMessagesFiles['AuthorEmailNotification'] = __DIR__ . '/AuthorEmailNotification.i18n.php';
$wgSpecialPages['AuthorEmailNotification'] = 'AuthorEmailNotification';
$wgAutoloadClasses['AuthorEmailNotification'] = __DIR__ . '/AuthorEmailNotification.body.php';
$wgAutoloadClasses['EmailActionButtonScript'] = __DIR__ . '/EmailActionButtonScript.class.php';  //Class that returns script for including gmail action buttons

$wgHooks['LocalUserCreated'][] = array("attributeAnon");
$wgHooks['LocalUserCreated'][] = array("setUserTalkOption");
#$wgHooks['ArticlePageDataBefore'][] = array("addFirstEdit");
$wgHooks['MarkPatrolledDB'][] = array("sendModNotification");
$wgHooks['ConfirmEmailComplete'][] = array('setUserWatchToWatchAll');


function sendModNotification(&$rcid, &$article) {
	$articleTitle = null;
	if ($article) {
		$articleTitle = $article->getTitle();
	}

	try {
		if ($articleTitle && $articleTitle->getArticleID() != 0)  {
			$dbw = wfGetDB(DB_MASTER);
			$r = Revision::loadFromPageId($dbw, $articleTitle->getArticleID());
			if ($r) {
				$u = User::newFromId($r->getUser());
				AuthorEmailNotification::notifyMod($article, $u, $r);
			}
		}
	} catch (Exception $e) {
	}
	return true;
}

function attributeAnon( $user ) {
	try {
		if (isset($_COOKIE["aen_anon_newarticleid"])) {
			$aid = $_COOKIE['aen_anon_newarticleid'];
			AuthorEmailNotification::reassignArticleAnon($aid);
			$user->incEditCount();
			if ($user->getEmail() != '') {
				AuthorEmailNotification::addUserWatch($aid, 1);
			}

			//now send them a talk page message for their first article
			$title = Title::newFromID($aid);
			if ($title) CreatePage::sendTalkPageMsg($user, $title);
		}
	} catch (Exception $e) {
	}
	return true;
}

function setUserTalkOption( $user ) {
	try {
		$user->setOption('usertalknotifications', 0);
		$user->saveSettings();
	} catch (Exception $e) {

	}
	return true;
}

//for when a user adds (and confirms) an email to their account
//flip all their started article notifications on
function setUserWatchToWatchAll($user) {
	if ($user->getID()) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select(array('firstedit','page'),array ('page_id'),
				array ('fe_page=page_id', 'fe_user' => $user->getID()),__METHOD__);

		foreach ($res as $row) {
			AuthorEmailNotification::addUserWatch($row->page_id, 1);
		}
	}
	return true;
}
