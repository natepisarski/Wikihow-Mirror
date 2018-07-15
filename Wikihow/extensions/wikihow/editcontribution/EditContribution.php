<?php

if ( ! defined( 'MEDIAWIKI' ) )
  die();

  $wgExtensionCredits['specialpage'][] = array(
          'name' => 'EditContribution',
          'author' => 'Gershon Bialer',
          'description' => 'A tool to see what users have contributed to an article',
          );

$wgSpecialPages['EditContribution'] = 'EditContribution';                                                                                                                                           
$wgAutoloadClasses['EditContribution'] = dirname(__FILE__) . '/EditContribution.body.php';

