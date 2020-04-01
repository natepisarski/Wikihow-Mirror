<?php
if ( ! defined( 'MEDIAWIKI' ) )
  die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'CommunityExpert',
    'author' => 'Gershon Bialer',
    'description' => 'Find users with expertise on a given title',
);

$wgSpecialPages['CommunityExpert'] = 'CommunityExpert';
$wgAutoloadClasses['CommunityExpert'] = __DIR__ . '/CommunityExpert.body.php';

