<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Interface for managing qualifications for Turk Workers',
    'author' => 'RJS Bhatia',
    'description'=> 'Page for assigning, revoking qualifications. Sending messaged to workers',
    ];

$wgSpecialPages['TQualManager'] = 'TQualManager';
$wgAutoloadClasses['TQualManager'] = dirname(__FILE__).'/TQualManager.body.php';
$wgResourceModules['ext.wikihow.TQualManager'] = [
        'scripts' => [ 'resources/tqualmanager.js' ],
        'styles'  => [ 'resources/tqualmanager.css' ],
        'position' => 'top',
        'localBasePath' => dirname(__FILE__),
        'targets' => [ 'desktop' ]
];
