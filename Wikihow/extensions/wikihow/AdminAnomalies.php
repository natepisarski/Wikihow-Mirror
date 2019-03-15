<?php

if ( !defined('MEDIAWIKI') ) exit(1);

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'AdminAnomalies',
    'author' => 'Reuben Smith',
    'description' => 'Small admin tool to delete or rename pages with weird URLs',
);

$wgSpecialPages['AdminAnomalies'] = 'AdminAnomalies';
$wgAutoloadClasses['AdminAnomalies'] = __DIR__ . '/AdminAnomalies.body.php';
