<?php

if (!defined('MEDIAWIKI')) die();

/**#@+
 * The wikiHow community dashboard.  It's a list of widgets that update in
 * close to real time.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:CommunityDashboard-Extension Documentation
 *
 * @author Reuben Smith <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'CommunityDashboard',
	'author' => 'Bebeth Steudel and Reuben Smith',
	'description' => 'Shows the status of a bunch of different aspects of the wikiHow site',
	'url' => 'http://www.wikihow.com/WikiHow:CommunityDashboard-Extension',
);

$wgSpecialPages['CommunityDashboard'] = 'CommunityDashboard';
$wgAutoloadClasses['CommunityDashboard'] = __DIR__ . '/CommunityDashboard.body.php';
$wgAutoloadClasses['DashboardData'] = __DIR__ . '/DashboardData.php';
$wgAutoloadClasses['DashboardWidget'] = __DIR__ . '/DashboardWidget.php';
$wgExtensionMessagesFiles['CommunityDashboard'] = __DIR__ . '/CommunityDashboard.i18n.php';
$wgExtensionMessagesFiles['CommunityDashboardAliases'] = __DIR__ . '/CommunityDashboard.alias.php';

/**
 * $wgWidgetList is a list of that can be displayed on the CommunityDashboard
 * special page.  Each widget listed should have a class named
 * ClassNameWidget.php in the widget/ subdirectory.  The class loaded in
 * this file should extend the WHDashboardWidget class.
 *
 * IMPORTANT NOTE: every widget defined in this array must also be
 * defined in $wgWidgetShortCodes below.
 */
$wgWidgetList = array(
	'TechFeedbackAppWidget',
	//'ArticleFeedbackAppWidget',
	'WriteAppWidget',
	'RecentChangesAppWidget',
	'CategorizerAppWidget',
	'TopicTaggingAppWidget',
	'FormatAppWidget',
	'CopyeditAppWidget',
	'CleanupAppWidget',
	// 'StubAppWidget',
	'QcAppWidget',
	'AddVideosAppWidget',
	'NabAppWidget',
	'TopicAppWidget',
	'NfdAppWidget',
	'TipsPatrolWidget',
	'TipsGuardianAppWidget',
	'SpellcheckerAppWidget',
	'CategoryGuardianAppWidget',
	'UCIPatrolWidget',
	'WelcomeWagonWidget',
	// 'RateAppWidget',
	'UnitGuardianAppWidget',
	'SortQuestionsAppWidget',
	'AnswerQuestionsAppWidget',
	// 'DuplicateTitlesAppWidget',
	'FixFlaggedAnswersAppWidget',
	'QAPatrolWidget',
	'TechTestingAppWidget',
	'QuizYourselfWidget'
);

/**
 * Define some short codes for apps, so that the long names don't have to be
 * transmitted constantly.
 */
$wgWidgetShortCodes = array(
	'RecentChangesAppWidget' => 'rc',
	'NabAppWidget' => 'nab',
	'AddVideosAppWidget' => 'vid',
	'WriteAppWidget' => 'wri',
	'FormatAppWidget' => 'for',
	'CopyeditAppWidget' => 'cop',
	'CleanupAppWidget' => 'cln',
	'CategorizerAppWidget' => 'cat',
	// 'StubAppWidget' => 'stu',
	'QcAppWidget' => 'qc',
	'TopicAppWidget' => 'tpc',
	'NfdAppWidget' => 'nfd',
	'TipsPatrolWidget' => 'tip',
	'TipsGuardianAppWidget' => 'tg',
	'SpellcheckerAppWidget' => 'spl',
	'CategoryGuardianAppWidget' => 'catch',
	'UCIPatrolWidget' => 'uci',
	'WelcomeWagonWidget' => 'welcomewagon',
	// 'RateAppWidget' => 'rat',
	'UnitGuardianAppWidget' => 'ung',
	'QAPatrolWidget' => 'qap',
	'SortQuestionsAppWidget' => 'sqt',
	'AnswerQuestionsAppWidget' => 'aq',
	'TechFeedbackAppWidget' => 'tf',
	'ArticleFeedbackAppWidget' => 'af',
	'TechTestingAppWidget' => 'tv',
	// 'DuplicateTitlesAppWidget' => 'dt',
	'FixFlaggedAnswersAppWidget' => 'ffa',
	'TopicTaggingAppWidget' => 'ttt',
	'QuizYourselfWidget' => 'qy'
);

/*top mobile widgets*/
$wgMobilePriorityWidgetList = array(
	'SortQuestionsAppWidget',
	'TechFeedbackAppWidget',
	'SpellcheckerAppWidget',
);

/*bottom mobile widgets*/
$wgMobileWidgetList = array(
	'TopicTaggingAppWidget',
	'CategoryGuardianAppWidget',
	'TipsGuardianAppWidget',
	'UCIPatrolWidget',
	'RecentChangesAppWidget',
	'UnitGuardianAppWidget',
	'QuizYourselfWidget'
);

/*widgets that SHOULD NOT show on desktop*/
$wgMobileOnlyWidgetList = array(
	'TipsGuardianAppWidget',
	'UnitGuardianAppWidget',
	'SortQuestionsAppWidget',
	'QuizYourselfWidget'
);

/**
 * Community Dashboard debug flag -- always check-in as false and make a
 * local edit.
 */
define('COMDASH_DEBUG', false);

/**
 * Hooks
 */
$wgHooks['MarkPatrolled'][] = array("wfMarkCompleted", "RecentChangesAppWidget"); //recent changes
$wgHooks['NABArticleFinished'][] = array("wfMarkCompleted", "NabAppWidget"); //nab
$wgHooks['PageContentSaveComplete'][] = array("wfMarkCompletedWrite"); //write articles
$wgHooks['EditFinderArticleSaveComplete'][] = array("wfMarkCompletedEF"); //stub, format, cleanup, copyedit
$wgHooks['CategoryHelperSuccess'][] = array("wfMarkCompleted", "CategorizerAppWidget"); //categorizer
$wgHooks['VAdone'][] = array("wfMarkCompleted", "AddVideosAppWidget"); //add videos
$wgHooks['QCVoted'][] = array("wfMarkCompleted", "QcAppWidget"); //qc
$wgHooks['NFDVoted'][] = array("wfMarkCompleted", "NfdAppWidget"); //nfd
$wgHooks['Spellchecked'][] = array("wfMarkCompleted", "SpellcheckerAppWidget"); //spellchecker
$wgHooks['TipsPatrolled'][] = array("wfMarkCompleted", "TipsPatrolWidget");
$wgHooks['PicturePatrolled'][] = array("wfMarkCompleted", "UCIPatrolWidget");
$wgHooks['WelcomeWagonMessageSent'][] = array("wfMarkCompleted", "WelcomeWagonWidget");
$wgHooks['SpecialTechFeedbackItemCompleted'][] = array("wfMarkCompleted", "TechFeedbackAppWidget");
$wgHooks['SpecialArticleFeedbackItemCompleted'][] = array("wfMarkCompleted", "ArticleFeedbackAppWidget");
$wgHooks['SpecialTechVerifyItemCompleted'][] = array("wfMarkCompleted", "TechTestingAppWidget");
$wgHooks["IsEligibleForMobileSpecial"][] = array("wfCDIsEligibleForMobile");

function wfMarkCompleted($appName) {
	$dashboardData = new DashboardData();
	$dashboardData->setDailyCompletion($appName);

	return true;
}

function wfMarkCompletedEF($wikiPage, $text, $summary, $user, $type) {
	switch (strtolower($type)) {
		case 'copyedit':
			wfMarkCompleted("CopyeditAppWidget");
			break;
		case 'cleanup':
			wfMarkCompleted("CleanupAppWidget");
			break;
		case 'format':
			wfMarkCompleted("FormatAppWidget");
			break;
		case 'stub':
			wfMarkCompleted("StubAppWidget");
			break;
		case 'topic':
			wfMarkCompleted("TopicAppWidget");
			break;
		default:
			break;
	}
	return true;
}

function wfMarkCompletedWrite(&$wikiPage, &$user, $content, $summary, $p5, $p6, $p7) {
	try {
		$dbw = wfGetDB(DB_MASTER);
		$t = $wikiPage->getTitle();
		if (!$t || !$t->inNamespace(NS_MAIN))  {
			return true;
		}

		$num_revisions = $dbw->selectField('revision', 'count(*)', array('rev_page' => $wikiPage->getId()), __METHOD__);

		if ($num_revisions == 1)
			wfMarkCompleted("WriteAppWidget");
	} catch (Exception $e) {
		return true;
	}
	return true;
}

function wfCDIsEligibleForMobile(&$isEligible) {
	global $wgTitle;
	if ($wgTitle && strrpos($wgTitle->getText(), "CommunityDashboard") === 0) {
		$isEligible = true;
	}

	return true;
}
