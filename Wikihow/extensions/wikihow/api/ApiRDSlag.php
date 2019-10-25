<?php

$wgExtensionCredits['api'][] = array(
    'path' => __FILE__,
    'name' => 'Lag checking API',
    'description' => 'An API extension to display the lag times of the WH RDS db',
    'author' => 'Reuben',
);

$wgAutoloadClasses['ApiRDSlag'] = __DIR__ . '/ApiRDSlag.body.php';
$wgAPIModules['rdslag'] = 'ApiRDSlag';
