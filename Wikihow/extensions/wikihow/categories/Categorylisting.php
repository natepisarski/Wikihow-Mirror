<?php

if ( ! defined( 'MEDIAWIKI' ) ) {
	die();
}

/**#@+
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:CategoryListing-Extension Documentation
 *
 * @author Travis Derouin (wikiHow)
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgShowRatings = true; // set this to false if you want your ratings hidden

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CategoryListing',
	'author' => 'Travis Derouin (wikiHow)',
	'description' => 'A curated list of the top level categories on wikiHow',
);

$wgSpecialPages['CategoryListing'] = 'CategoryListing';
$wgAutoloadClasses['CategoryListing'] = __DIR__ . '/Categorylisting.body.php';
$wgExtensionMessagesFiles['CategoryListingAliases'] = __DIR__ . '/Categorylisting.alias.php';

$wgResourceModules['ext.wikihow.mobile_category_listing'] = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/categories',
	'styles' => ['category-listing-responsive.less'],
	'position' => 'top',
	'targets' => ['mobile'],
];

$wgResourceModules['ext.wikihow.mobile_category_listing_intl'] = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/categories',
	'styles' => ['category-listing-responsive-intl.less'],
	'position' => 'top',
	'targets' => ['mobile'],
];
