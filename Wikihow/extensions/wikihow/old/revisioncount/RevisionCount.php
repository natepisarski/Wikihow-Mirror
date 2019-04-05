<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Revision Count',
	'author' => 'Gershon Bialer',
	'description' => 'An extension that calculates article editing information',
);

$wgHooks['PageContentSaveComplete'][] = 'RevisionCount::onPageContentSaveComplete';
$wgAutoloadClasses['RevisionCount'] = __DIR__ . '/RevisionCount.class.php';
