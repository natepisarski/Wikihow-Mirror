<?php
/**
 * API for querying summary sections
 *
 * @license MIT
 * @author Trevor Parscal <trevorparscal@gmail.com>
 */
$wgExtensionCredits['SummarySection'][] = array(
	'name' => 'Summary Section',
	'author' => 'Trevor Parscal',
	'description' => 'API that gets summary sections',
);
$wgAutoloadClasses['ApiSummarySection'] = __DIR__ . '/ApiSummarySection.body.php';
$wgAPIModules['summary_section'] = 'ApiSummarySection';
