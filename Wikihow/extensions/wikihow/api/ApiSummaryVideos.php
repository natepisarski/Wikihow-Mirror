<?php
/**
 * API for querying summary videos
 *
 * @license MIT
 * @author Trevor Parscal <trevorparscal@gmail.com>
 */
$wgExtensionCredits['SummaryVideosApi'][] = array(
	'name' => 'Summary Videos API',
	'author' => 'Trevor Parscal',
	'description' => 'API that lists article summary videos',
);
$wgAutoloadClasses['ApiSummaryVideos'] = __DIR__ . '/ApiSummaryVideos.body.php';
$wgAPIModules['summary_videos'] = 'ApiSummaryVideos';
