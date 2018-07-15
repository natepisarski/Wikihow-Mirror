<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.mediawiki.org/wiki/SpamDiffTool_Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgSpamBlacklistArticle = "Spam-Blacklist";

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'SpamDiffTool',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way of adding new entries to the Spam Blacklist from diff pages',
);
$wgExtensionMessagesFiles['SpamDiffTool'] = dirname(__FILE__) . '/SpamDiffTool.i18n.php';

$wgSpecialPages['SpamDiffTool'] = 'SpamDiffTool';
$wgAutoloadClasses['SpamDiffTool'] = dirname( __FILE__ ) . '/SpamDiffTool.body.php';

