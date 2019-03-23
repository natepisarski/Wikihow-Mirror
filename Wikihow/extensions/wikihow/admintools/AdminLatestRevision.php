<?php

if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'AdminLatestRevision',
	'author' => 'Bebeth Steudel <bebeth@wikihow.com>',
	'description' => "Tool to get links to latest revisions of articles",
);

$wgSpecialPages['AdminLatestRevision'] = 'AdminLatestRevision';
$wgAutoloadClasses['AdminLatestRevision'] = __DIR__ . '/AdminLatestRevision.body.php';
