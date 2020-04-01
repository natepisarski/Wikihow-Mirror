<?php

if ( !defined('MEDIAWIKI') ) die();

/**#@+
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:RateArticle-Extension Documentation
 *
 *
 * @author Bebeth Steudel
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgShowRatings = false; // set this to false if you want your ratings hidden

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'RateItem',
    'author' => 'Bebeth <bebeth@wikihow.com>',
    'description' => 'Provides a basic ratings system for article, samples, etc',
);

$wgExtensionMessagesFiles['RateItem'] = __DIR__ . '/Rating.i18n.php';
$wgExtensionMessagesFiles['RateItemAliases'] = __DIR__ . '/Rating.alias.php';
$wgExtensionMessagesFiles['RatingReasonAliases'] = __DIR__ . '/Rating.alias.php';

$wgSpecialPages['RateItem'] = 'RateItem';
$wgSpecialPages['ListRatings'] = 'ListRatings';
$wgSpecialPages['ClearRatings'] = 'ClearRatings';
$wgSpecialPages['AccuracyPatrol'] = 'AccuracyPatrol';
$wgSpecialPages['RatingReason'] = 'RatingReason';

$wgAutoloadClasses['RatingsTool'] = __DIR__ . '/RatingsTool.php';
$wgAutoloadClasses['RatingArticle'] = __DIR__ . '/RatingArticle.php';
$wgAutoloadClasses['RatingSample'] = __DIR__ . '/RatingSample.php';
$wgAutoloadClasses['RatingArticleMHStyle'] = __DIR__ . '/RatingArticleMHStyle.php';
$wgAutoloadClasses['RatingStar'] = __DIR__ . '/RatingStar.php';

$wgAutoloadClasses['AccuracyPatrol'] = __DIR__ . '/AccuracyPatrol.php';
$wgAutoloadClasses['ClearRatings'] = __DIR__ . '/ClearRatings.php';
$wgAutoloadClasses['ListRatings'] = __DIR__ . '/ListRatings.php';
$wgAutoloadClasses['RateItem'] = __DIR__ . '/RateItem.php';
$wgAutoloadClasses['RatingReason'] = __DIR__ . '/RatingReason.php';
$wgAutoloadClasses['TechRating'] = __DIR__ . '/techrating/TechRating.class.php';

$wgAutoloadClasses['RatingRedis'] = __DIR__ . '/RatingRedis.php';

$wgHooks['RatingAdded'][] = array('RatingsTool::sendHelpfulEmails');

$wgLogTypes[] = 'accuracy';
$wgLogNames['accuracy'] = 'accuracylogpage';
$wgLogHeaders['accuracy'] = 'accuracylogtext';
$wgLogTypes[] = 'acc_sample';
$wgLogNames['acc_sample'] = 'accsamplelogpage';
$wgLogHeaders['acc_sample'] = 'accsamplelogtext';

$wgResourceModules['ext.wikihow.ratingreason'] = array(
	'scripts' => 'rating.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Rating',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'messages' => array()
);

$wgResourceModules['ext.wikihow.ratingreason.styles'] = array(
	'styles' => 'rating.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Rating',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile')
);

$wgResourceModules['ext.wikihow.ratingreason.mh_style'] = array(
	'scripts' => 'rating_mh_style.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Rating',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'messages' => array()
);

$wgResourceModules['ext.wikihow.ratingreason.mh_style.styles'] = array(
	'styles' => 'rating_mh_style.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Rating',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile')
);

$wgResourceModules['ext.wikihow.rating_sidebar'] = array(
	'scripts' => 'rating_sidebar.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Rating',
	'messages' => array(
		'ras_res_yes_hdr',
		'ras_res_no_hdr'
	),
	'position' => 'bottom',
	'targets' => array('desktop')
);

$wgResourceModules['ext.wikihow.rating_sidebar.styles'] = array(
	'styles' => 'rating_sidebar.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Rating',
	'position' => 'top',
	'targets' => array('desktop')
);

$wgResourceModules['ext.wikihow.rating_desktop.style'] = array(
	'styles' => 'rating_desktop_body.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/Rating',
	'position' => 'bottom',
	'targets' => array('desktop')
);

