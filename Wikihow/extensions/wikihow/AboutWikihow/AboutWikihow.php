<?php

$wgAutoloadClasses['AboutWikihow'] = __DIR__ . '/AboutWikihow.class.php';
$wgMessagesDirs['AboutWikihow'] = __DIR__ . '/i18n/';

$wgHooks['WikihowTemplateShowTopLinksSidebar'][] = ['AboutWikihow::onWikihowTemplateShowTopLinksSidebar'];

$wgResourceModules['ext.wikihow.press_sidebox'] = [
	'styles' => [ 'press_sidebox.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/AboutWikihow/assets',
	'targets' => [ 'desktop' ],
	'position' => 'top'
];