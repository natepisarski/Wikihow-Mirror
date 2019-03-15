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
$wgAutoloadClasses['GDPR'] = __DIR__ . '/GDPR.body.php';
$wgExtensionMessagesFiles['GDPR'] = __DIR__ . '/GDPR.i18n.php';

$wgResourceModules['ext.wikihow.GDPR'] = array(
    'scripts' => array( 'gdpr.js' ),
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/GDPR',
    'position' => 'top',
    'targets' => array( 'desktop', 'mobile' )
);
