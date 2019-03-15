<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 * The wikiHow category page with tiled layout and infinite scrolling.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:WikihowCategoryPage-Extension Documentation
 *
 *
 * @author Reuben Smith <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgAutoloadClasses['WikihowCategoryPage'] = __DIR__ . '/WikihowCategoryPage.body.php';
$wgAutoloadClasses['DesktopWikihowCategoryPage'] = __DIR__ . '/DesktopWikihowCategoryPage.body.php';
$wgAutoloadClasses['MobileWikihowCategoryPage'] = __DIR__ . '/mobile_category_page/MobileWikihowCategoryPage.body.php';
$wgAutoloadClasses['CategoryData'] = __DIR__ . '/CategoryData.class.php';
$wgAutoloadClasses['CategoryCarousel'] = __DIR__ . '/category_carousel/CategoryCarousel.class.php';
$wgAutoloadClasses['TopCategoryData'] = __DIR__ . '/TopCategoryData.class.php';

$wgHooks['ArticleFromTitle'][] = array('WikihowCategoryPage::onArticleFromTitle');

$wgResourceModules['ext.wikihow.desktop_category_page'] = array(
	'scripts' => array('categories-owl.js'),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/categories',
	'position' => 'top',
	'targets' => array('desktop'),
	'dependencies' => array('ext.wikihow.common_top', 'wikihow.common.slick'),
);

$wgResourceModules['mobile.wikihow.mobile_category_page'] = array(
	'styles' => array('mobile_category_page.less'),
	'scripts' => array('mobile_category_page.js'),
	'localBasePath' => __DIR__ . '/mobile_category_page',
	'remoteExtPath' => 'wikihow/categories/mobile_category_page',
	'position' => 'top',
	'targets' => array('mobile', 'desktop'),
	'dependencies' => array('mobile.wikihow.category_carousel','ext.wikihow.common_top'),
	'messages' => [
		'cat_show_more',
		'cat_show_less'
	]
);

$wgResourceModules['mobile.wikihow.category_carousel'] = array(
	'styles' => array('category_carousel.less'),
	'scripts' => array('category_carousel.js'),
	'localBasePath' => __DIR__ . '/category_carousel',
	'remoteExtPath' => 'wikihow/categories/category_carousel',
	'position' => 'top',
	'targets' => array('mobile', 'desktop'),
	'dependencies' => array('wikihow.common.mustache', 'wikihow.common.slick'),
);
