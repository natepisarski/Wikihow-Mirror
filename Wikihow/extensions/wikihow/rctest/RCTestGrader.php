<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['RCTestGrader'] = 'RCTestGrader';
$wgAutoloadClasses['RCTestGrader'] = __DIR__ . '/RCTestGrader.body.php';
$wgExtensionMessagesFiles['RCTestGrader'] = __DIR__ . '/RCTestGrader.i18n.php';
