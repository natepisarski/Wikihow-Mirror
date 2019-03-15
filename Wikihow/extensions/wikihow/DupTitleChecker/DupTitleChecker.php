<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Duplicate title checker',
    'author' => 'RJS Bhatia',
    'description'=> 'Interface for checking if a title exists on wikihow',
    ];

$wgSpecialPages['DupTitleChecker']='DupTitleChecker';
$wgAutoloadClasses['DupTitleChecker']=__DIR__.'/DupTitleChecker.body.php';
$wgAutoloadClasses['RankBiasedOverlap']=__DIR__.'/RankBiasedOverlap.php';
$wgResourceModules['ext.wikihow.DupTitleChecker'] = [
        'styles'  => [ 'DupTitleChecker.css' ],
        'position' => 'top',
        'localBasePath' => __DIR__,
        'targets' => [ 'desktop' ]
];
