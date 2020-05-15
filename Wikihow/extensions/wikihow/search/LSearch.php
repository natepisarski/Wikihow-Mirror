<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'LSearch',
	'author' => 'wikiHow',
	'description' => 'Customed search backend for wikiHow',
];

$wgExtensionMessagesFiles['LSearch'] = __DIR__ . '/LSearch.i18n.php';
$wgExtensionMessagesFiles['LSearchAlias'] = __DIR__ . '/LSearch.alias.php';

$wgSpecialPages['LSearch'] = 'LSearch';
$wgAutoloadClasses['LSearch'] = __DIR__ . '/LSearch.body.php';

$wgBogusQueries  = [
	"_vti_bin/owssvr.dll",
	"msoffice/cltreq.asp",
	"crossdomain.xml",
	"type in here",
	"ehow_feed.rss",
	"__utm.gif",
	"null",
	"_vpi.xml",
	"wikihow.gif",
	"",
	"sharetab_email.gif",
	"main page/favicon.ico",
	"sharetab_delicious.gif",
	"sharetab_digg.gif",
	"sharetab_facebook.png",
	"sharetab_blogger.gif",
	"sharetab_google.png",
	"cnw_logowikihow1_133.png",
	"acticon_create.gif",
	"acticon_edit.gif",
	"$1",
	"http:/amyru.h18.ru/images/cs.txt",
	"logo_creative_commons.gif",
	"acticon_discuss.gif",
	"sharetab_yahoo.png",
	"logo_mediawiki.png",
	"2547 1_3 0 20.xml",
	"extreme.xml",
	"_vti_inf.html",
	"_vti_bin/shtml.exe/_vti_rpc",
	"acticon_email.gif",
	"acticon_printable.gif",
	"opera6fixes.css",
	"opera7fixes.css",
	"khtmlfixes.css",
];

$wgCensoredWords = [
	"nigger"
];

$wgResourceModules['ext.wikihow.lsearch.desktop.styles'] = [
	'styles' => [ 'searchresults_desktop.css' ],
	'localBasePath' => __DIR__ . '/../../../skins/owl',
	'remoteExtPath' => 'skins/owl',
];

$wgResourceModules['ext.wikihow.lsearch.mobile.styles'] = [
	'styles' => ['searchresults_responsive.less'],
	'localBasePath' => __DIR__ . '/../../../skins/owl',
	'remoteExtPath' => 'skins/owl',
	'targets' => [ 'desktop', 'mobile' ],
];

$wgResourceModules['ext.wikihow.lsearch'] = [
	'scripts' => [ 'searchresults.js' ],
	'dependencies' => [ 'jquery' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'extensions/wikihow/search',
	'targets' => [ 'desktop', 'mobile' ]
];

$wgHooks['OutputPageAfterGetHeadLinksArray'][] = 'LSearch::onOutputPageAfterGetHeadLinksArray';
