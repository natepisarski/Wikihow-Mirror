<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Alien',
    'author' => 'Reuben <reuben@wikihow.com>',
    'description' => 'Server-side helper for front end cache probing',
);

$wgSpecialPages['Alien'] = 'Alien';
$wgAutoloadClasses['Alien'] = __DIR__ . '/Alien.body.php';
