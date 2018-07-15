<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Duplicate title checker',
    'author' => 'RJS Bhatia',
    'description'=> 'Interface for checking if a title exists on wikihow',
    ];

$wgSpecialPages['DupTitleChecker']='DupTitleChecker';
$wgAutoloadClasses['DupTitleChecker']=dirname(__FILE__).'/DupTitleChecker.body.php';
$wgAutoloadClasses['RankBiasedOverlap']=dirname( __FILE__).'/RankBiasedOverlap.php';
$wgResourceModules['ext.wikihow.DupTitleChecker'] = [
        'styles'  => [ 'DupTitleChecker.css' ],
        'position' => 'top',
        'localBasePath' => dirname(__FILE__),
        'targets' => [ 'desktop' ]
];
