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
	'name' => 'SpecialPage',
	'url' => 'http://src.wikihow.com',
	'author' => 'Sam Gussman',
    'description' => 'controlled for sherlock data collection project.'
);

$wgSpecialPages['SherlockController'] = "SherlockController";
$wgAutoloadClasses['SherlockController'] =  __DIR__."/SpecialSherlock.body.php";
$wgAutoloadClasses['Sherlock'] = __DIR__."/Sherlock.php";
