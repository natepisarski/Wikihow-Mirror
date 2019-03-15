<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

// Extensions displays featured contributor widget
$wgAutoloadClasses['FeaturedContributor'] = __DIR__ . '/FeaturedContributor.body.php';
