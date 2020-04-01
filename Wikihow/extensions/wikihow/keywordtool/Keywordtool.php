<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Interface for keyword tool api',
    'author' => 'RJS Bhatia',
    'description'=> 'Page for posting jobs to the python script that talks to keywordtool',
    ];

$wgSpecialPages['Keywordtool'] = 'Keywordtool';
$wgAutoloadClasses['Keywordtool'] = __DIR__.'/Keywordtool.body.php';
$wgResourceModules['ext.wikihow.Keywordtool'] = [
        'scripts' => [ 'keyword_tool.js' ],
        'styles'  => [ 'keyword_tool.css' ],
        'position' => 'top',
        'localBasePath' => __DIR__,
        'targets' => [ 'desktop' ]
];
