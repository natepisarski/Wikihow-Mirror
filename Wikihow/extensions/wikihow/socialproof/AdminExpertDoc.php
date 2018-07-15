<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Admin Expert Doc Creator',
    'author' => 'Aaron',
    'description' => 'create shared google doc from an article',
);

$wgExtensionMessagesFiles['AdminExpertDoc'] = dirname(__FILE__) . '/SocialProof.i18n.php';
$wgSpecialPages['AdminExpertDoc'] = 'AdminExpertDoc';
$wgAutoloadClasses['AdminExpertDoc'] = dirname(__FILE__) . '/AdminExpertDoc.body.php';

$wgResourceModules['ext.wikihow.adminexpertdoc'] = array(
	'scripts' => array( 'adminexpertdoc.js', ),
	'styles' => array( 'adminexpertdoc.css' ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/socialproof',
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
);
