<?php

if ( !defined( 'MEDIAWIKI' ) ) exit(1);

/**#@+
 * A simple extension that allows users to enter a title before creating a
 * page.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CreatePage',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way entering a title and searching for potential duplicate articles before creating a page',
	'url' => 'http://www.wikihow.com/WikiHow:CreatePage-Extension',
);

$wgExtensionMessagesFiles['CreatePage'] = __DIR__ . '/CreatePage.i18n.php';
$wgExtensionMessagesFiles['CreatePageAliases'] = __DIR__ . '/CreatePage.alias.php';

$wgSpecialPages['CreatePage'] = 'CreatePage';
$wgSpecialPages['CreatePageTitleResults'] = 'CreatePageTitleResults';
$wgSpecialPages['CreatepageWarn'] = 'CreatepageWarn';
$wgSpecialPages['ProposedRedirects'] = 'ProposedRedirects';
$wgSpecialPages['CreatepageEmailFriend'] = 'CreatepageEmailFriend';
$wgSpecialPages['CreatepageFinished'] = 'CreatepageFinished';
$wgSpecialPages['CreatepageReview'] = 'CreatepageReview';
$wgSpecialPages['SuggestionSearch'] = 'SuggestionSearch';
$wgSpecialPages['ManageSuggestions'] = 'ManageSuggestions';

$wgAutoloadClasses['CreatePage'] = __DIR__ . '/CreatePage.body.php';

$wgAutoloadClasses['CreatePageTitleResults'] = __DIR__ . '/CreatePageEndpoints.php';
$wgAutoloadClasses['CreatepageWarn'] = __DIR__ . '/CreatePageEndpoints.php';
$wgAutoloadClasses['CreatepageEmailFriend'] = __DIR__ . '/CreatePageEndpoints.php';
$wgAutoloadClasses['CreatepageFinished'] = __DIR__ . '/CreatePageEndpoints.php';
$wgAutoloadClasses['CreatepageReview'] = __DIR__ . '/CreatePageEndpoints.php';

$wgAutoloadClasses['ProposedRedirects'] = __DIR__ . '/ProposedRedirects.php';
$wgAutoloadClasses['SuggestionSearch'] = __DIR__ . '/SuggestionSearch.php';
$wgAutoloadClasses['ManageSuggestions'] = __DIR__ . '/ManageSuggestions.php';

$wgHooks['ArticleDelete'][] = array("wfCheckSuggestionOnDelete");
$wgHooks['ArticleSaveComplete'][] = array("wfCheckSuggestionOnSave");
$wgHooks['ArticleSave'][] = array("wfCheckForCashSpammer");
$wgHooks['TitleMoveComplete'][] = array("wfCheckSuggestionOnMove");
$wgHooks['ArticleJustBeforeBodyClose'][] = array("wfShowFollowUpOnCreation");
$wgHooks['ArticleInsertComplete'][] = array("wfProcessNewArticle");
$wgHooks['ArticleDeleteComplete'][] = array("wfRemoveFromFirstEdit");
$wgHooks['ArticleUndelete'][] = array('wfRestoreFirstEdit');
$wgHooks['ShowGrayContainer'][] = array('wfRemoveGrayContainerCallback');

$wgLogTypes[] = 'suggestion';
$wgLogNames['suggestion'] = 'suggestionlogpage';
$wgLogHeaders['suggestion'] = 'suggestionlogtext';

$wgLogTypes[] = 'redirects';
$wgLogNames['redirects'] = 'redirects';
$wgLogHeaders['redirects'] = 'redirectstext';
$wgLogActions['redirects/added'] = 'redirects_logsummary';

$wgResourceModules['ext.wikihow.createpage'] = [
    'styles' => ['createpage.css'],
    'scripts' => ['createpage.js'],
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/createpage',
    'position' => 'top',
    'targets' => ['desktop'],
    'dependencies' => ['ext.wikihow.common_top'],
];

function wfGetSuggTitlesMemcKey($articleID) {
	return wfMemcKey("suggtitles:" . $articleID);
}

function wfClearSuggestionsCache($t) {
	global $wgMemc;

	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select(['suggested_links', 'suggested_titles'],
		['sl_page'],
		['st_title' => $t->getDBKey(), 'sl_sugg = st_id'] );
	foreach ($res as $row) {
		$key = wfGetSuggTitlesMemcKey($row->sl_page);
		$wgMemc->delete($key);
	}
	return true;
}

function wfCheckSuggestionOnDelete($wikiPage) {
	try {
		$t = $wikiPage->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN) {
			return true;
		}
		$dbw = wfGetDB(DB_MASTER);
		$key = TitleSearch::generateSearchKey(trim($t));
		$dbw->update('suggested_titles',
					['st_used' => 0],
					['st_key' => $key],
					__METHOD__);
		wfClearSuggestionsCache($t);
	} catch (Exception $e) {
		// ignore
	}
	return true;
}

function wfCheckSuggestionOnMove( &$ot, &$nt, &$wgUser, $pageid, $redirid) {
	$dbw = wfGetDB(DB_MASTER);
	$dbw->update('suggested_titles',
		['st_used' => 1, 'st_created' => wfTimestampNow(TS_MW)],
		['st_title' => $nt->getDBKey()],
		__METHOD__);
	wfClearSuggestionsCache($nt);
	return true;
}

// When a new article is created, mark the suggsted as used in the DB
function wfCheckSuggestionOnSave($article, $user, $text, $summary, $p5, $p6, $p7) {
	try {
		$dbr = wfGetDB(DB_SLAVE);
		$t = $article->getTitle();
		if (!$t || $t->getNamespace() != NS_MAIN) {
			return true;
		}
		$num_revisions = $dbr->selectField('revision',
			'count(*)',
			['rev_page=' . $article->getId()],
			__METHOD__);
		// < 2 for race conditions
		if ($num_revisions < 2) {
			$dbw = wfGetDB(DB_MASTER);
			$key = TitleSearch::generateSearchKey(trim($t));
			$dbw->update('suggested_titles',
					['st_used' => 1, 'st_created' => wfTimestampNow(TS_MW)],
					['st_key' => $key],
					__METHOD__);
			wfClearSuggestionsCache($t);
		}
		if ($num_revisions == 1) {
			$email = $dbw->selectField('suggested_titles',
				['st_notify'],
				['st_title' => $t->getDBKey()],
				__METHOD__);
			if ($email) {
				$dbw->insert('suggested_notify',
					   ['sn_page' => $article->getId(),
						'sn_notify' => $email,
						'sn_timestamp' => wfTimestampNow(TS_MW)],
					__METHOD__);
			}
		}
	} catch (Exception $e) {
		// ignore
	}
	return true;
}

// update the first edit table and set the cookie that will show the
// follow up dialog for the user
function wfProcessNewArticle(&$article, &$user, $text) {
	global $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgCookiePrefix, $wgLanguageCode;

	$title = $article->getTitle();
	if (!$title || $title->getNamespace() != NS_MAIN) {
		return true;
	}

	$id = $title->getArticleID();
	if (!$id) {
		return true;
	}
	$cookieName = $wgCookiePrefix . 'ArticleCreated' . $id;
	$expiry = time() + 60 * 60; // 1 hour
	if ( !headers_sent() ) {
		setcookie( $cookieName, '1', $expiry, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
	}

	if (preg_match("@#REDIRECT@", $text)) {
		return true;
	}

	// update the first edit table
	$dbw = wfGetDB(DB_MASTER);
	$dbw->insert('firstedit',
		   ['fe_page' => $id,
			'fe_user' => $user->getID(),
			'fe_user_text' => $user->getName(),
			'fe_timestamp' => wfTimestampNow()],
		__METHOD__);

	// write talk page message for first created article
	if ($wgLanguageCode == "en") {
		$num_articles = $dbw->selectField('firstedit',
			'count(*)',
			['fe_user' => $user->getID()],
			__METHOD__);
		if ($num_articles == 1) {
			CreatePage::sendTalkPageMsg($user, $title);
		}
	}

	return true;
}

function wfRestoreFirstEdit( $title, $create, $comment ) {
	// would be more efficient to look up page using page id, but the
	// parameter is not available until we have
	// https://gerrit.wikimedia.org/r/#/c/133631/ (1.24)
	$dbw = wfGetDB(DB_MASTER);
	$page = WikiPage::factory( $title );

	if ( !$page || !$page->exists() ) {
		return true;
	}

	$revision = $page->getOldestRevision();
	if ( !$revision ) {
		return true;
	}

	$userId = $revision->getUser();
	$userText = $revision->getUserText();
	$timestamp = $revision->getTimestamp();

	$dbw->insert( 'firstedit',
		[
			'fe_page' => $page->getId(),
			'fe_user' => $userId,
			'fe_user_text' => $userText,
			'fe_timestamp' => $timestamp
		],
		__METHOD__,
		array( 'IGNORE' ) );

	return true;
}

function wfRemoveFromFirstEdit($wikiPage, $user, $reason, $id) {
	$dbw = wfGetDB(DB_MASTER);
	$dbw->delete('firstedit', ['fe_page' => $id], __METHOD__);
	return true;
}

function wfHasCurrentArticleCreationCookie() {
	global $wgCookiePrefix, $wgTitle;
	$articleID = $wgTitle ? $wgTitle->getArticleId() : 0;
	if ($articleID) {
		$cookieName = $wgCookiePrefix . 'ArticleCreated' . $articleID;
		// short circuit the database look ups because they are frigging slow
		if ( isset( $_COOKIE[$cookieName] ) && $_COOKIE[$cookieName] ) {
			return true;
		}
	}
	return false;
}

function wfShowFollowUpOnCreation() {
	global $wgTitle, $wgUser, $wgCookiePath, $wgCookieDomain, $wgCookieSecure, $wgLanguageCode;

	// Don't show extra sharing dialog after creating an article on international
	if ($wgLanguageCode != "en") {
		return true;
	}

	try {
		$t = $wgTitle;
		if (!$t || $t->getNamespace() != NS_MAIN) {
			return true;
		}
		$articleID = $t->getArticleID();
		if (!$articleID) {
			return true;
		}

		if ( !wfHasCurrentArticleCreationCookie() ) {
			// they didn't create that article
			return true;
		}

		$article = new Article($t);

		$dbr = wfGetDB(DB_SLAVE);
		$num_revisions = $dbr->selectField('revision',
			'count(*)',
			['rev_page' => $article->getId()],
			__METHOD__);
		if ($num_revisions > 1) return true;

		$user_name = $dbr->selectField('revision',
			'rev_user_text',
			['rev_page' => $article->getId()],
			__METHOD__);
		if ($wgUser->getName() != $user_name) {
			return true;
		}

		// all of this logic could be cleaned up and HTML moved to a template
		$referer = $_SERVER['HTTP_REFERER'];
		if ( ( strpos($referer, 'action=edit') !== false
				 || strpos($referer, '/Special:ArticleCreator') !== false
				 || strpos($referer, 'action=submit2') !== false )
			&& !isset($_SESSION["aen_dialog"][$article->getId()])
			&& $wgUser->getOption( 'enableauthoremail' ) != '1'
		) {
			if ($wgUser->getID() == 0) {
				setcookie('aen_anon_newarticleid', $article->getId(), time()+3600, $wgCookiePath, $wgCookieDomain, $wgCookieSecure);
			}

			// pop it!
			ArticleCreator::printArticleCreatedScript($t);

			$_SESSION["aen_dialog"][$article->getId()] = 1;
		}
	} catch (Exception $e) {
		// ignore
	}
	return true;
}

function wfCheckForCashSpammer($article, $user, $text, $summary, $flags, $p1, $p2, $flags2) {
	if ($text) {
		if ($article->getTitle()->getText() == "Yrt291x"
			|| $article->getTitle()->getText() == "Spam Blacklist"
		) {
			return true;
		}
		$msg = preg_replace('@<\![-]+-[\n]+|[-]+>@U', '', wfMessage('yrt291x')->text());
		$msgs = explode("\n", $msg);
		foreach ($msgs as $m) {
			$m = trim($m);
			if ($m == "") continue;
			if (stripos($text, $m) !== false) {
				return false;
			}
		}
	}
	return true;
}

function wfRemoveGrayContainerCallback(&$showGrayContainer) {
	global $wgTitle;
	if ($wgTitle && $wgTitle->getText() == 'CreatePage') {
		$showGrayContainer = false;
	}
	return true;
}
