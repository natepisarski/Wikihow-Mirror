<?php

// This extension was disabled by Alberto on September 1, 2016 - Changeset: 50cddec

$wgExtensionsCredits['api'][] = array(
		'path' => __FILE__,
		'name' => 'Dedup API',
		'description' => 'An API for the dedupping tool',
		'descriptionmsg' => '',
		'version' => '1',
		'author' => 'Gershon Bialer'
		);

$wgAutoloadClasses['ApiDedup'] = __DIR__ . '/ApiDedup.body.php';

$wgAPIModules['dedup'] = 'ApiDedup';
