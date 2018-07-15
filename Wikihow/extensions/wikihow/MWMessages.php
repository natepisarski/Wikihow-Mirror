<?php

if ( !defined( 'MEDIAWIKI' ) ) {
exit(1);
}

/**#@+
 * A simple extension that allows users to enter a title before creating a page. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'MWMessages.php',
	'author' => 'Travis Derouin',
	'description' => 'Maintain Mediawiki messages',
	'url' => 'http://www.wikihow.com/WikiHow:MWMessages-Extension',
);

$wgSpecialPages['MWMessages'] = 'MWMessages';
$wgAutoloadClasses['MWMessages'] = dirname( __FILE__ ) . '/MWMessages.body.php';

