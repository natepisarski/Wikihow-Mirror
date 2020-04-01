<?php
if ( ! defined( 'MEDIAWIKI' ) )
    die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Titus Stored Query',
    'author' => 'Gershon Bialer',
    'description' => 'Run stored Titus queries'
);

$wgSpecialPages['TitusStoredQuery'] = 'TitusStoredQuery';
$wgAutoloadClasses['TitusStoredQuery'] = __DIR__ . '/TitusStoredQuery.body.php';

