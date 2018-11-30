<?php

$wgAutoloadClasses['FileAttachmentResponse'] = dirname( __FILE__ ) . '/FileAttachmentResponse.php';
$wgAutoloadClasses['FileAttachmentMailer'] = dirname( __FILE__ ) . '/FileAttachmentMailer.php';
$wgAutoloadClasses['DataUtil'] = dirname( __FILE__ ) . '/DataUtil.php';
$wgAutoloadClasses['UrlUtil'] = dirname( __FILE__ ) . '/UrlUtil.php';
$wgAutoloadClasses['WilsonConfidenceInterval'] = dirname( __FILE__ ) . '/WilsonConfidenceInterval.php';
$wgAutoloadClasses['BadWordFilter'] = dirname( __FILE__ ) . '/BadWordFilter.php';
$wgAutoloadClasses['FileUtil'] = dirname( __FILE__ ) . '/FileUtil.php';
$wgAutoloadClasses['RandomTitleGenerator'] = dirname( __FILE__ ) . '/RandomTitleGenerator.php';
$wgAutoloadClasses['GooglePageSpeedUtil'] = dirname( __FILE__ ) . '/GooglePageSpeedUtil.php';
$wgAutoloadClasses['DOMUtil'] = dirname( __FILE__ ) . '/DOMUtil.php';
$wgAutoloadClasses['TitleUtil'] = dirname( __FILE__ ) . '/TitleUtil.php';
$wgAutoloadClasses['TitleFilters'] = dirname( __FILE__ ) . '/TitleFilters.php';
$wgAutoloadClasses['StringUtil'] = dirname( __FILE__ ) . '/StringUtil.php';
$wgAutoloadClasses['JsonApi'] = dirname( __FILE__ ) . '/JsonApi.php';

$wgHooks['UnitTestsList'][] = array( 'BadWordFilter::onUnitTestsList');
