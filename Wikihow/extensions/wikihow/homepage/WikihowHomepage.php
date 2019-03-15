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
$wgExtensionMessagesFiles['WikihowMobileHomepage'] = __DIR__ . '/WikihowMobileHomepage.i18n.php';

$wgHooks['ArticleFromTitle'][] = array('WikihowHomepage::onArticleFromTitle');
$wgHooks['ArticleJustBeforeBodyClose'][] = array('WikihowHomepage::onArticleJustBeforeBodyClose');
$wgHooks['MobileEndOfPage'][] = array('WikihowHomepage::onArticleJustBeforeBodyClose');

