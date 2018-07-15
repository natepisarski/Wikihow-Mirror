<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Interface for short phrase classifier',
    'author' => 'RJS Bhatia',
    'description'=> 'Page for posting jobs to the python classifier',
    ];
    
$wgSpecialPages['ClassifyTitles']='ClassifyTitles';
$wgAutoloadClasses['ClassifyTitles']=dirname(__FILE__).'/ClassifyTitles.body.php';
$wgResourceModules['ext.wikihow.ClassifyTitles'] = [
        'scripts' => [ 'classifytitles.js' ],
        'styles'  => [ 'classifytitles.css' ],
        'position' => 'top',
        'localBasePath' => dirname(__FILE__),
        'targets' => [ 'desktop' ]
];