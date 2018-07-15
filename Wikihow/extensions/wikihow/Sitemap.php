<?php

if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**#@+
 * Generates a page of links to the top level categories and their subcatgories;
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Sitemap',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Generates a page of links to the top level categories and their subcatgories',
);

$wgExtensionMessagesFiles['Sitemap'] = dirname(__FILE__) . '/Sitemap.i18n.php';

$wgSpecialPages['Sitemap'] = 'Sitemap';
$wgAutoloadClasses['Sitemap'] = dirname( __FILE__ ) . '/Sitemap.body.php';
$wgExtensionMessagesFiles['SitemapAlias'] = dirname( __FILE__ ) . '/Sitemap.alias.php';
 
