<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Interests-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'Interests',
	'author' => 'Travis Derouin',
	'description' => 'Gather a bunch of interests for a user and use it to suggest articles for them to edit or create', 
	'url' => 'http://www.wikihow.com/WikiHow:Interests-Extension',
);

$wgExtensionMessagesFiles['Interests'] = dirname(__FILE__) . '/Interests.i18n.php';

$wgSpecialPages['Interests'] = 'Interests';
$wgAutoloadClasses['Interests'] = dirname( __FILE__ ) . '/Interests.body.php';

