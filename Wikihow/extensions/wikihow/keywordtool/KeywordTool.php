<?php

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = [
    'name' => 'Interface for keyword tool api',
    'author' => 'RJS Bhatia',
    'description'=> 'Page for posting jobs to the python script that talks to keywordtool',
];

$wgSpecialPages['KeywordTool'] = 'KeywordTool';
$wgAutoloadClasses['KeywordTool'] = __DIR__.'/KeywordTool.body.php';
$wgResourceModules['ext.wikihow.keywordtool'] = [
	'scripts' => [ 'keyword_tool.js' ],
	'styles'  => [ 'keyword_tool.css' ],
	'position' => 'top',
	'localBasePath' => __DIR__,
	'targets' => [ 'desktop' ]
];
