<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminRedirects',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to look up a bunch of given redirects',
);

$wgSpecialPages['AdminRedirects'] = 'AdminRedirects';
$wgAutoloadClasses['AdminRedirects'] = __DIR__ . '/AdminRedirects.body.php';
