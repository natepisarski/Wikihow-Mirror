<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['WikihowToc'] = __DIR__ . '/WikihowToc.class.php';
$wgMessagesDirs['WikihowToc'] = __DIR__ . '/i18n';

$wgHooks['BeforePageDisplay'][] = ['WikihowToc::onBeforePageDisplay'];

$wgResourceModules['ext.wikihow.mobile_toc'] = [
	'styles' => ['mobile_toc.less'],
	'localBasePath' => __DIR__ . '/resources/',
	'remoteExtPath' => 'wikihow/wikihowtoc/resources',
	'position' => 'bottom',
	'targets' => ['mobile'],
];