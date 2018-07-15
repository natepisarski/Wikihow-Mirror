<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**
 * An extension that displays number of new articles and number of rising stars
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Leaderboard',
	'author' => 'Vu Nguyen',
	'description' => 'Shows leaderboard stats',
);

$wgExtensionMessagesFiles['Leaderboard'] = __DIR__ . '/Leaderboard.i18n.php';

$wgSpecialPages['Leaderboard'] = 'Leaderboard';
$wgAutoloadClasses['Leaderboard'] = __DIR__ . '/SpecialLeaderboard.php';
$wgAutoloadClasses['LeaderboardStats'] = __DIR__ . '/LeaderboardStats.php';

$wgResourceModules['ext.wikihow.leaderboard'] = [
    'styles' => ['Leaderboard.css'],
    'scripts' => ['Leaderboard.js'],
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow',
    'position' => 'top',
    'targets' => ['desktop', 'mobile'],
    'dependencies' => ['ext.wikihow.common_top'],
];

function wfLeaderboardTabs(&$tabArray) {
	$tabWriting->link = '/Special:Leaderboard/articles_written';
	$tabWriting->text = 'Writing';
	$tabArray[] = $tabWriting;

	$tabNab->link = '/Special:Leaderboard/articles_nabed';
	$tabNab->text = 'RC and NAB';
	$tabArray[] = $tabNab;

	$tabOther->link = '/Special:Leaderboard/total_edits';
	$tabOther->text = 'Other';
	$tabArray[] = $tabOther;

	return true;
}
