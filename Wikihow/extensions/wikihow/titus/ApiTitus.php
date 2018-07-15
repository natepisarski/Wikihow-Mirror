<?php

// The basis for this code was taken from:
// https://www.mediawiki.org/wiki/API:Extensions

$wgExtensionCredits['api'][] = array(
  'path' => __FILE__,
  'name' => 'Titus API',
  'description' => 'An API extension to server up Titus data',
  'descriptionmsg' => '',
  'version' => 1,
  'author' => 'Gershon Bialer'
);

$wgAutoloadClasses['ApiTitus'] = dirname( __FILE__ ) . '/ApiTitus.body.php';

$wgAPIModules['titus'] = 'ApiTitus';


