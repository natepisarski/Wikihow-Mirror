<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpecialPages['RCTestGrader'] = 'RCTestGrader';
$wgAutoloadClasses['RCTestGrader'] = dirname( __FILE__ ) . '/RCTestGrader.body.php';
$wgExtensionMessagesFiles['RCTestGrader'] = dirname(__FILE__) . '/RCTestGrader.i18n.php';
