<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * Allows custom headers/footers to be added to user to user emails. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:FormatEmail Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'FormatEmail',
	'author' => 'Travis Derouin',
	'description' => 'Allows custom headers/footers to be added to user to user emails.',
	'url' => 'http://www.mediawiki.org/wiki/Extension:FormatEmail',
);

$wgHooks['EmailUser'][] = 'wfFormatEmail';

function wfFormatEmail (&$to, &$from, &$subject, &$text ) {
	global $wgUser; 
	$ul = $wgUser->getUserPage();
	// append the unsubscribe link.
	// NOTE: It'd be nice to obtain the token from a user ID, but we don't really have access to one.
	$link = UnsubscribeLink::newFromEmail( $to->address );
	$text = wfMsg('email_header') . $text . wfMsg('email_footer',$wgUser->getName(), $ul->getFullURL(), $link->getLink());
	return true;
}


?>
