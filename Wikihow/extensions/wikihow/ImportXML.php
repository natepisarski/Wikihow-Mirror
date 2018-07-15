<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:ImportXML-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ImportXML',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Importing exporting XML',
);

$wgSpecialPages['ImportXML'] = 'ImportXML';
$wgAutoloadClasses['ImportXML'] = dirname( __FILE__ ) . '/ImportXML.body.php';
$wgSpecialPages['ExportXML'] = 'ExportXML';
$wgAutoloadClasses['ExportXML'] = dirname( __FILE__ ) . '/ImportXML.body.php';


