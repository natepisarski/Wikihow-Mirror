<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
	
/**#@+
 * The wikiHow Image Page
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 * @author Jordan Small 
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgAutoloadClasses['WikihowImagePage'] = dirname( __FILE__ ) . '/WikihowImagePage.body.php';
$wgAutoloadClasses['WikihowImageHistoryList'] = dirname( __FILE__) . '/WikihowImagePage.body.php';

$wgHooks['ArticleFromTitle'][] = array('WikihowImagePage::onArticleFromTitle');
$wgHooks['ImagePageFileHistoryLine'][] = array('WikihowImageHistoryList::onImagePageFileHistoryLine');
