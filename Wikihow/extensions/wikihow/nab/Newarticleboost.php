<?php
if ( ! defined('MEDIAWIKI') ) die();
/**#@+
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:NewArticleBoost-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'NewArticleBoost',
	'author' => 'Travis Derouin',
	'description' => 'Provides a separate way of boosting new articles',
	'url' => 'http://www.wikihow.com/WikiHow:NewArticleBoost-Extension',
);

$wgResourceModules['ext.wikihow.nab'] = array(
    'scripts' => 'newarticleboost.js',
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/nab',
	'messages' => array(
		'nap_autosummary',
		'all-changes-lost',
	),
    'position' => 'bottom',
    'targets' => array( 'desktop', 'mobile' )
);

$wgResourceModules['ext.wikihow.nab.styles'] = array(
    'styles' => 'newarticleboost.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/nab',
    'targets' => array('desktop', 'mobile'),
);

$wgExtensionMessagesFiles['NewArticleBoost'] = __DIR__ . '/Newarticleboost.i18n.php';
$wgSpecialPages['NewArticleBoost'] = 'NewArticleBoost';
$wgSpecialPages['NABStatus'] = 'NABStatus';
$wgSpecialPages['CopyrightChecker'] = 'CopyrightChecker';
$wgSpecialPages['MarkRelated'] = 'MarkRelated';
$wgSpecialPages['NABClean'] = 'NABClean';
$wgSpecialPages['AdminMarkPromoted'] = 'AdminMarkPromoted';
$wgSpecialPages['NABPatrol'] = 'NABPatrol';
$wgSpecialPages['AdminNAD'] = 'AdminNAD';
$wgAutoloadClasses['NewArticleBoost'] = __DIR__ . '/Newarticleboost.body.php';
$wgAutoloadClasses['NABStatus'] = __DIR__ . '/Newarticleboost.body.php';
$wgAutoloadClasses['CopyrightChecker'] = __DIR__ . '/Newarticleboost.body.php';
$wgAutoloadClasses['MarkRelated'] = __DIR__ . '/Newarticleboost.body.php';
$wgAutoloadClasses['NABClean'] = __DIR__ . '/Newarticleboost.body.php';
$wgAutoloadClasses['NABPatrol'] = __DIR__ . '/Newarticleboost.body.php';
$wgAutoloadClasses['AdminMarkPromoted'] = __DIR__ . '/AdminMarkNAB.body.php';
$wgAutoloadClasses['NabQueryPage'] = __DIR__ . '/Newarticleboost.body.php';
$wgAutoloadClasses['AdminNAD'] = __DIR__ . '/AdminNAD.body.php';

$wgExtensionMessagesFiles['NewArticleBoostAlias'] = __DIR__ . '/Newarticleboost.alias.php';

$wgHooks['ArticleDelete'][] = array("wfNewArticlePatrolClearOnDelete");
$wgHooks['PageContentSaveComplete'][] = array("wfNewArticlePatrolAddOnCreation");

$wgAvailableRights[] = 'newarticlepatrol';
$wgGroupPermissions['newarticlepatrol']['newarticlepatrol'] = true;
$wgGroupPermissions['newarticlepatrol']['move'] = true;
$wgGroupPermissions['newarticlepatrol']['suppressredirect'] = true;
$wgGroupPermissions['staff']['newbienap'] = true;

$wgLogTypes[] = 'nap';
$wgLogNames['nap'] = 'newarticlepatrollogpage';
$wgLogHeaders['nap'] = 'newarticlepatrollogpagetext';

// Take the article out of the queue if it's been deleted
function wfNewArticlePatrolClearOnDelete($article, $user, $reason) {
	$dbw = wfGetDB(DB_MASTER);
	$dbw->delete(NewArticleBoost::NAB_TABLE, array('nap_page' => $article->getId()), __METHOD__);
	return true;
}

function wfNewArticlePatrolAddOnCreation($wikiPage, $napUser, $content, $summary, $p5, $p6, $p7) {
	$db = wfGetDB(DB_MASTER);
	$t = $wikiPage->getTitle();
	if (!$t || !$t->inNamespace(NS_MAIN))  {
		return true;
	}

	if (in_array("bot", RequestContext::getMain()->getUser()->getGroups())) {
		// ignore bots
		return true;
	}

	$row = $db->selectRow('revision',
		array('count(*) as count',
			'min(rev_id) as min_rev',
			'min(rev_timestamp) as min_ts'),
		array('rev_page' => $wikiPage->getId()),
		__METHOD__);
	$num_revisions = $row->count;
	$min_rev = $row->min_rev;
	$min_ts = $row->min_ts;

	$row = $db->selectRow('revision',
		array('rev_timestamp', 'rev_user'),
		array('rev_id' => $min_rev),
		__METHOD__);
	$ts = $row->rev_timestamp;
	$userid = $row->rev_user;

	$nab_count = $db->selectField(NewArticleBoost::NAB_TABLE,
		'count(*)',
		array('nap_page' => $wikiPage->getId()),
		__METHOD__);

	$langCode = RequestContext::getMain()->getLanguage()->getCode();

	// filter articles created by bots and non-English translators
	if ($userid > 0) {
		$revUser = User::newFromID($userid);
		if ($revUser) {
			if ($langCode == 'en') {
				$specialGroups = array('bot');
			} else {
				// Edits by users in these groups won't go into NAB on intl
				$specialGroups = array('bot', 'translator', 'staff', 'sysop');
			}

			$userGroups = $revUser->getGroups();
			foreach ($specialGroups as $group) {
				if ( in_array( $group, $userGroups ) ) {
					return true;
				}
			}
		}
	}

	$oldRevID = 0;
	$rev = $wikiPage->getRevision();
	if ($rev) {
		$oldRevID = $rev->getId();
	}
	if (($min_rev == $oldRevID
		 || !$num_revisions
		 || $num_revisions < 5)
		&& $nab_count == 0        // ignore articles already in there.
		&& $ts > '20090101000000' // forget articles before 2009-01-01
	) {
		// default to not a newbie
		$nab_newbie = 0;

		// check for newbie feature and processing settings
		$newbie = array('anon' => 1, 'articles' => 5, 'edits' => 10);
		$msg = wfMessage('NSS')->text();
		$lines = preg_split('@(\n|\s)+@', $msg);
		foreach ($lines as $line) {
			list($k, $v) = explode('=', $line);
			$k = trim($k);
			$v = trim($v);
			if ($k === '' || $v === '') continue;
			$newbie[$k] = intval($v);
		}

		// only do checks if we the anon flag is set, or the user
		// is logged in
		if ($newbie['anon'] || $napUser->getID()) {
			// how many edits?
			if ($newbie['edits'] > 0) {
				$count = $db->selectField(
					array('revision', 'page'),
					'count(*)',
					array('rev_page=page_id',
						'page_namespace' => NS_MAIN,
						'rev_user_text' => $napUser->getName()),
					__METHOD__);
				if ($count < $newbie['edits']) {
					$nab_newbie = 1;
				}
			}
			if ($nab_newbie == 0 && $newbie['articles'] > 0) {
				// how many articles created?
				$count = $db->selectField(
					'firstedit',
					'count(*)',
					array('fe_user_text' => $napUser->getName()),
					__METHOD__);
				if ($count < $newbie['articles']) {
					$nab_newbie = 1;
				}
			}
		}

		$db->insert(NewArticleBoost::NAB_TABLE,
			array(
				'nap_page' => $wikiPage->getId(),
				'nap_timestamp' => $min_ts,
				'nap_newbie' => $nab_newbie),
			__METHOD__);

		if ($langCode == 'en') {
			$db->insert('nab_atlas', array('na_page_id' => $wikiPage->getId()), __METHOD__, array('IGNORE'));
		}
	}

	return true;
}

/*****

time pt-online-schema-change --execute --alter "ADD COLUMN nap_demote tinyint(3) UNSIGNED NOT NULL DEFAULT 0" D=wikidb_112,t=newarticlepatrol
time pt-online-schema-change --execute --alter "ADD COLUMN nap_atlas_score tinyint(4) NOT NULL DEFAULT -1" D=wikidb_112,t=newarticlepatrol

UPDATE `newarticlepatrol` INNER JOIN `nab_atlas` ON newarticlepatrol.nap_page = nab_atlas.na_page_id set newarticlepatrol.nap_atlas_score = nab_atlas.na_atlas_score
*****/
