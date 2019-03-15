<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminRemoveAvatar',
	'author' => 'Reuben Smith',
	'description' => 'Tool for support personnel to remove a user\'s avatar file',
);

$wgSpecialPages['AdminRemoveAvatar'] = 'AdminRemoveAvatar';
$wgAutoloadClasses['AdminRemoveAvatar'] = __DIR__ . '/AdminRemoveAvatar.body.php';

$wgLogTypes[]             = 'avatarrm';
$wgLogNames['avatarrm']   = 'avatarrm';
$wgLogHeaders['avatarrm'] = 'avatarrmtext';

