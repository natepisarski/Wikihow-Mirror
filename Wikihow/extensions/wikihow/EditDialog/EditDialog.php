<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['extension'][] = array(
    'name' => 'EditDialog',
    'author' => 'Trevor Parscal <trevor@wikihow.com>',
    'description' => 'Page editing in a dialog on the article page for anons.',
);

$wgExtensionMessagesFiles['EditDialog'] = __DIR__ . '/EditDialog.i18n.php';

$wgResourceModules[ 'ext.wikihow.editDialog' ] = [
	'scripts' => [ 'editdialog.js' ],
	'styles' => [ 'editdialog.less' ],
	'dependencies' => [
		'oojs-ui-core',
		'oojs-ui-widgets',
		'oojs-ui-windows',
		'oojs-ui-toolbars',
		'oojs-ui.styles.icons-editing-advanced',
		'oojs-ui.styles.icons-editing-citation',
		'oojs-ui.styles.icons-editing-core',
		'oojs-ui.styles.icons-editing-list',
		'oojs-ui.styles.icons-editing-styling',
		'oojs-ui.styles.icons-interactions',
		'oojs-ui.styles.icons-wikimedia',
		'jquery.textSelection',
		'ext.wikihow.diff_styles'
	],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/EditDialog',
	'position' => 'bottom',
	'targets' => [ 'desktop' ],
	'messages' => [
		'editing',
		'summary',
		'cancel',
		'savechanges',
		'showpreview',
		'showdiff',
		'minoredit',
		'tooltip-minoredit',
		'watchthis',
		'tooltip-watch',
		'bold_sample',
		'bold_tip',
		'italic_sample',
		'italic_tip',
		'link_sample',
		'link_tip',
		'extlink_sample',
		'extlink_tip',
		'termsofuse',
		'copyrightpage',
		'copyrightwarning2',
		'editpage-tos-summary',
		'anoneditwarning',
		'editnotice-0',
		'warnings',
		'previewnote',
		'fancycaptcha-captcha',
		'fancycaptcha-addurl',
		'fancycaptcha-reload-text',
		'fancycaptcha-imgcaptcha-ph',
		'editdialog-error-editconflict',
		'editdialog-error-publish',
		'editdialog-error-preview',
		'editdialog-error-diff',
	]
];
