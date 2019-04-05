<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Quizzes',
	'author' => 'Scott Cushman',
	'description' => 'The Quiz Displayer of Awesomeness',
);

$wgSpecialPages['Quizzes'] = 'Quizzes';
$wgAutoloadClasses['Quizzes'] = __DIR__ . '/Quizzes.body.php';
$wgExtensionMessagesFiles['Quizzes'] = __DIR__ . '/Quizzes.i18n.php';

/*importer*/
$wgSpecialPages['AdminQuizzes'] = 'AdminQuizzes';
$wgAutoloadClasses['AdminQuizzes'] = __DIR__ . '/AdminQuizzes.body.php';
$wgGroupPermissions['*']['AdminQuizzes'] = false;
$wgGroupPermissions['staff']['AdminQuizzes'] = true;

$wgResourceModules['ext.wikihow.quizzes'] = [
	'styles' => ['quizzes.css'],
	'scripts' => [
		'quizzes.js',
		//'interstitialCookie.js',
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/quizzes',
	'position' => 'top',
	'targets' => ['desktop', 'mobile'],
	'dependencies' => ['ext.wikihow.common_top'],
];

// Reuben disabled quizzes (per Eliz) because it was going to be work to make them
// mobile-friendly on Oct 1, 2018. See LH #2541.
//$wgHooks['WebRequestPathInfoRouter'][] = array('wfGetQuizPage');
//$wgHooks["BeforeParserFetchFileAndTitle2"][] = array("wfGrabQuizCTA");
//$wgHooks["PageContentSaveComplete"][] = array("wfConnectQuiz");

/* disabled unused code
function wfGrabQuizCTA(&$parser, &$nt, &$ret, $ns) {
	global $wgCanonicalNamespaceNames;
	if (!$nt) return true;
	if ($ns == NS_QUIZ) {
		//remove the namespace and colon
		$nt = preg_replace('@'.$wgCanonicalNamespaceNames[$ns].':@','',$nt);
		//do it
		$ret = Quizzes::grabQuizCTA($nt, $parser->mTitle);
	}
	return true;
}
*/

/*
 * If someone added a [[Quiz:foo]] then add it to the link table
 */
/* disabled unused hook. NOTE: this code needs to be upgraded to use the PageContentSaveComplete (rather than ArticleSaveComplete)
function wfConnectQuiz(&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision) {
	if (!$article || !$text) return true;
	if ($article->getID() == 0) return true;

	//first check to see if there's a [[Quiz:foo]] in the article
	$count = preg_match_all('@\[\[Quiz:([^\]]*)\]\]@i', $text, $matches, PREG_SET_ORDER);

	if ($count) {
		$quiz_array = array();

		//cycle through and clean up the samples, check for multiples, etc.
		foreach ($matches as $match) {
			$quiz = preg_replace('@ @','-',$match[1]);

			//check for multiple
			$quizzes_array = explode(',',$quiz);
			foreach ($quizzes_array as $quiz) {
				$quiz_array[] = $quiz;
			}
		}

		//update that link table
		foreach ($quiz_array as $quiz) {
			Quizzes::updateLinkTable($article,$quiz);
		}

		//make sure we didn't lose any
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('quiz_links', 'ql_name', array('ql_page' => $article->getID()), __METHOD__);

		foreach ($res as $row) {
			if (!in_array($row->ql_name, $quiz_array)) {
				//no longer on the page; remove it
				Quizzes::updateLinkTable($article, $row->ql_name, false);
			}
		}
	}
	else {
		//nothing in the article?
		//remove anything in the link table if there are mentions
		Quizzes::updateLinkTable($article,'',false);
	}

	return true;
}
*/

// Display "/Quiz/[sample name]" but load "/Special:Quizzes/[sample name]"
/* disabled unused hook
function wfGetQuizPage( $router ) {
	$router->add( '/Quiz/$1', array( 'title' => 'Special:Quizzes/$1' ) );
	return true;
}
*/
