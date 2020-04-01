<?php

if ( !defined( 'MEDIAWIKI' ) ) exit(1);

$wgAutoloadClasses['TechLayout'] = __DIR__ . '/TechLayout.class.php';



$wgHooks['ShowSideBar'][] = ['TechLayout::removeSideBarCallback'];
$wgHooks['DesktopTopStyles'][] = ['TechLayout::addCSS'];

$wgResourceModules['ext.wikihow.techlayout'] = array(
	'scripts' => 'techlayout.js',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/techlayout',
	'position' => 'top',
	'targets' => array( 'desktop' )
);
