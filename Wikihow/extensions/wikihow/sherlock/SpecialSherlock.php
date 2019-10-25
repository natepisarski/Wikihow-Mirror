<?php

/**
 * @package MediaWiki
 * @subpackage Extensions
 * @author Sam Gussman <sgussman@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Sherlock',
	'author' => 'Sam Gussman',
    'description' => 'Sherlock data collection project. Real search ddata is used in aggregate to improve our search results.'
);

$wgSpecialPages['SherlockController'] = "SherlockController";
$wgAutoloadClasses['SherlockController'] =  __DIR__."/SpecialSherlock.body.php";
$wgAutoloadClasses['Sherlock'] = __DIR__."/Sherlock.php";
