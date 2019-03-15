<?php
/*
 * Temporary class to insert all new NAB/atlas scores into nab table
 *
 * @file
 * @ingroup Extensions
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'AdminNabAtlasList',
	'author' => 'wikiHow',
	'url' => 'http://src.wikihow.com/',
	'descriptionmsg' => 'adminnabatlaslist-desc',
	'version' => '0.1',
);
$dir = __DIR__;

$wgSpecialPages['AdminNabAtlasList'] = 'SpecialAdminNabAtlasList';
$wgExtensionMessagesFiles['AdminNabAtlasList'] = "$dir/AdminNabAtlasList.i18n.php";

$wgAutoloadClasses['SpecialAdminNabAtlasList'] = "$dir/SpecialAdminNabAtlasList.php";
$wgAutoloadClasses['NabAtlasList'] = "$dir/NabAtlasList.php";


