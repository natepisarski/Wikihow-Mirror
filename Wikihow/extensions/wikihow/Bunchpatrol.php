<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Bunchpatrol-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'Bunchpatrol',
	'author' => 'Travis Derouin',
	'description' => 'Bunches a bunch of edits of 1 user together',
	'url' => 'http://www.wikihow.com/WikiHow:Bunchpatrol-Extension',
);

$wgExtensionMessagesFiles['Bunchpatrol'] = dirname(__FILE__) . '/Bunchpatrol.i18n.php';

$wgSpecialPages['Bunchpatrol'] = 'Bunchpatrol';
$wgAutoloadClasses['Bunchpatrol'] = dirname( __FILE__ ) . '/Bunchpatrol.body.php';

