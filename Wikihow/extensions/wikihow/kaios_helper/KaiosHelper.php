<?php

$wgAutoloadClasses['KaiosHelper'] = __DIR__ . '/KaiosHelper.class.php';

$wgResourceModules['ext.wikihow.kaios_helper'] = array(
	'scripts' => array(
		'kaios_helper.js'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/kaios_helper',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'dependencies' => array('mediawiki.page.ready')
);

$wgHooks['TitleSquidURLs'][] = array('KaiosHelper::onTitleSquidURLsPurgeVariants');
$wgHooks['BeforePageDisplay'][] = array('KaiosHelper::onBeforePageDisplay');
