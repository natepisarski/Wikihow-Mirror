<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 * The wikiHow homepage with based on 2013 redesign.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author Bebeth Steudel <bebeth@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgAutoloadClasses['WikihowHomepage'] = __DIR__ . '/WikihowHomepage.body.php';
$wgAutoloadClasses['WikihowMobileHomepage'] = __DIR__ . '/WikihowMobileHomepage.body.php';
$wgExtensionMessagesFiles['WikihowHomepage'] = __DIR__ . '/WikihowHomepage.i18n.php';
$wgMessagesDirs['WikihowMobileHomepage'] = __DIR__ . '/i18n/';

$wgHooks['ArticleFromTitle'][] = array('WikihowHomepage::onArticleFromTitle');
$wgHooks['ArticleJustBeforeBodyClose'][] = array('WikihowHomepage::onArticleJustBeforeBodyClose');
$wgHooks['MobileEndOfPage'][] = array('WikihowHomepage::onArticleJustBeforeBodyClose');

// Have to add zzz to beginning of module to ensure it loads after other mw modules
// and properly overrides css without having to add !important with all the rules.
// A hack, for sure, but has to be done since the OutputPage alphabetically sorts
// all the modules before building a ss url.  Another alternative, if we want to spend the time,
// is to pull out specific styles for each mw module we are overriding and inject
// css into those modules. This approach, of course, is brittle and still will largely
// be influenced by the sort order
$wgResourceModules['zzz.mobile.wikihow.homepage.styles'] = [
	'styles' => [
		'homepage.less',
	],
	'position' => 'top',
	'localBasePath' => __DIR__ . '/less',
	'remoteExtPath' => 'wikihow/homepage/less',
	'targets' => ['mobile', 'desktop'],
];
$wgResourceModules['zzz.mobile.wikihow.homepage.scripts'] = [
	'scripts' => [
		'homepage.js',
	],
	'position' => 'top',
	'localBasePath' => __DIR__ . '/javascripts/wikihow',
	'remoteExtPath' => 'wikihow/homepage/javascripts/wikihow',
	'targets' => ['mobile', 'desktop'],
];

