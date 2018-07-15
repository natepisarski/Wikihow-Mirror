<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * Takes a set of URLs, finds related pages, and adds inbound links to the submitted pages
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
    'description' => 'Takes a set of URLs, finds related pages, and adds inbound links to the submitted pages',
);

$wgSpecialPages['AddRelatedLinks'] = 'AddRelatedLinks';
$wgAutoloadClasses['AddRelatedLinks'] = dirname( __FILE__ ) . '/AddRelatedLinks.body.php';


