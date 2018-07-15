<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 * An extension that allows users to rate articles.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.mediawiki.org/wiki/InternalLinksPopup_Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'PopBox',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way of adding new entries to the Spam Blacklist from diff pages',
	'url' => 'http://www.mediawiki.org/wiki/InternalLinksPopup_Extension',
);

$wgExtensionMessagesFiles['PopBox'] = dirname(__FILE__) . '/PopBox.i18n.php';

$wgAutoloadClasses['PopBox'] = dirname( __FILE__ ) . '/PopBox.body.php';

$wgResourceModules['ext.wikihow.popbox'] = [
    'styles' => ['popbox.css'],
    'scripts' => ['PopBox.js'],
    'targets' => array( 'desktop' ),
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/popbox',
	'messages' => [
		'popbox_noelement', 'popbox_noresults', 'popbox_related_articles',
		'popbox_revise', 'popbox_nothanks', 'popbox_editdetails',
		'popbox_search', 'popbox_no_text_selected', 
	],
];
