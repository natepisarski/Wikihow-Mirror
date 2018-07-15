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
 * @author Vu Nguyen <vu@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AuthorEmailNotification',
	'author' => 'Vu Nguyen',
	'description' => 'Notifies by email on certain events',
);

$wgExtensionMessagesFiles['AuthorEmailNotification'] = dirname(__FILE__) . '/AuthorEmailNotification.i18n.php';
$wgSpecialPages['AuthorEmailNotification'] = 'AuthorEmailNotification';
$wgAutoloadClasses['AuthorEmailNotification'] = dirname( __FILE__ ) . '/AuthorEmailNotification.body.php';
$wgAutoloadClasses['EmailActionButtonScript'] = dirname( __FILE__ ) . '/EmailActionButtonScript.class.php';  //Class that returns script for including gmail action buttons

$wgHooks['AddNewAccount'][] = array("attributeAnon");
$wgHooks['AddNewAccount'][] = array("setUserTalkOption");
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
			$u = User::newFromId($r->getUser());
			AuthorEmailNotification::notifyMod($article, $u, $r);
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

/*
// Not used since at least 2013 - Alberto
function addFirstEdit($article, $details) {
	global $wgTitle, $wgRequest, $wgOut, $wgUser;

	try {
		$t = $article->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN)
			return true;
		$dbr = wfGetDB(DB_MASTER);
		$num_revisions = $dbr->selectField('revision', 'count(*)', array('rev_page=' . $article->getId()));
		if ($num_revisions > 1) return true;
		$user_name  = $dbr->selectField('revision', 'rev_user_text', array('rev_page=' . $article->getId()));
		if (
			(strpos($_SERVER['HTTP_REFERER'], "action=edit") !== false || strpos($_SERVER['HTTP_REFERER'], "action=submit2"))
			&& $wgUser->getName() == $user_name) {

		  $dbw = &wfGetDB(DB_MASTER);
			$sql = "insert ignore into firstedit select rev_page, rev_user, rev_user_text, min(rev_timestamp) from page, revision where page_id=rev_page and page_namespace=0 and page_is_redirect=0 and page_id=". $article->getId() ." group by rev_page";
			$ret = $dbw->query($sql);

		}
	} catch (Exception $e) {
	}
	return true;
}
*/

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
