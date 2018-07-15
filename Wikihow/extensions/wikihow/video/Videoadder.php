<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();
	
/**#@+
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Videoadder-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Videoadder',
	'author' => 'Travis <travis@wikihow.com>',
);

$dir = __DIR__ . '/';
$wgSpecialPages['Videoadder'] = 'Videoadder';
$wgAutoloadClasses['Videoadder'] = __DIR__ . '/Videoadder.body.php';
$wgExtensionMessagesFiles['Videoadder'] = $dir . 'Videoadder.i18n.php';

$wgGroupPermissions['sysop']['videoadder'] = true;
$wgGroupPermissions['newarticlepatrol']['videoadder'] = true;

$wgResourceModules['ext.wikihow.videoadder'] = $wgResourceModulesDesktopBoiler + [
       'scripts' => [ 'video/videoadder.js', 'video/cookie.js' ],
	   'messages' => [ 'va_congrats', 'va_check' ] ];
$wgResourceModules['ext.wikihow.videoadder_styles'] = $wgResourceModulesDesktopBoiler + [
       'styles' => [ 'video/videoadder.css' ] ];

/* description of videoadder table, since it may seem convoluted

va_id               	The id of the row, simple.
va_page             	The page in question.
va_page_touched     	The page_touched column of the page in question from the page table.
va_inuse            	1 = a user is adding a video right now to the article, prevents multiple users from looking at same video
va_skipped_accepted 	NULL = hasn't been looked at, 2 there were no results don't show page again, 1 accepted, 0 skipped
va_template_ns     		we init this to 10 if the page already has a video, or NULL if the page has no video 
va_src              	the source of the video considered when skipped, for now 'youtube'
va_vid_id           	the id of the video that was shown, but skipped. we'll use this later on when we want to re-skip this video
va_user             	the user id who accepted or rejected the video, for stats purproses
va_user_text        	the user name who accepted or rejected the video, for stats purproses
va_timestamp        	when the video was accepted or rejected (no change if just skipped by user)	
va_page_counter     	the page_counter column of the page in question from the page table

*/
