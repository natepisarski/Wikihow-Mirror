<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Admin Expert Doc Creator',
    'author' => 'Aaron',
    'description' => 'create shared google doc from an article',
);

$wgSpecialPages['AdminExpertDoc'] = 'AdminExpertDoc';
$wgAutoloadClasses['AdminExpertDoc'] = __DIR__ . '/AdminExpertDoc.body.php';

$wgResourceModules['ext.wikihow.adminexpertdoc'] = array(
	'scripts' => array( 'adminexpertdoc.js', ),
	'styles' => array( 'adminexpertdoc.css' ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/socialproof/AdminExpertDoc',
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
);
