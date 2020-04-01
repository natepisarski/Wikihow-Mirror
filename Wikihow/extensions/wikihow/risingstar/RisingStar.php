<?php
if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['RisingStar'] = __DIR__ . '/RisingStar.body.php';

$wgHooks['MarkTitleAsRisingStar'][] = array('RisingStar::onMarkRisingStar');
