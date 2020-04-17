<?php

$wgAutoloadClasses['ArticleTiles'] = __DIR__ . '/ArticleTiles.class.php';
$wgExtensionMessagesFiles['ArticleTilesMagic'] = __DIR__ . '/ArticleTiles.i18n.magic.php';

$wgHooks['ParserFirstCallInit'][] = ['ArticleTiles::onParserFirstCallInit'];

$wgResourceModules['ext.wikihow.article_tiles'] = [
	'styles' => [ 'article_tiles.less' ],
	'localBasePath' => __DIR__.'/resources',
	'remoteExtPath' => 'wikihow/ArticleTiles/resources',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'top'
];
