<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminCustomMeta',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to upload/download lists of custom titles',
);

$wgSpecialPages['AdminCustomMeta'] = 'AdminCustomMeta';
$wgAutoloadClasses['AdminCustomMeta'] = __DIR__ . '/AdminCustomMeta.body.php';
$wgExtensionMessagesFiles['AdminCustomMetaAlias'] = __DIR__ . '/AdminCustomMeta.alias.php';

