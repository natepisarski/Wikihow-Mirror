<?php

if ( ! defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RevertTool',
	'author' => 'Gershon Bialer (wikiHow)',
	'description' => 'Tool to revert edits by certain bots or users'
);

$wgSpecialPages['RevertTool'] = 'RevertTool';
$wgAutoloadClasses['RevertTool'] = __DIR__ . '/RevertTool.body.php';
