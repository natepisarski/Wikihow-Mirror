<?php
if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name'=>'GDPR',
    'author'=>'Titus',
    'description'=>'code to support GDPR',
);
$wgSpecialPages['GDPR'] = 'GDPR';
$wgAutoloadClasses['GDPR'] = dirname(__FILE__) . '/GDPR.body.php';
$wgExtensionMessagesFiles['GDPR'] = dirname(__FILE__) . '/GDPR.i18n.php';

$wgResourceModules['ext.wikihow.GDPR'] = array(
    'scripts' => array( 'gdpr.js' ),
    'localBasePath' => dirname(__FILE__) . '/',
    'remoteExtPath' => 'wikihow/GDPR',
    'position' => 'top',
    'targets' => array( 'desktop', 'mobile' )
);
