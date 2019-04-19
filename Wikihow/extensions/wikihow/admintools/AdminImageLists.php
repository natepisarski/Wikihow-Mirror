<?php

if (!defined('MEDIAWIKI')) {
	die();
}


$wgSpecialPages['AdminImageLists'] = 'AdminImageLists';
$wgAutoloadClasses['AdminImageLists'] = __DIR__ . '/AdminImageLists.body.php';
