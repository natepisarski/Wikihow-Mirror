<?php

if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**#@+
 *  Lists pages that have links to non-existant pages
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

/**
 *
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'RCBuddy',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Helper special page for the wikihow editors toobar',
);

$wgSpecialPages['RCBuddy'] = 'RCBuddy';
$wgAutoloadClasses['RCBuddy'] = dirname( __FILE__ ) . '/RCBuddy.body.php';

