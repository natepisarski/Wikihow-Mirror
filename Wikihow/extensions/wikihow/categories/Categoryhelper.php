<?php
/**
 * support for the category code
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Categoryhelper-Extension Documentation
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Categoryhelper',
	'author' => 'Travis Derouin',
	'description' => 'helper functions for working with categories',
);

$wgSpecialPages['Categoryhelper'] = 'Categoryhelper';
$wgAutoloadClasses['Categoryhelper'] = dirname( __FILE__ ) . '/Categoryhelper.body.php';

$wgHooks['PageContentSaveComplete'][] = array( "Categoryhelper::onPageContentSaveComplete" );
