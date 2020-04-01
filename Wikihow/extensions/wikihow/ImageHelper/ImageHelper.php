<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ImageHelper',
    'author' => 'Bebeth Steudel',
    'description' => 'New functionality for the Image pages',
);

$wgAutoloadClasses['ImageHelper'] = __DIR__ . '/ImageHelper.body.php';
$wgExtensionMessagesFiles['ImageHelper'] = __DIR__ . "/ImageHelper.i18n.php";
