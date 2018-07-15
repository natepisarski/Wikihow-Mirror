<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Revision Count',
	'author' => 'Gershon Bialer',
	'description' => 'An extension that calculates article editing information',
);

$wgHooks['ArticleSaveComplete'][] = 'RevisionCount::onArticleSaveComplete';
$wgAutoloadClasses['RevisionCount'] = dirname(__FILE__) . '/RevisionCount.class.php';

