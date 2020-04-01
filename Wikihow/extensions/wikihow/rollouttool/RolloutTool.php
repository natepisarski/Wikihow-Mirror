<?php
if ( ! defined( 'MEDIAWIKI' ) )
    die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'RolloutTool',
    'author' => 'Gershon Bialer',
    'description' => 'Shows when things are going to rollout',
);

$wgSpecialPages['RolloutTool'] = 'RolloutTool';
$wgAutoloadClasses['RolloutTool'] = __DIR__ . '/RolloutTool.body.php';
