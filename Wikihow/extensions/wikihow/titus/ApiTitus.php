<?php

// The basis for this code was taken from:
// https://www.mediawiki.org/wiki/API:Extensions

$wgExtensionCredits['api'][] = array(
  'path' => __FILE__,
  'name' => 'Titus API',
  'author' => 'Gershon Bialer (wikiHow)',
  'description' => 'An API extension to server up Titus data',
);

$wgAutoloadClasses['ApiTitus'] = __DIR__ . '/ApiTitus.body.php';
$wgAPIModules['titus'] = 'ApiTitus';
