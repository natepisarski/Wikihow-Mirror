<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ImportXML',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Importing exporting XML',
);

$wgSpecialPages['ImportXML'] = 'ImportXML';
$wgAutoloadClasses['ImportXML'] = __DIR__ . '/ImportXML.body.php';
$wgSpecialPages['ExportXML'] = 'ExportXML';
$wgAutoloadClasses['ExportXML'] = __DIR__ . '/ImportXML.body.php';
