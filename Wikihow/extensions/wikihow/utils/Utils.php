<?php

$wgAutoloadClasses['FileAttachmentResponse'] = __DIR__ . '/FileAttachmentResponse.php';
$wgAutoloadClasses['UrlUtil'] = __DIR__ . '/UrlUtil.php';
$wgAutoloadClasses['WilsonConfidenceInterval'] = __DIR__ . '/WilsonConfidenceInterval.php';
$wgAutoloadClasses['BadWordFilter'] = __DIR__ . '/BadWordFilter.php';
$wgAutoloadClasses['FileUtil'] = __DIR__ . '/FileUtil.php';
$wgAutoloadClasses['RandomTitleGenerator'] = __DIR__ . '/RandomTitleGenerator.php';
$wgAutoloadClasses['GooglePageSpeedUtil'] = __DIR__ . '/GooglePageSpeedUtil.php';
$wgAutoloadClasses['DOMUtil'] = __DIR__ . '/DOMUtil.php';
$wgAutoloadClasses['TitleUtil'] = __DIR__ . '/TitleUtil.php';
$wgAutoloadClasses['TitleFilters'] = __DIR__ . '/TitleFilters.php';
$wgAutoloadClasses['StringUtil'] = __DIR__ . '/StringUtil.php';
$wgAutoloadClasses['JsonApi'] = __DIR__ . '/JsonApi.php';

$wgHooks['UnitTestsList'][] = array( 'BadWordFilter::onUnitTestsList');
