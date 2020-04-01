<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Interface for short phrase classifier',
    'author' => 'RJS Bhatia',
    'description'=> 'Page for posting jobs to the python classifier',
    ];

$wgSpecialPages['ClassifyTitles']='ClassifyTitles';
$wgAutoloadClasses['ClassifyTitles']=__DIR__.'/ClassifyTitles.body.php';
$wgResourceModules['ext.wikihow.ClassifyTitles'] = [
        'scripts' => [ 'classifytitles.js' ],
        'styles'  => [ 'classifytitles.css' ],
        'position' => 'top',
        'localBasePath' => __DIR__,
        'targets' => [ 'desktop' ]
];
