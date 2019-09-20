<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['CatSearch'] = 'CatSearch';
$wgAutoloadClasses['CatSearch'] = __DIR__ . '/CatSearch.body.php';
$wgExtensionMessagesFiles['CatSearch'] = __DIR__ . '/CatSearch.i18n.php';
