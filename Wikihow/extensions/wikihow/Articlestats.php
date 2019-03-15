<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

/**#@+
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:RateArticle-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ArticleStats',
	'author' => 'Travis Derouin',
	'description' => 'Basic dashboard that gives some summarized information on a page',
	'url' => 'http://www.wikihow.com/WikiHow:ArticleStats-Extension',
);

$wgSpecialPages['ArticleStats'] = 'ArticleStats';
$wgExtensionMessagesFiles['Cite'] = __DIR__ . '/Articlestats.i18n.php';
$wgAutoloadClasses['ArticleStats'] = __DIR__ . '/Articlestats.body.php';

$wgExtensionMessagesFiles['ArticleStatsAlias'] = __DIR__ . '/Articlestats.alias.php';
