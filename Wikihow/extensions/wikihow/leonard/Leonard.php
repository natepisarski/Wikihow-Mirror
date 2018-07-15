<?php                                                                           
if ( ! defined( 'MEDIAWIKI' ) )
  die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Leonard',
    'author' => 'Abhay Saswade',
    'description' => 'Suggest titles',
);

$wgSpecialPages['Leonard'] = 'Leonard';
$wgAutoloadClasses['Leonard'] = dirname(__FILE__) . '/Leonard.body.php';

