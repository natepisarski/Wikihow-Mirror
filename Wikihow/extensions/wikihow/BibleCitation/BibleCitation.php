<?php

$wgAutoloadClasses['BibleCitation'] = __DIR__ . '/BibleCitation.class.php';
$wgMessagesDirs['BibleCitation'] = __DIR__ . '/i18n/';

$wgHooks['BeforePageDisplay'][] = ['BibleCitation::onBeforePageDisplay'];
$wgHooks['ProcessArticleHTMLAfter'][] = ['BibleCitation::addWidget'];
$wgHooks['MobileProcessArticleHTMLAfter'][] = ['BibleCitation::addWidget'];

$wgResourceModules['ext.wikihow.bible_citation.scripts'] = [
	'scripts' => [ 'bible_citation.js' ],
	'messages' => [
		'bible_citation_complete',
		'bible_citation_error',
		'bible_citation_error_edition',
		'bible_citation_copied'
	],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/BibleCitation/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'bottom'
];

$wgResourceModules['ext.wikihow.bible_citation.styles'] = [
	'styles' => [ 'bible_citation.less' ],
	'localBasePath' => __DIR__.'/assets',
	'remoteExtPath' => 'wikihow/BibleCitation/assets',
	'targets' => [ 'desktop', 'mobile' ],
	'position' => 'top'
];
