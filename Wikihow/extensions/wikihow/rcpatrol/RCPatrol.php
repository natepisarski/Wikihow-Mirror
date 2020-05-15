<?php

if ( !defined( 'MEDIAWIKI' ) ) {
exit(1);
}

/**#@+
 * A simple extension that allows users to enter a title before creating a page.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author Travis Derouin (wikiHow)
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RCPatrol',
	'author' => 'Travis Derouin',
	'description' => 'An improved way of doing RC Patrol',
);

$wgExtensionMessagesFiles['RCPatrol'] = __DIR__ . '/RCPatrol.i18n.php';
$wgExtensionMessagesFiles['RCPatrolAliases'] = __DIR__ . '/RCPatrol.alias.php';
$wgSpecialPages['RCPatrol'] = 'RCPatrol';
$wgAutoloadClasses['RCPatrol'] = __DIR__ . '/RCPatrol.body.php';

$wgSpecialPages['RCPatrolGuts'] = 'RCPatrolGuts';
$wgAutoloadClasses['RCPatrolGuts'] = __DIR__ . '/RCPatrol.body.php';

$wgResourceModules['ext.wikihow.rcpatrol_styles'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'styles' => [ 'rcpatrol.css' ],
	'remoteExtPath' => 'wikihow/rcpatrol',
	'position' => 'top',
];

$wgResourceModules['ext.wikihow.rcpatrol'] = [
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop', 'mobile' ],
	'scripts' => [
		'rcpatrol.js',
	],
	'dependencies' => [ 'ext.wikihow.QuickNoteEdit' ],
	'remoteExtPath' => 'wikihow/rcpatrol',
	'position' => 'top',
];
