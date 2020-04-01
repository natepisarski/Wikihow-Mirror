<?php
/**
 * support for the category code
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:CategoryHelper-Extension Documentation
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'CategoryHelper',
	'author' => 'Travis Derouin',
	'description' => 'helper functions for working with categories',
);

$wgSpecialPages['CategoryHelper'] = 'CategoryHelper';
$wgAutoloadClasses['CategoryHelper'] = __DIR__ . '/Categoryhelper.body.php';

$wgHooks['PageContentSaveComplete'][] = array( "CategoryHelper::onPageContentSaveComplete" );
