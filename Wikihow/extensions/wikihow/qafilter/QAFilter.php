<?php

$wgExtensionCredits['api'][] = [
	'path' => __FILE__,
	'name' => 'QA Filter API',
	'description' => 'An API for the QA Filter to update submitted questions',
	'descriptionmsg' => 'qafilterapi-desc',
	'version' => 1,
	'author' => 'George Bahij',
];

$wgAutoloadClasses['ApiQAFilter'] = __DIR__ . '/ApiQAFilter.body.php';

$wgAPIModules['qafilter'] = 'ApiQAFilter';

