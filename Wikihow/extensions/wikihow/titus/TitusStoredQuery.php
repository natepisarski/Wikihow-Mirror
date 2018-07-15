<?php                                                                                                                                              
if ( ! defined( 'MEDIAWIKI' ) )
    die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Titus Stored Query',
    'author' => 'Gershon Bialer',
    'description' => 'Run stored Titus queries'
);

$wgSpecialPages['TitusStoredQuery'] = 'TitusStoredQuery';
$wgAutoloadClasses['TitusStoredQuery'] = dirname(__FILE__) . '/TitusStoredQuery.body.php';

