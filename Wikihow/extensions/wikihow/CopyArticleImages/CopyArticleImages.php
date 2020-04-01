<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'CopyArticleImages',
	'author' => 'Trevor Parscal <trevorparscal@gmail.com>',
	'description' => 'Copy images from articles on the main site to identical pages on intl'
];

$wgSpecialPages['CopyArticleImages'] = 'SpecialCopyArticleImages';
$wgAutoloadClasses['SpecialCopyArticleImages'] = __DIR__ . '/SpecialCopyArticleImages.php';
$wgAutoloadClasses['CopyArticleImages'] = __DIR__ . '/CopyArticleImages.body.php';

$wgAutoloadClasses['ImageTransfer'] = __DIR__  . '/ImageTransfer.class.php';

$wgAutoloadClasses['ApiQueryImageTransfers'] =  __DIR__ . '/api/ApiQueryImageTransfers.php';
$wgAutoloadClasses['ApiAddImageTransfers'] =  __DIR__ . '/api/ApiAddImageTransfers.php';

$wgExtensionMessagesFiles['CopyArticleImages'] = __DIR__ . '/CopyArticleImages.i18n.php';
$wgExtensionMessagesFiles['CopyArticleImagesAliases'] = __DIR__ . '/CopyArticleImages.alias.php';

$wgAPIListModules['imagetransfers'] = 'ApiQueryImageTransfers';
$wgAPIModules['addimagetransfers'] = 'ApiAddImageTransfers';

$wgResourceModules['ext.wikihow.copyArticleImages'] = [
	'styles' => [
		'styles/main.less',
	],
	'scripts' => [
		'scripts/main.js',
		'scripts/PageListInputComponent.js',
		'scripts/LanguageSelectorComponent.js',
		'scripts/SubmitButtonComponent.js',
		'scripts/FormComponent.js',
		'scripts/BatchListComponent.js',
		'scripts/ListComponent.js',
		'scripts/MainComponent.js',
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/CopyArticleImages',
	'position' => 'top',
	'messages' => [
		'cai-pagelistinput-instructions',
		'cai-pagelistinput-placeholder',
		'cai-languageselector-instructions',
		'cai-languageselector-all',
		'cai-languageselector-none',
		'cai-submitbutton-instructions',
		'cai-submitbutton-label',
		'cai-submitbutton-confirmation',
		'cai-list-title',
		'cai-list-itemnotfound',
		'cai-list-loading',
		'cai-list-refresh',
		'cai-list-empty',
		'cai-list-newitems',
		'cai-list-from',
		'cai-list-to',
		'cai-list-creator',
		'cai-batchlist-title',
	],
	'targets' => [ 'desktop', 'mobile' ],
	'dependencies' => [
		'mediawiki.user',
		'wikihow.render',
		'mediawiki.cookie',
		'ext.wikihow.common_top',
		'ext.wikihow.common_bottom'
	]
];
