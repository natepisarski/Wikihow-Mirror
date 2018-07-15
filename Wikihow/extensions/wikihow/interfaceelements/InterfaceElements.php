<?php

$wgAutoloadClasses['InterfaceElements'] = __DIR__ . '/InterfaceElements.body.php';

$wgResourceModules['ext.wikihow.tips_bubble'] = array(
    'styles' => array('tipsbubble.css'),
    'scripts' => array('tipsbubble.js'),
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/interfaceelements',
    'position' => 'top',
    'targets' => array('desktop', 'mobile'),
    'dependencies' => array('ext.wikihow.common_top'),
);

$wgResourceModules['common.mousetrap'] = array(
    'scripts' => 'mousetrap.min.js',
    'localBasePath' => __DIR__ . '/../common/',
    'remoteExtPath' => 'wikihow/common',
    'messages' => array(),
    'position' => 'top',
    'targets' => array( 'mobile', 'desktop' ),
    'dependencies' => array('ext.wikihow.common_top')
);
