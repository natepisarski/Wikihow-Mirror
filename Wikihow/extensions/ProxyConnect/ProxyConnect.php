<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:ProxyConnect-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'ProxyConnect',
	'author' => 'Travis Derouin',
	'description' => 'Implements the server side functionality of Proxy Connect',
	'url' => 'http://www.wikihow.com/WikiHow:ProxyConnect-Extension',
);


$wgSpecialPages['ProxyConnect'] = 'ProxyConnect';
$wgAutoloadClasses['ProxyConnect'] = dirname( __FILE__ ) . '/ProxyConnect.body.php';
