<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Importvideo',
	'author' => 'Travis <travis@wikihow.com>',
	'description' => 'Provides a way to search and \'import\' videos  from various sources (Youtube, 5min.com, Howcast, Videojug, WonderHowTo) into the wiki',
);

$wgSpecialPages['Importvideo'] = 'Importvideo';
$wgSpecialPages['ImportvideoPopup'] = 'ImportvideoPopup';
$wgSpecialPages['Previewvideo'] = 'Previewvideo';
$wgSpecialPages['Newvideoboard'] = 'Newvideoboard';

# Internationalisation file
$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Importvideo'] = $dir . 'Importvideo.i18n.php';
$wgExtensionMessagesFiles['ImportvideoAlias'] = $dir . 'Importvideo.alias.php';

$wgAutoloadClasses['Importvideo']		= $dir . 'Importvideo.body.php';
$wgAutoloadClasses['ImportvideoPopup']	= $dir . 'Importvideo.body.php';
$wgAutoloadClasses['Previewvideo']		= $dir . 'Importvideo.body.php';
$wgAutoloadClasses['VideoPage']			= $dir . 'VideoPage.class.php';
$wgAutoloadClasses['Newvideoboard']		= $dir . 'Importvideo.body.php';

$wgAutoloadClasses['ImportvideoYoutube']		= $dir . 'Importvideo.Youtube.class.php';
$wgAutoloadClasses['ImportvideoHowcast']		= $dir . 'Importvideo.Howcast.class.php';

define('NS_VIDEO' , 24);
define('NS_VIDEO_TALK' , 25);
define('NS_VIDEO_COMMENTS' , 26);
define('NS_VIDEO_COMMENTS_TALK' , 27);
$wgExtraNamespaces[NS_VIDEO] = "Video";
$wgExtraNamespaces[NS_VIDEO_TALK] = "Video_talk";
$wgExtraNamespaces[NS_VIDEO_COMMENTS] = "VideoComments";
$wgExtraNamespaces[NS_VIDEO_COMMENTS_TALK] = "VideoComments_talk";

$wgImportVideoSources = array( 'youtube' );
$wgImportVideoBadUsers = array("expertvillage", "ehow", "videojug", "howcast");

$wgLogTypes[]               = 'vidsfornew';
$wgLogNames['vidsfornew']   = 'vidsfornew';
$wgLogHeaders['vidsfornew'] = 'vidsfornewtext';
$wgLogActions['vidsfornew/added'] = 'vidsfornew_logsummary';

$wgHowcastAPIKey = WH_HOWCAST_API_KEY;

$wgResourceModules['ext.wikihow.ImportVideo'] = [
    'localBasePath' => __DIR__,
    'targets' => [ 'desktop' ],
    'remoteExtPath' => 'wikihow',
	'styles' => [ 'importvideo.css' ],
	'scripts' => [ 'importvideo.js' ],
	'dependencies' => [ 'ext.wikihow.common_top', 'jquery.ui.dialog' ],
    'position' => 'top' ];

