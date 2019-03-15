<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'MyPages',
	'author' => 'Travis Derouin',
	'description' => 'Provides redirecting static urls to dynamic user pages',
);

$wgSpecialPages['MyPages'] = 'MyPages';
$wgAutoloadClasses['MyPages'] = __DIR__ . '/Mypages.body.php';

$wgExtensionMessagesFiles['MyPagesAlias'] = __DIR__ . '/Mypages.alias.php';
