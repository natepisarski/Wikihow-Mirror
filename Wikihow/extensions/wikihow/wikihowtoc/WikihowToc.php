<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['WikihowToc'] = __DIR__ . '/WikihowToc.class.php';
$wgMessagesDirs['WikihowToc'] = __DIR__ . '/i18n';