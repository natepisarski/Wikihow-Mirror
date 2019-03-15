<?php

if ( ! defined( 'MEDIAWIKI' ) )
  die();

  $wgExtensionCredits['specialpage'][] = array(
		  'name' => 'NAB Prioritizer',
		  'author' => 'Gershon Bialer',
		  'description' => 'Shows NAB Priorities for deletion output',
		  );

$wgSpecialPages['NABPrioritizer'] = 'NABPrioritizer';
$wgAutoloadClasses['NABPrioritizer'] = __DIR__ . '/NABPrioritizer.body.php';

