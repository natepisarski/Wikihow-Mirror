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
	'name' => 'Articlestats',
	'author' => 'Travis Derouin',
	'description' => 'Basic dashboard that gives some summarized information on a page',
	'url' => 'http://www.wikihow.com/WikiHow:Articlestats-Extension',
);

$wgSpecialPages['Articlestats'] = 'Articlestats';
$wgExtensionMessagesFiles['Cite'] = dirname( __FILE__ ) . "/Articlestats.i18n.php";
$wgAutoloadClasses['Articlestats'] = dirname( __FILE__ ) . '/Articlestats.body.php';

$wgExtensionMessagesFiles['ArticlestatsAlias'] = dirname( __FILE__ ) . "/Articlestats.alias.php";
