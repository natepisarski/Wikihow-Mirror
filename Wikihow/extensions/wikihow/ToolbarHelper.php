<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

/**#@+
 * Server side helper for the Firefox toolbar
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
/**
 *
 * @package MediaWiki
 * @subpackage SpecialPage
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Toolbarhelper',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Server side helper for the toolbar, could be replaced by RCBuddy at some point',
);


$wgSpecialPages['Toolbarhelper'] = 'Toolbarhelper';
$wgAutoloadClasses['Toolbarhelper'] = dirname( __FILE__ ) . '/ToolbarHelper.body.php';
 
