<?php

$wgSpecialPages['CreateEmptyIntlArticle'] = 'CreateEmptyIntlArticle';

$wgAutoloadClasses['CreateEmptyIntlArticle'] = __DIR__ . '/CreateEmptyIntlArticle.body.php';
$wgMessagesDirs['CreateEmptyIntlArticle'] = __DIR__ . '/i18n';

$wgResourceModules['ext.wikihow.createemptyintlarticle'] = [
	'scripts' => [ 'createemptyintlarticle.js' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/CreateEmptyIntlArticle/resources',
	'targets' => [ 'desktop' ],
	'position' => 'top'
];
