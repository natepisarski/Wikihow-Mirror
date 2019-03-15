<?php

if (!defined('MEDIAWIKI')) die();

/**#@+
 * Display a google form/spreadsheet which people can use to sign up.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Bloggers-Extension Documentation
 *
 * @author Reuben Smith <reuben@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'Bloggers',
	'author' => 'Reuben Smith',
	'description' => 'Display a Google form for bloggers',
	'url' => 'http://www.wikihow.com/WikiHow:Bloggers-Extension',
);

$wgSpecialPages['Bloggers'] = 'Bloggers';
$wgAutoloadClasses['Bloggers'] = __DIR__ . '/Bloggers.body.php';
