<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'EditorInvoiceNotifier',
	'author' => 'George Bahij',
	'description' => 'Send e-mails to editors when they write words'
];

$wgSpecialPages['EditorInvoiceNotifier'] = 'EditorInvoiceNotifier';
$wgAutoloadClasses['EditorInvoiceNotifier'] = __DIR__ . '/EditorInvoiceNotifier.body.php';

