<?php
if ( ! defined( 'MEDIAWIKI' ) )
    die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'RolloutTool',
    'author' => 'Gershon Bialer',
    'description' => 'Shows when things are going to rollout',
);
                                                                                                                                                                                                         
$wgSpecialPages['RolloutTool'] = 'RolloutTool';
$wgAutoloadClasses['RolloutTool'] = dirname(__FILE__) . '/RolloutTool.body.php';
