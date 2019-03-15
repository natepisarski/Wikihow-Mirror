<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['CatSearch'] = 'CatSearch';
$wgAutoloadClasses['CatSearch'] = __DIR__ . '/CatSearch.body.php';
$wgExtensionMessagesFiles['CatSearch'] = __DIR__ . '/CatSearch.i18n.php';



/**
 * CatSearch feature debug flag -- always check-in as false and make a
 * local edit.
 */
define('CATSEARCH_DEBUG', false);
