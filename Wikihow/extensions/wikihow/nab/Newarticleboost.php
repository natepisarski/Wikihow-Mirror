<?php
if ( ! defined('MEDIAWIKI') ) die();
/**#@+
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Newarticleboost-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Newarticleboost',
	'author' => 'Travis Derouin',
	'description' => 'Provides a separate way of patrolling new articles',
	'url' => 'http://www.wikihow.com/WikiHow:Newarticleboost-Extension',
);

$wgResourceModules['ext.wikihow.nab'] = array(
    'scripts' => 'newarticleboost.js',
    'localBasePath' => dirname(__FILE__) . '/',
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
    'localBasePath' => dirname(__FILE__),
    'remoteExtPath' => 'wikihow/nab',
    'targets' => array('desktop', 'mobile'),
);

$wgExtensionMessagesFiles['Newarticleboost'] = dirname(__FILE__) . '/Newarticleboost.i18n.php';
$wgSpecialPages['Newarticleboost'] = 'Newarticleboost';
$wgSpecialPages['NABStatus'] = 'NABStatus';
$wgSpecialPages['Copyrightchecker'] = 'Copyrightchecker';
$wgSpecialPages['Markrelated'] = 'Markrelated';
$wgSpecialPages['NABClean'] = 'NABClean';
$wgSpecialPages['AdminMarkPromoted'] = 'AdminMarkPromoted';
$wgSpecialPages['NABPatrol'] = 'NABPatrol';
$wgSpecialPages['AdminNAD'] = 'AdminNAD';
$wgAutoloadClasses['Newarticleboost'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['NABStatus'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['Copyrightchecker'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['Markrelated'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['NABClean'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['NABPatrol'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['AdminMarkPromoted'] = dirname( __FILE__ ) . '/AdminMarkNAB.body.php';
$wgAutoloadClasses['NabQueryPage'] = dirname( __FILE__ ) . '/Newarticleboost.body.php';
$wgAutoloadClasses['AdminNAD'] = dirname( __FILE__ ) . '/AdminNAD.body.php';

$wgHooks['ArticleDelete'][] = array("wfNewArticlePatrolClearOnDelete");
$wgHooks['ArticleSaveComplete'][] = array("wfNewArticlePatrolAddOnCreation");

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

	$dbw->delete(Newarticleboost::NAB_TABLE, array('nap_page' => $article->getId()), __METHOD__);
	return true;
}

function wfNewArticlePatrolAddOnCreation($article, $user, $text, $summary, $p5, $p6, $p7) {
	global $wgUser, $wgLanguageCode;

	$db = wfGetDB(DB_MASTER);
	$t = $article->getTitle();
	if (!$t || $t->getNamespace() != NS_MAIN)  {
		return true;
	}

	if (in_array("bot", $wgUser->getGroups())) {
		// ignore bots
		return true;
	}

	$row = $db->selectRow('revision',
		array('count(*) as count',
			'min(rev_id) as min_rev',
			'min(rev_timestamp) as min_ts'),
		array('rev_page' => $article->getId()),
		__METHOD__);
	$num_revisions = $row->count;
	$min_rev = $row->min_rev;
	$min_ts = $row->min_ts;

	$row = $db->selectRow('revision', array('rev_timestamp', 'rev_user'), array('rev_id' => $min_rev), __METHOD__);
	$ts = $row->rev_timestamp;
	$userid = $row->rev_user;

	$nab_count = $db->selectField(Newarticleboost::NAB_TABLE, 'count(*)', array('nap_page' => $article->getId()), __METHOD__);

	// filter articles created by bots and non-English translators
	if ($userid > 0) {
		$revUser = User::newFromID($userid);
		if ($revUser) {
			if ($wgLanguageCode == 'en') {
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
	$rev = $article->getRevision();
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
		if ($newbie['anon'] || $user->getID()) {
			// how many edits?
			if ($newbie['edits'] > 0) {
				$count = $db->selectField(
					array('revision', 'page'),
					'count(*)',
					array('rev_page=page_id',
						'page_namespace' => NS_MAIN,
						'rev_user_text'=>$user->getName()),
					__METHOD__);
				if ($count < $newbie['edits']) {
					$nab_newbie = 1;
				}
			}
			if ($nab_newbie == 0 && $newbie['articles'] > 0) {
				// how many articles created?
				$count = $db->selectField(
					array('firstedit'),
					'count(*)',
					array('fe_user_text' => $user->getName()),
					__METHOD__);
				if ($count < $newbie['articles']) {
					$nab_newbie = 1;
				}
			}
		}

		$db->insert(Newarticleboost::NAB_TABLE,
			array(
				'nap_page' => $article->getId(),
				'nap_timestamp' => $min_ts,
				'nap_newbie' => $nab_newbie),
			__METHOD__);

		if ($wgLanguageCode == 'en') {
			$db->insert('nab_atlas', array('na_page_id' => $article->getId()), __METHOD__, array('IGNORE'));
		}
	}

	return true;
}

/*****

time pt-online-schema-change --execute --alter "ADD COLUMN nap_demote tinyint(3) UNSIGNED NOT NULL DEFAULT 0" D=wikidb_112,t=newarticlepatrol
time pt-online-schema-change --execute --alter "ADD COLUMN nap_atlas_score tinyint(4) NOT NULL DEFAULT -1" D=wikidb_112,t=newarticlepatrol

UPDATE `newarticlepatrol` INNER JOIN `nab_atlas` ON newarticlepatrol.nap_page = nab_atlas.na_page_id set newarticlepatrol.nap_atlas_score = nab_atlas.na_atlas_score
*****/
