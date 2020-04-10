<?php

if (!defined('MEDIAWIKI')) {
	die();
}


$wgSpecialPages['AdminYouTubeIds'] = 'AdminYouTubeIds';
$wgAutoloadClasses['AdminYouTubeIds'] = __DIR__ . '/AdminYouTubeIds.body.php';
