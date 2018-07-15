<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RevertTool',
	'author' => 'Gershon Bialer',
	'description' => 'Tool to revert special edits' 
);

$wgSpecialPages['RevertTool'] = 'RevertTool';
$wgAutoloadClasses['RevertTool'] = dirname( __FILE__ ) . '/RevertTool.body.php';

