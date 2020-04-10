<?php

if (!defined('MEDIAWIKI'))
	die();

$wgExtensionCredits['edithook'][] = array(
	'name' => 'EditMapper',
	'author' => 'Alberto',
	'description' => "Provide hooks to map article edits to different users",
);

// Make sure all required EditMapper subclasses are included
require_once("$IP/extensions/wikihow/AssistedTranslation/AssistedTranslation.php");

$wgAutoloadClasses['EditMapper\EditMapperHooks'] = __DIR__ . '/EditMapper.hooks.php';
$wgAutoloadClasses['EditMapper\EditMapper'] = __DIR__ . '/EditMapper.class.php';
$wgAutoloadClasses['EditMapper\PortalEditMapper'] = __DIR__ . '/PortalEditMapper.class.php';

$wgHooks['BeforePrepareContent'][] = 'EditMapper\EditMapperHooks::onBeforePrepareContent';
$wgHooks['PageContentSaveComplete'][] = 'EditMapper\EditMapperHooks::onPageContentSaveComplete';
