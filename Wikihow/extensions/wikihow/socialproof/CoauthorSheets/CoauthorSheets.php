<?php

if (!defined('MEDIAWIKI')) {
    die();
}

$wgAutoloadClasses['CoauthorSheet'] = __DIR__ . '/CoauthorSheet.php';
$wgAutoloadClasses['CoauthorSheetMaster'] = __DIR__ . '/CoauthorSheetMaster.php';

$wgAutoloadClasses['CoauthorSheetTools'] = __DIR__ . '/CoauthorSheetTools.php';
