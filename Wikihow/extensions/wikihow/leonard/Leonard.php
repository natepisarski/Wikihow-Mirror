<?php
if ( ! defined( 'MEDIAWIKI' ) )
  die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Leonard',
    'author' => 'Abhay Saswade',
    'description' => 'Suggest titles',
);

$wgSpecialPages['Leonard'] = 'Leonard';
$wgAutoloadClasses['Leonard'] = __DIR__ . '/Leonard.body.php';

