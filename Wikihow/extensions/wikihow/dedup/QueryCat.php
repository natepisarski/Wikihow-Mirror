<?php

// This extension was disabled by Alberto on September 1, 2016 - Changeset: 50cddec

if ( ! defined( 'MEDIAWIKI' ) )
  die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'QueryCat',
    'author' => 'Gershon Bialer',
    'description' => 'Find the categories on queries',
);

$wgSpecialPages['QueryCat'] = 'QueryCat';
$wgAutoloadClasses['QueryCat'] = __DIR__ . '/QueryCat.body.php';

