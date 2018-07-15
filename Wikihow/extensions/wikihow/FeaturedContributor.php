<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that displays number of new articles and number of rising stars
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 * @author Vu Nguyen <vu@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'FeaturedContributor',
	'author' => 'Vu Nguyen',
	'description' => 'displays featured contributor widget',
);

$wgSpecialPages['FeaturedContributor'] = 'FeaturedContributor';
$wgAutoloadClasses['FeaturedContributor'] = dirname( __FILE__ ) . '/FeaturedContributor.body.php';

