<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:BunchPatrol-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'BunchPatrol',
	'author' => 'Travis Derouin',
	'description' => 'Bunches a bunch of edits of 1 user together',
	'url' => 'http://www.wikihow.com/WikiHow:BunchPatrol-Extension',
);

$wgExtensionMessagesFiles['BunchPatrol'] = __DIR__ . '/Bunchpatrol.i18n.php';

$wgSpecialPages['BunchPatrol'] = 'BunchPatrol';
$wgAutoloadClasses['BunchPatrol'] = __DIR__ . '/Bunchpatrol.body.php';
