<?php

$wgExtensionCredits['api'][] = array(
    'path' => __FILE__,
    'name' => 'Lag checking API',
    'description' => 'An API extension to display the lag times of the wH RDS db',
    'descriptionmsg' => 'sampleapiextension-desc',
    'version' => 1,
    'author' => 'Reuben Smith',
    'url' => 'https://www.mediawiki.org/wiki/API:Extensions',
);

$wgAutoloadClasses['ApiRDSlag'] =
    dirname(__FILE__) . '/ApiRDSlag.body.php';
$wgAPIModules['rdslag'] = 'ApiRDSlag';
