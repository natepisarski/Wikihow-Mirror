<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();
    
/**#@+
 * Changes the real name of a user
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Changerealname-Extension Documentation
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgAvailableRights[] = 'changerealname';
$wgGroupPermissions['sysop']['changerealname'] = true;

$wgExtensionCredits['other'][] = array(
	'name' => 'Changerealname',
	'author' => 'Travis Derouin',
	'description' => 'Changes the real name of a user',
);

$wgExtensionMessagesFiles['Changerealname'] = dirname(__FILE__) . '/Changerealname.i18n.php';
$wgSpecialPages['Changerealname'] = 'Changerealname';
$wgAutoloadClasses['Changerealname'] = dirname( __FILE__ ) . '/Changerealname.body.php';

