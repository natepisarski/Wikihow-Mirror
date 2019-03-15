<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'VideoEmbedHelperTool',
	'author' => 'Wilson Restrepo',
	'description' => 'Tool for embedding youtube videos in a page',
);

$wgSpecialPages['VideoEmbedHelperTool'] = 'VideoEmbedHelperTool';
$wgAutoloadClasses['VideoEmbedHelperTool'] = __DIR__ . '/SpecialVideoEmbedHelperTool.php';
$wgExtensionMessagesFiles['VideoEmbedHelperTool'] = __DIR__ . '/VideoEmbedHelperTool.i18n.php';

$wgResourceModules['ext.wikihow.VideoEmbedHelperTool'] = [
    'localBasePath' => __DIR__ . '/',
    'scripts' => [ 'VideoEmbedHelperTool.js'],
	'targets' => [ 'desktop' ],
    'remoteExtPath' => 'wikihow/VideoEmbedHelperTool',
    'messages' => ['evht_addingvideo_summary'],
    'position' => 'top' ];


