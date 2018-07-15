<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Mypages-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Mypages',
	'author' => 'Travis Derouin',
	'description' => 'Provides redirecting static urls to dynamic user pages',
);


$wgSpecialPages['Mypages'] = 'Mypages';
$wgAutoloadClasses['Mypages'] = dirname( __FILE__ ) . '/Mypages.body.php';

$wgExtensionMessagesFiles['MypagesAlias'] = __DIR__  . '/Mypages.alias.php';

