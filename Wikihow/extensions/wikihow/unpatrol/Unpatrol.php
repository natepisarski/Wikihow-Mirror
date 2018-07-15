<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Unpatrol-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Unpatrol',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Unpatrol bad patrols',
);

$wgSpecialPages['UnpatrolTips'] = 'UnpatrolTips';
$wgAutoloadClasses['UnpatrolTips'] = dirname( __FILE__ ) . '/Unpatrol.body.php';
$wgExtensionMessagesFiles['UnpatrolTipsAliases'] = __DIR__ . '/Unpatrol.alias.php';
$wgExtensionMessagesFiles['UnpatrolTips'] = __DIR__ . '/Unpatrol.i18n.php';

$wgSpecialPages['Unpatrol'] = 'Unpatrol';
$wgAutoloadClasses['Unpatrol'] = dirname( __FILE__ ) . '/Unpatrol.body.php';

$wgLogTypes[] = 'undotips';
$wgLogNames['undotips'] = 'undotips';
$wgLogHeaders['undotips'] = 'undotips';

$wgLogTypes[] = 'unpatrol';
$wgLogNames['unpatrol'] = 'unpatrol';
$wgLogHeaders['unpatrol'] = 'unpatrol';

