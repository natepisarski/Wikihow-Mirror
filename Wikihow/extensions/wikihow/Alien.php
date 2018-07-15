<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

/**#@+
 * Server side helper for the front end caches. This special page was initially
 * named "BackendProbe" without a trace of irony. So had to rename.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author Reuben <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
/**
 * @package MediaWiki
 * @subpackage SpecialPage
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Alien',
    'author' => 'Reuben <reuben@wikihow.com>',
    'description' => 'Server-side helper for front end cache probing',
);


$wgSpecialPages['Alien'] = 'Alien';
$wgAutoloadClasses['Alien'] = dirname( __FILE__ ) . '/Alien.body.php';
 
