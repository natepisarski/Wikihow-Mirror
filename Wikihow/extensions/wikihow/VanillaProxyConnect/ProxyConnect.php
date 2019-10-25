<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['special'][] = array(
	'name' => 'ProxyConnect',
	'author' => 'Travis Derouin',
	'description' => 'Implements the server side functionality of Proxy Connect',
);

$wgSpecialPages['ProxyConnect'] = 'ProxyConnect';
$wgAutoloadClasses['ProxyConnect'] = __DIR__ . '/ProxyConnect.body.php';
