<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Standings',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'The standings widget',
);

// INTL: set $dir parth to the current file path.  Autoloader was having trouble finding otherwise
$oldDirIntl = $dir;
$dir = __DIR__ . '/';
$wgSpecialPages['Standings'] = 'Standings';
$wgAutoloadClasses['Standings'] =  $dir . 'Standings.body.php';
$wgExtensionMessagesFiles['Standings'] = $dir . 'Standings.i18n.php';

$wgAutoloadClasses['StandingsGroup'] =  $dir . 'Standings.class.php';
$wgAutoloadClasses['StandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QCStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QCStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['NFDStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['NFDStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['IntroImageStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['IntroImageStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['VideoStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['VideoStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QuickEditStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QuickEditStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['RCPatrolStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['RCPatrolStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['EditFinderStandingsGroup'] 			= $dir . 'Standings.class.php';
$wgAutoloadClasses['EditFinderStandingsIndividual'] 	= $dir . 'Standings.class.php';
$wgAutoloadClasses['CategorizationStandingsGroup'] 			= $dir . 'Standings.class.php';
$wgAutoloadClasses['CategorizationStandingsIndividual'] 	= $dir . 'Standings.class.php';
$wgAutoloadClasses['NABStandingsGroup'] =	$dir . 'Standings.class.php';
$wgAutoloadClasses['RequestsAnsweredStandingsGroup'] =	$dir . 'Standings.class.php';
$wgAutoloadClasses['SpellcheckerStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['SpellcheckerStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TechFeedbackStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TechFeedbackStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['ArticleFeedbackStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['ArticleFeedbackStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TechTestingStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TechTestingStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['UCIPatrolStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['UCIPatrolStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TipsPatrolStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TipsPatrolStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['WelcomeWagonStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['WelcomeWagonStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['RateToolStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['RateToolStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['SortQuestionsStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['SortQuestionsStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['DuplicateTitlesStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['DuplicateTitlesStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['FixFlaggedAnswersStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['FixFlaggedAnswersStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QAPatrolStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QAPatrolStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TopicTaggingStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['TopicTaggingStandingsIndividual'] = $dir . 'Standings.class.php';

// INTL: Change $dir path back to what it was before in case this is used elsewhere.
$dir = $oldDirIntl;
