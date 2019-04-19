<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgSpecialPages['AdminExpertNameChange'] = 'AdminExpertNameChange';
$wgAutoloadClasses['AdminExpertNameChange'] = __DIR__ . '/AdminExpertNameChange.body.php';
$wgResourceModules['ext.wikihow.adminexpertnamechange'] = array(
	'scripts' => array( 'adminexpertnamechange.js', ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/socialproof/AdminExpertNameChange',
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
);
