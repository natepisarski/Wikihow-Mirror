<?php

/**
 * PatrolThrottle extension - a special page for community managers
 * and admins to limit the number of patrols a user can do per day.
 *
 * @file
 * @package MediaWiki
 * @ingroup Extensions
 *
 * @version 1.1.3 (2014-08-07)
 * @author Lojjik Braughler
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 3.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file can only be run through MediaWiki.' );
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'PatrolThrottle',
	'version' => '1.1.2',
	'author' => 'Lojjik Braughler',
	'url' => 'http://src.wikihow.com',
	'license-name' => 'GPL-3.0+',
	'descriptionmsg' => 'patrolthrottle-desc'
);

// Internationalization
$wgMessagesDirs['PatrolThrottle'] = __DIR__ . '/i18n';
$wgAvailableRights[] = 'patrolthrottle';
$wgExtensionMessagesFiles['PatrolThrottleAliases'] = __DIR__ . '/PatrolThrottle.alias.php';

$wgAutoloadClasses['SpecialPatrolThrottle'] = __DIR__ . '/SpecialPatrolThrottle.php';
$wgAutoloadClasses['PatrolUser'] = __DIR__ . '/PatrolUser.class.php';
$wgAutoloadClasses['PatrolThrottleUITemplate'] = __DIR__ . '/PatrolThrottleForm.tmpl.php';
$wgSpecialPages['PatrolThrottle'] = 'SpecialPatrolThrottle';
$wgSpecialPageGroups['PatrolThrottle'] = 'users';

$wgLogTypes[] = 'throttle';
$wgLogActionsHandlers['throttle/added'] = 'LogFormatter';
$wgLogActionsHandlers['throttle/changed'] = 'LogFormatter';
$wgLogActionsHandlers['throttle/removed'] = 'LogFormatter';

$wgResourceModules['ext.wikihow.PatrolThrottle'] = array(
			'styles' => 'patrolthrottle.css',
			'scripts' => 'patrolthrottle.js',
			'localBasePath' => __DIR__ . '/',
			'remoteExtPath' => '/extensions/wikihow/PatrolThrottle'
);

$wgHooks['MarkPatrolledBatchComplete'][] = 'PatrolUser::onMarkPatrolledBatchComplete';

