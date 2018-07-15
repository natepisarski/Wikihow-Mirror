<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['CatSearch'] = 'CatSearch';
$wgAutoloadClasses['CatSearch'] = dirname( __FILE__ ) . '/CatSearch.body.php';
$wgExtensionMessagesFiles['CatSearch'] = dirname(__FILE__) . '/CatSearch.i18n.php';



/**
 * CatSearch feature debug flag -- always check-in as false and make a
 * local edit.
 */
define('CATSEARCH_DEBUG', false);
