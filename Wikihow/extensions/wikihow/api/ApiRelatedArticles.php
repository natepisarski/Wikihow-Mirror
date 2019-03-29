<?php
/**
 * API for related articles videos
 *
 * @license MIT
 * @author Trevor Parscal <trevorparscal@gmail.com>
 */
$wgExtensionCredits['RelatedArticles'][] = array(
	'name' => 'Related Articles',
	'author' => 'Trevor Parscal',
	'description' => 'API that lists related articles',
);
$wgAutoloadClasses['ApiRelatedArticles'] = __DIR__ . '/ApiRelatedArticles.body.php';
$wgAPIModules['related_articles'] = 'ApiRelatedArticles';
