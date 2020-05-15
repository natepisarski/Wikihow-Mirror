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
 * @author Travis Derouin (wikiHow)
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'BunchPatrol',
	'author' => 'Travis Derouin (wikiHow)',
	'description' => 'Bunches many edits of a user together, so they can be patrolled all at once',
);

$wgExtensionMessagesFiles['BunchPatrol'] = __DIR__ . '/Bunchpatrol.i18n.php';

$wgSpecialPages['BunchPatrol'] = 'BunchPatrol';
$wgAutoloadClasses['BunchPatrol'] = __DIR__ . '/Bunchpatrol.body.php';
