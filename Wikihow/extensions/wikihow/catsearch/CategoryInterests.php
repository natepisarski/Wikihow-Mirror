<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['CategoryInterests'] = 'CategoryInterests';
$wgAutoloadClasses['CategoryInterests'] = __DIR__ . '/CategoryInterests.body.php';
$wgExtensionMessagesFiles['CategoryInterests'] = __DIR__ . '/CategoryInterests.i18n.php';

$wgSpecialPages['CategoryExpertise'] = 'CategoryExpertise';
$wgAutoloadClasses['CategoryExpertise'] = __DIR__ . '/CategoryInterests.body.php';
$wgExtensionMessagesFiles['CategoryExpertise'] = __DIR__ . '/CategoryInterests.i18n.php';
