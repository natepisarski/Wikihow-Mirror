<?php

/**
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @link http://www.wikihow.com/WikiHow:Patrolcount-Extension Documentation
 * @author Lojjik Braughler <llbraughler@gmail.com>
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'PatrolCount',
	'url' => 'http://src.wikihow.com',
	'author' => 'Lojjik Braughler, Travis Derouin',
	'description' => 'Lists number of patrols by user in the past day according to user\'s specified timezone, or GMT by default'
);

$wgMessagesDirs['PatrolCount'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles[ 'PatrolCountAliases' ] = __DIR__ . '/PatrolCount.alias.php';
$wgSpecialPages['PatrolCount'] = 'PatrolCount';
$wgAutoloadClasses['PatrolCount'] = __DIR__ . '/PatrolCount.body.php';
$wgResourceModules['ext.wikihow.PatrolCount'] = array(
	'styles' => array( 'PatrolCount.css' ),
	'targets' => array( 'mobile', 'desktop' ),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/PatrolCount'
);

