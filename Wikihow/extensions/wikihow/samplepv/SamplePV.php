<?php

if ( ! defined( 'MEDIAWIKI' ) )
  die();

    $wgExtensionCredits['specialpage'][] = array(
      'name' => 'Sample Page Views',
      'author' => 'Gershon Bialer',
      'description' => 'Get the Page Views for Samples'
    );

    $wgSpecialPages['SamplePV'] = 'SamplePV';
	$wgAutoloadClasses['SamplePV'] = __DIR__ . '/SamplePV.body.php';

