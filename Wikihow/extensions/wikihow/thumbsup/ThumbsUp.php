<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['ThumbsUp'] = 'ThumbsUp';
$wgAutoloadClasses['ThumbsUp'] = dirname( __FILE__ ) . '/ThumbsUp.body.php';
$wgExtensionMessagesFiles['ThumbsUp'] = dirname(__FILE__) . '/ThumbsUp.i18n.php';


$wgLogTypes[]             = 'thumbsup';
$wgLogNames['thumbsup']   = 'thumbslogpage';
$wgLogHeaders['thumbsup'] = 'thumbspagetext';

/**
 * Thumbs Up feature debug flag -- always check-in as false and make a
 * local edit.
 */
define('THUMBSUP_DEBUG', false);
